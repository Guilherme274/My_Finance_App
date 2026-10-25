<?php

namespace App\Services\Import;

use App\Models\Account;
use App\Models\BankProfile;
use App\Models\ImportBatch;
use App\Models\Transaction;
use App\Services\CategoryMatcher;
use App\Support\Money;
use App\Support\Text;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Importa extratos CSV de bancos usando um "perfil" (BankProfile) que descreve
 * delimitador, encoding, formato de data, colunas e convenção de sinal.
 *
 * Fluxo: parse() -> preview() (marca duplicados e sugere categorias) -> commit().
 */
class CsvImporter
{
    public function __construct(private readonly CategoryMatcher $matcher) {}

    /**
     * Lê o conteúdo do CSV e devolve as linhas normalizadas.
     *
     * @return array<int, array{date: string, description: string, amount_cents: int, installment: ?int, installments: ?int, line: int}>
     */
    public function parse(string $contents, BankProfile $profile): array
    {
        $contents = $this->toUtf8($contents, $profile->encoding);
        $delimiter = $profile->delimiter === 'auto' ? $this->detectDelimiter($contents) : $profile->delimiter;

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $contents);
        rewind($handle);

        for ($i = 0; $i < $profile->skip_rows; $i++) {
            fgets($handle);
        }

        $header = fgetcsv($handle, null, $delimiter, '"', '');
        if (! $header) {
            throw new RuntimeException('Arquivo CSV vazio ou sem cabeçalho.');
        }

        $index = $this->resolveColumns($header, $profile->column_map);
        $rows = [];
        $line = 1 + $profile->skip_rows;

