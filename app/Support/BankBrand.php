<?php

namespace App\Support;

use App\Models\Transaction;

final class BankBrand
{
    /**
     * Identifica a bandeira bancária (mercadopago, nubank, etc.) e remove prefixos
     * textuais redundantes (como "MP " ou "MP: ") para exibição limpa com o ícone oficial.
     *
     * @return array{brand: string|null, cleanDescription: string}
     */
    public static function parse(Transaction|string $transactionOrDesc, ?string $accountName = null): array
    {
        $desc = $transactionOrDesc instanceof Transaction ? $transactionOrDesc->description : $transactionOrDesc;
        $acc = $transactionOrDesc instanceof Transaction ? ($transactionOrDesc->account?->name ?? '') : ($accountName ?? '');
        $extId = $transactionOrDesc instanceof Transaction ? (string) $transactionOrDesc->external_id : '';

        $brand = null;
        $clean = $desc;

        // Mercado Pago
        if (preg_match('/^MP[\s:\-]+/i', $clean)) {
            $brand = 'mercadopago';
            $clean = preg_replace('/^MP[\s:\-]+/i', '', $clean);
        } elseif (str_starts_with($extId, 'mp:') || str_contains(mb_strtolower($acc), 'mercado pago')) {
            $brand = 'mercadopago';
        }

        // Nubank
        if (! $brand) {
            if (str_contains(mb_strtolower($acc), 'nubank') || str_contains(mb_strtolower($desc), 'nubank')) {
                $brand = 'nubank';
            }
        }

        return [
            'brand' => $brand,
            'cleanDescription' => trim((string) $clean) ?: $desc,
        ];
    }
}
