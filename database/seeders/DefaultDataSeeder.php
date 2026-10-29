<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

/**
 * Dados iniciais (categorias, regras, perfis de banco e contas).
 *
 * Usa UUIDs determinísticos (v5) e um updated_at antigo fixo: assim o PC e o
 * celular criam exatamente os mesmos registros, sem duplicar após o sync, e
 * qualquer edição do usuário sempre prevalece sobre o padrão.
 */
class DefaultDataSeeder extends Seeder
{
    private const NAMESPACE = '6f1c1c2e-5d1b-4b8e-9a51-0b7c3c1f0a10';

    private const STAMP = '2026-01-01 00:00:00';

    public static function id(string $name): string
    {
        return Uuid::uuid5(self::NAMESPACE, $name)->toString();
    }

    public function run(): void
    {
        $this->accounts();
        $this->categoriesAndRules();
        $this->bankProfiles();
    }

    private function accounts(): void
    {
        $accounts = [
            ['Nubank - Cartão', 'credit_card', '#8a05be'],
            ['Nubank - Conta', 'checking', '#8a05be'],
            ['Mercado Pago', 'wallet', '#00b1ea'],
            ['Dinheiro', 'cash', '#16a34a'],
        ];

        foreach ($accounts as [$name, $type, $color]) {
            DB::table('accounts')->insertOrIgnore($this->stamp([
                'id' => self::id("account:{$name}"),
                'name' => $name,
                'type' => $type,
                'color' => $color,
                'initial_balance_cents' => 0,
                'archived' => false,
            ]));
        }
    }

    private function categoriesAndRules(): void
    {
        // nome => [tipo, cor, ícone, [padrões de regra]]
        $categories = [
            'Alimentação' => ['expense', '#f97316', 'restaurant', ['ifood', 'keeta', 'restaurante', 'lanchonete', 'pastel', 'buffet', 'padaria', 'pizzaria', 'burger', 'mcdonald']],
            'Mercado' => ['expense', '#84cc16', 'shopping_cart', ['oxxo', 'supermercado', 'mercado', 'carrefour', 'pao de acucar', 'atacadao', 'assai', 'comercial']],
            'Transporte' => ['expense', '#0ea5e9', 'directions_car', ['99app', 'uber', 'posto', 'combustivel', 'estacionamento', 'sem parar', 'metro']],
            'Assinaturas' => ['expense', '#a855f7', 'subscriptions', ['google one', 'globoplay', 'netflix', 'spotify', 'amazon prime', 'disney', 'youtube', 'apple.com', 'hbo']],
            'Saúde e bem-estar' => ['expense', '#ef4444', 'medical_services', ['bluefit', 'smartfit', 'farmacia', 'drogasil', 'droga raia', 'pague menos', 'clinica', 'laboratorio']],
            'Educação' => ['expense', '#6366f1', 'school', ['unisant', 'mensalidade', 'curso', 'udemy', 'alura', 'livraria']],
            'Pets' => ['expense', '#d97706', 'pets', ['cobasi', 'petz', 'petshop', 'veterinario']],
            'Moradia' => ['expense', '#64748b', 'home', ['aluguel', 'condominio', 'energia', 'enel', 'sabesp', 'internet', 'vivo', 'claro', 'tim']],
            'Lazer' => ['expense', '#ec4899', 'sports_esports', ['cinema', 'ingresso', 'show', 'bar ', 'steam', 'playstation']],
            'Compras' => ['expense', '#14b8a6', 'shopping_bag', ['mercadolivre', 'mercado livre', 'shopee', 'amazon', 'magalu', 'aliexpress', 'shein']],
            'Tarifas e juros' => ['expense', '#b91c1c', 'account_balance', ['iof', 'multa', 'juros', 'tarifa', 'anuidade', 'encargos']],
            'Outros' => ['expense', '#94a3b8', 'inventory_2', []],
            'Salário' => ['income', '#22c55e', 'payments', ['salario', 'pagamento de salario', 'folha']],
            'Outras receitas' => ['income', '#10b981', 'trending_up', ['rendimento', 'cashback', 'estorno', 'reembolso']],
            'Transferências' => ['transfer', '#0f766e', 'sync_alt', ['pagamento recebido', 'pagamento de fatura', 'valor pendente', 'transferencia', 'pix enviado', 'pix recebido']],
        ];

        foreach ($categories as $name => [$kind, $color, $icon, $patterns]) {
            $categoryId = self::id("category:{$name}");

            DB::table('categories')->insertOrIgnore($this->stamp([
                'id' => $categoryId,
                'name' => $name,
                'kind' => $kind,
                'color' => $color,
                'icon' => $icon,
            ]));

            foreach ($patterns as $pattern) {
                DB::table('category_rules')->insertOrIgnore($this->stamp([
                    'id' => self::id("rule:{$pattern}"),
                    'pattern' => $pattern,
                    'category_id' => $categoryId,
                    'priority' => $kind === 'transfer' ? 10 : 0,
                ]));
            }
        }
    }

    private function bankProfiles(): void
    {
        $profiles = [
            [
                // Exportação da fatura do cartão: date,title,amount  (2026-09-16,Giani Buffet,"12,00")
                // Valores positivos são gastos; pagamentos aparecem negativos ("- 2.547,67").
                'name' => 'Nubank - Cartão de crédito',
                'slug' => 'nubank-cartao',
                'delimiter' => ',',
                'encoding' => 'UTF-8',
                'date_format' => 'Y-m-d',
                'decimal_separator' => ',',
                'column_map' => ['date' => 'date', 'description' => 'title', 'amount' => 'amount'],
                'invert_sign' => true,
            ],
            [
                // Extrato da conta: Data,Valor,Identificador,Descrição  (01/09/2026,-12.50,...)
                'name' => 'Nubank - Conta',
                'slug' => 'nubank-conta',
                'delimiter' => ',',
                'encoding' => 'UTF-8',
                'date_format' => 'd/m/Y',
                'decimal_separator' => '.',
                'column_map' => ['date' => 'Data', 'description' => 'Descrição', 'amount' => 'Valor'],
                'invert_sign' => false,
            ],
        ];

        foreach ($profiles as $profile) {
            $profile['column_map'] = json_encode($profile['column_map'], JSON_UNESCAPED_UNICODE);
            $profile['skip_rows'] = 0;
            $profile['id'] = self::id("bank_profile:{$profile['slug']}");
            DB::table('bank_profiles')->insertOrIgnore($this->stamp($profile));
        }
    }

    private function stamp(array $row): array
    {
        return $row + ['created_at' => self::STAMP, 'updated_at' => self::STAMP];
    }
}