        while (($data = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
            $line++;
            if ($data === [null] || trim(implode('', $data)) === '') {
                continue;
            }

            try {
                $rows[] = $this->mapRow($data, $index, $profile, $line);
            } catch (Throwable $e) {
                throw new RuntimeException("Linha {$line}: {$e->getMessage()}", previous: $e);
            }
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Acrescenta hash de deduplicação, flag de duplicado e categoria sugerida.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function preview(array $rows, Account $account): array
    {
        $occurrences = [];

        foreach ($rows as &$row) {
            $base = implode('|', [$account->id, $row['date'], $row['amount_cents'], Text::normalize($row['description'])]);
            // Lançamentos idênticos no mesmo arquivo (ex.: dois cafés iguais no mesmo dia) recebem
            // hashes distintos pela ordem de ocorrência; reimportar o mesmo arquivo gera os mesmos hashes.
            $occurrences[$base] = ($occurrences[$base] ?? 0) + 1;
            $row['hash'] = sha1($base.'|'.$occurrences[$base]);
            $row['category_id'] = $this->matcher->match($row['description']);
        }
        unset($row);

        $existing = Transaction::withTrashed()
            ->where('account_id', $account->id)
            ->whereIn('hash', array_column($rows, 'hash'))
            ->pluck('hash')
            ->flip();

        foreach ($rows as &$row) {
            $row['duplicate'] = $existing->has($row['hash']);
        }
        unset($row);

        return $rows;
    }

    /**
     * Grava as linhas (não duplicadas) em um lote de importação.
     *
     * @param  array<int, array<string, mixed>>  $rows  Linhas vindas de preview() (category_id pode ter sido alterado pelo usuário)
     */
    public function commit(array $rows, Account $account, ?BankProfile $profile, ?string $filename, string $source = 'csv'): ImportBatch
    {
        return DB::transaction(function () use ($rows, $account, $profile, $filename, $source) {
            $batch = ImportBatch::create([
                'account_id' => $account->id,
                'bank_profile_id' => $profile?->id,
                'source' => $source,
                'filename' => $filename,
            ]);

            $imported = 0;
            $skipped = 0;

            foreach ($rows as $row) {
                if (! empty($row['duplicate']) || ! empty($row['skip'])) {
                    $skipped++;

                    continue;
                }

                Transaction::create([
                    'account_id' => $account->id,
                    'category_id' => $row['category_id'] ?: null,
                    'date' => $row['date'],
                    'description' => $row['description'],
                    'amount_cents' => $row['amount_cents'],
                    'installment' => $row['installment'],
                    'installments' => $row['installments'],
                    'source' => $source,
                    'external_id' => $row['external_id'] ?? null,
                    'hash' => $row['hash'],
                    'import_batch_id' => $batch->id,
                ]);
                $imported++;
            }

            $batch->update(['rows_imported' => $imported, 'rows_skipped' => $skipped]);

            return $batch;
        });
    }

    /**
     * Desfaz uma importação inteira.
     */
    public function undo(ImportBatch $batch): void
    {
        DB::transaction(function () use ($batch) {
            $batch->transactions()->get()->each->delete();
            $batch->delete();
        });
    }

    private function toUtf8(string $contents, string $encoding): string
    {
        // Remove BOM
        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            $contents = substr($contents, 3);
        }

        if (strtoupper($encoding) !== 'UTF-8' || ! mb_check_encoding($contents, 'UTF-8')) {
            $from = strtoupper($encoding) === 'UTF-8' ? 'ISO-8859-1' : $encoding;
            $contents = mb_convert_encoding($contents, 'UTF-8', $from);
        }

        return str_replace(["\r\n", "\r"], "\n", $contents);
    }

    private function detectDelimiter(string $contents): string
    {
        $firstLine = strtok($contents, "\n") ?: '';
        $counts = [';' => substr_count($firstLine, ';'), ',' => substr_count($firstLine, ','), "\t" => substr_count($firstLine, "\t")];
        arsort($counts);

        return (string) array_key_first($counts);
    }

    /**
     * @param  array<int, string|null>  $header
     * @param  array<string, string>  $map  campo => nome da coluna no CSV
     * @return array<string, int>
     */
    private function resolveColumns(array $header, array $map): array
    {
        $normalizedHeader = array_map(fn ($h) => Text::normalize((string) $h), $header);
        $index = [];

        foreach ($map as $field => $column) {
            $position = array_search(Text::normalize($column), $normalizedHeader, true);
            if ($position === false) {
                throw new RuntimeException("Coluna \"{$column}\" não encontrada no CSV. Colunas: ".implode(', ', $header));
            }
            $index[$field] = $position;
        }

        foreach (['date', 'description'] as $required) {
            if (! isset($index[$required])) {
                throw new RuntimeException("Perfil sem mapeamento para \"{$required}\".");
            }
        }

        if (! isset($index['amount']) && ! (isset($index['debit']) || isset($index['credit']))) {
            throw new RuntimeException('Perfil sem coluna de valor (amount ou debit/credit).');
        }

        return $index;
    }

    /**
     * @param  array<int, string|null>  $data
     * @param  array<string, int>  $index
     */
    private function mapRow(array $data, array $index, BankProfile $profile, int $line): array
    {
        $get = fn (string $field) => isset($index[$field]) ? trim((string) ($data[$index[$field]] ?? '')) : '';

        $date = CarbonImmutable::createFromFormat('!'.$profile->date_format, $get('date'));
        if (! $date) {
            throw new RuntimeException("data inválida \"{$get('date')}\" (formato esperado {$profile->date_format}).");
        }

        if (isset($index['amount'])) {
            $amount = Money::parse($get('amount'), $profile->decimal_separator);
        } else {
            $debit = $get('debit') !== '' ? abs(Money::parse($get('debit'), $profile->decimal_separator)) : 0;
            $credit = $get('credit') !== '' ? abs(Money::parse($get('credit'), $profile->decimal_separator)) : 0;
            $amount = $credit - $debit;
        }

        if ($profile->invert_sign) {
            $amount = -$amount;
        }

        $description = $get('description');
        [$installment, $installments] = $this->detectInstallment($description);

        return [
            'date' => $date->toDateString(),
            'description' => $description,
            'amount_cents' => $amount,
            'installment' => $installment,
            'installments' => $installments,
            'line' => $line,
        ];
    }

    /**
     * Detecta "Parcela 7/12" na descrição.
     *
     * @return array{0: ?int, 1: ?int}
     */
    private function detectInstallment(string $description): array
    {
        if (preg_match('/parcela\s*(\d{1,3})\s*\/\s*(\d{1,3})/i', $description, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }

        return [null, null];
    }
}
