<?php

namespace App\Services\Sync;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sincronização "last-write-wins" baseada em updated_at.
 *
 * Cada registro tem UUID, updated_at e deleted_at (soft delete), então basta
 * trocar as linhas alteradas desde o último sync: a versão com updated_at
 * mais recente prevalece. Exclusões são propagadas pelo deleted_at.
 */
class SyncService
{
    /**
     * Tabelas sincronizadas, na ordem em que devem ser aplicadas.
     */
    public const TABLES = [
        'accounts',
        'categories',
        'category_rules',
        'bank_profiles',
        'import_batches',
        'transactions',
        'investments',
    ];

    /** @var array<string, array<int, string>> */
    private array $columns = [];

    /**
     * Linhas alteradas (inclusive excluídas) desde $since.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function changesSince(?string $since): array
    {
        $changes = [];

        foreach (self::TABLES as $table) {
            $query = DB::table($table);
            if ($since) {
                // ">=" para não perder alterações no mesmo segundo; aplicar é idempotente.
                $query->where('updated_at', '>=', $since);
            }
            $rows = $query->orderBy('updated_at')->get()->map(fn ($r) => (array) $r)->all();
            if ($rows) {
                $changes[$table] = $rows;
            }
        }

        return $changes;
    }

    /**
     * Aplica alterações vindas do outro dispositivo.
     *
     * @param  array<string, array<int, array<string, mixed>>>  $changes
     * @return array{inserted: int, updated: int, ignored: int}
     */
    public function apply(array $changes): array
    {
        $stats = ['inserted' => 0, 'updated' => 0, 'ignored' => 0];

        DB::transaction(function () use ($changes, &$stats) {
            foreach (self::TABLES as $table) {
                foreach ($changes[$table] ?? [] as $row) {
                    $row = Arr::only((array) $row, $this->columns($table));
                    if (empty($row['id']) || empty($row['updated_at'])) {
                        $stats['ignored']++;

                        continue;
                    }

                    $current = DB::table($table)->where('id', $row['id'])->value('updated_at');

                    if ($current === null) {
                        DB::table($table)->insert($row);
                        $stats['inserted']++;
                    } elseif (Carbon::parse($row['updated_at'])->gt(Carbon::parse($current))) {
                        DB::table($table)->where('id', $row['id'])->update(Arr::except($row, ['id']));
                        $stats['updated']++;
                    } else {
                        $stats['ignored']++;
                    }
                }
            }
        });

        return $stats;
    }

    /**
     * Horário atual no formato gravado pelo Eloquent (usado como cursor).
     */
    public function now(): string
    {
        return now()->format('Y-m-d H:i:s');
    }

    /** @return array<int, string> */
    private function columns(string $table): array
    {
        return $this->columns[$table] ??= Schema::getColumnListing($table);
    }
}
