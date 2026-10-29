<?php

namespace Tests\Feature;

use App\Livewire\Transactions;
use App\Models\Account;
use App\Models\BankProfile;
use App\Models\Category;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\Import\CsvImporter;
use App\Services\MercadoPago\KnownAccountsDictionary;
use App\Services\MercadoPago\MercadoPagoImporter;
use App\Services\Sync\SyncClient;
use App\Services\Sync\SyncService;
use App\Support\BankBrand;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_money_parser_handles_brazilian_formats_and_nubank_signs(): void
    {
        $this->assertSame(1200, Money::parse('12,00'));
        $this->assertSame(-254767, Money::parse('- 2.547,67'));
        $this->assertSame(104175, Money::parse('1.041,75'));
        $this->assertSame('R$ 1.041,75', Money::format(104175));
        $this->assertSame('-R$ 2.547,67', Money::format(-254767));
    }

    public function test_imports_real_nubank_csv_categorizes_and_deduplicates(): void
    {
        $csv = <<<'CSV'
date,title,amount
2026-09-16,Giani Buffet,"12,00"
2026-09-13,iFood - NuPay,"11,89"
2026-09-12,Google One,"35,99"
2026-09-11,Pagamento recebido,"- 2.547,67"
2026-09-11,IOF por fatura atrasada,"10,13"
2026-09-11,Oxxo Gonzaga,"18,00"
2026-09-10,99app *99app,"14,50"
2026-09-05,0 Bluefit Santos Iii,"141,19"
2026-09-03,Unisant*Mensalidadegr,"1.041,75"
2026-09-03,Cobasi,"59,99"
2026-09-02,Globo Globoplay - Parcela 7/12,"16,90"
CSV;

        $importer = app(CsvImporter::class);
        $profile = BankProfile::where('slug', 'nubank-cartao')->firstOrFail();
        $account = Account::where('name', 'Nubank - Cartão')->firstOrFail();

        $parsed = $importer->parse($csv, $profile);
        $preview = $importer->preview($parsed, $account);

        $this->assertCount(11, $preview);
        // Compras na fatura viram despesas (negativas); pagamento recebido vira positivo
        $this->assertSame(-1200, $preview[0]['amount_cents']);
        $this->assertSame(254767, $preview[3]['amount_cents']);
        // Parcela 7/12 detectada
        $this->assertSame(7, $preview[10]['installment']);
        $this->assertSame(12, $preview[10]['installments']);

        // Categorização pelas regras padrão
        $categoriesById = Category::pluck('name', 'id');
        $this->assertSame('Alimentação', $categoriesById[$preview[1]['category_id']] ?? null);
        $this->assertSame('Assinaturas', $categoriesById[$preview[2]['category_id']] ?? null);
        $this->assertSame('Transferências', $categoriesById[$preview[3]['category_id']] ?? null);
        $this->assertSame('Tarifas e juros', $categoriesById[$preview[4]['category_id']] ?? null);
        $this->assertSame('Mercado', $categoriesById[$preview[5]['category_id']] ?? null);
        $this->assertSame('Transporte', $categoriesById[$preview[6]['category_id']] ?? null);
        $this->assertSame('Saúde e bem-estar', $categoriesById[$preview[7]['category_id']] ?? null);
        $this->assertSame('Educação', $categoriesById[$preview[8]['category_id']] ?? null);
        $this->assertSame('Pets', $categoriesById[$preview[9]['category_id']] ?? null);

        $batch = $importer->commit($preview, $account, $profile, 'Nubank_2026-10-09.csv');
        $this->assertSame(11, $batch->rows_imported);
        $this->assertSame(0, $batch->rows_skipped);

        // Reimportar o mesmo arquivo marca tudo como duplicado e não duplica nada
        $previewAgain = $importer->preview($importer->parse($csv, $profile), $account);
        $this->assertTrue(collect($previewAgain)->every(fn ($r) => $r['duplicate'] === true));

        $batch2 = $importer->commit($previewAgain, $account, $profile, 'Nubank_2026-10-09.csv');
        $this->assertSame(0, $batch2->rows_imported);
        $this->assertSame(11, $batch2->rows_skipped);
        $this->assertSame(11, Transaction::count());

        // Desfazer lote remove os lançamentos
        $importer->undo($batch);
        $this->assertSame(0, Transaction::count());
    }

    public function test_sync_api_pushes_and_pulls_with_last_write_wins_and_wifi_to_tailscale_fallback(): void
    {
        config(['finance.role' => 'hub', 'finance.sync.token' => 'secret-token']);

        // 1) Endpoints HTTP do Hub exigem token
        $this->getJson('/api/sync/ping')->assertUnauthorized();
        $this->withToken('secret-token')->getJson('/api/sync/ping')
            ->assertOk()
            ->assertJson(['app' => 'gestao-financeira']);

        $account = Account::first();
        $txId = '11111111-2222-3333-4444-555555555555';

        // 2) Push insere um lançamento vindo do celular
        $this->withToken('secret-token')->postJson('/api/sync/push', [
            'changes' => [
                'transactions' => [[
                    'id' => $txId,
                    'account_id' => $account->id,
                    'category_id' => null,
                    'date' => '2026-10-10',
                    'description' => 'Padaria no celular',
                    'amount_cents' => -1500,
                    'source' => 'manual',
                    'created_at' => '2026-10-10 10:00:00',
                    'updated_at' => '2026-10-10 10:00:00',
                    'deleted_at' => null,
                ]],
            ],
        ])->assertOk()->assertJsonPath('stats.inserted', 1);

        $this->assertDatabaseHas('transactions', ['id' => $txId, 'description' => 'Padaria no celular']);

        // 3) Versão mais antiga é ignorada; versão mais recente atualiza (last-write-wins)
        $sync = app(SyncService::class);
        $statsOld = $sync->apply(['transactions' => [[
            'id' => $txId,
            'account_id' => $account->id,
            'date' => '2026-10-10',
            'description' => 'Versao antiga',
            'amount_cents' => -1500,
            'source' => 'manual',
            'created_at' => '2026-10-10 10:00:00',
            'updated_at' => '2026-10-10 09:00:00',
        ]]]);
        $this->assertSame(1, $statsOld['ignored']);
        $this->assertSame('Padaria no celular', Transaction::find($txId)->description);

        // 4) SyncClient tenta Wi-Fi primeiro e cai automaticamente para o IP do Tailscale
        Setting::put('sync_hub_urls', "http://192.168.0.10:8000\nhttp://100.90.1.2:8000");
        Setting::put('sync_token', 'secret-token');

        Http::fake([
            'http://192.168.0.10:8000/*' => fn () => throw new ConnectionException('Wi-Fi fora de alcance'),
            'http://100.90.1.2:8000/api/sync/ping' => Http::response(['app' => 'gestao-financeira', 'server_time' => '2026-10-10 12:00:00']),
            'http://100.90.1.2:8000/api/sync/push' => Http::response(['stats' => ['inserted' => 1, 'updated' => 0, 'ignored' => 0], 'server_time' => '2026-10-10 12:00:00']),
            'http://100.90.1.2:8000/api/sync/pull*' => Http::response(['changes' => [], 'server_time' => '2026-10-10 12:00:00']),
        ]);

        $result = app(SyncClient::class)->sync();
        $this->assertSame('http://100.90.1.2:8000', $result['url']);
    }

    public function test_mercado_pago_importer_maps_and_deduplicates_payments(): void
    {
        config(['finance.mercadopago.access_token' => 'APP_USR-test-token']);

        Http::fake([
            'https://api.mercadopago.com/users/me' => Http::response([
                'id' => 999,
                'nickname' => 'GUILHERME',
                'email' => 'guilh@example.com',
                'site_id' => 'MLB',
            ]),
            'https://api.mercadopago.com/v1/payments/search*' => Http::response([
                'results' => [
                    [
                        'id' => 123456,
                        'status' => 'approved',
                        'collector_id' => 999,
                        'transaction_amount' => 150.00,
                        'transaction_details' => ['net_received_amount' => 145.50],
                        'description' => 'Venda recebida',
                        'date_approved' => '2026-10-05T14:30:00.000-03:00',
                    ],
                    [
                        'id' => 123457,
                        'status' => 'approved',
                        'collector_id' => 88888,
                        'payer' => ['id' => 999],
                        'transaction_amount' => 35.99,
                        'transaction_details' => ['total_paid_amount' => 35.99],
                        'description' => 'iFood pedido',
                        'date_approved' => '2026-10-06T20:00:00.000-03:00',
                    ],
                ],
                'paging' => ['total' => 2, 'limit' => 50, 'offset' => 0],
            ]),
        ]);

        $importer = app(MercadoPagoImporter::class);
        $batch = $importer->import(30);

        $this->assertSame(2, $batch->rows_imported);
        $this->assertDatabaseHas('transactions', ['external_id' => 'mp:123456', 'amount_cents' => 14550]);
        $this->assertDatabaseHas('transactions', ['external_id' => 'mp:123457', 'amount_cents' => -3599]);

        // Segunda execução não duplica
        $batch2 = $importer->import(30);
        $this->assertSame(0, $batch2->rows_imported);
        $this->assertSame(2, $batch2->rows_skipped);
    }

    public function test_main_screens_render_successfully(): void
    {
        foreach (['/', '/agenda', '/nova-transacao', '/orcamento', '/investimentos', '/lancamentos', '/contas', '/categorias', '/importar', '/sincronizar', '/mercado-pago'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_bulk_category_update_applies_to_selected_and_same_description(): void
    {
        $account = Account::firstOrFail();
        $category = Category::where('name', 'Alimentação')->firstOrFail();

        $tx1 = Transaction::create([
            'account_id' => $account->id,
            'category_id' => null,
            'date' => '2026-10-10',
            'description' => 'Padaria Central',
            'amount_cents' => -2500,
            'source' => 'manual',
        ]);

        $tx2 = Transaction::create([
            'account_id' => $account->id,
            'category_id' => null,
            'date' => '2026-10-12',
            'description' => 'Mercado Bairro',
            'amount_cents' => -8900,
            'source' => 'manual',
        ]);

        $txSameDesc = Transaction::create([
            'account_id' => $account->id,
            'category_id' => null,
            'date' => '2026-09-15',
            'description' => 'Padaria Central',
            'amount_cents' => -1800,
            'source' => 'manual',
        ]);

        Livewire::test(Transactions::class)
            ->call('toggleBulkMode')
            ->assertSet('bulkMode', true)
            ->call('toggleSelectTransaction', $tx1->id)
            ->call('toggleSelectTransaction', $tx2->id)
            ->set('bulkCategoryId', $category->id)
            ->set('bulkApplySameDescription', true)
            ->call('applyBulkCategory')
            ->assertDispatched('emerald-feedback');

        $this->assertSame($category->id, $tx1->fresh()->category_id);
        $this->assertSame($category->id, $tx2->fresh()->category_id);
        $this->assertSame($category->id, $txSameDesc->fresh()->category_id);
    }

    public function test_known_accounts_dictionary_updates_payout_and_account_id_transactions(): void
    {
        $account = Account::firstOrCreate(
            ['slug' => 'mercado-pago'],
            ['name' => 'Mercado Pago', 'type' => 'checking', 'color' => '#ffe600']
        );

        $tx = Transaction::create([
            'account_id' => $account->id,
            'category_id' => null,
            'date' => '2026-10-04',
            'description' => 'MP Transferência / Pix Enviado (#182429240030)',
            'amount_cents' => -1700,
            'source' => 'mercadopago',
            'external_id' => 'mp:payout:182429240030',
        ]);

        $dict = app(KnownAccountsDictionary::class);
        $dict->addCustom('Nubank', '1', '182429240030', 'Maria Eduarda');

        $updated = $dict->applyToExistingTransactions();
        $this->assertGreaterThanOrEqual(1, $updated);

        $tx->refresh();
        $this->assertSame('MP Pix: Maria Eduarda (Nubank Ag. 1 C. 182429240030)', $tx->description);

        $parsed = BankBrand::parse($tx);
        $this->assertSame('mercadopago', $parsed['brand']);
        $this->assertSame('Pix: Maria Eduarda (Nubank Ag. 1 C. 182429240030)', $parsed['cleanDescription']);
    }

    public function test_pin_resolution_verification_and_settings_management(): void
    {
        // 1) Resolução a partir do config/env quando não há PIN no banco
        Setting::query()->where('key', 'settings_app_pin')->delete();
        config(['finance.pin' => '31012026']);

        $this->assertTrue(Setting::hasPin());
        $this->assertSame('31012026', Setting::getPin());
        $this->assertTrue(Setting::verifyPin('31012026'));
        $this->assertFalse(Setting::verifyPin('00000000'));

        // 2) Autenticação via rota /pin com PIN correto
        $this->post('/pin', ['pin' => '31012026'])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('pin_ok', true);

        // 3) Autenticação via rota /pin com PIN incorreto
        $this->post('/pin', ['pin' => '99999999'])
            ->assertSessionHasErrors(['pin']);

        // 4) Gerenciamento do PIN via tela de configurações (Livewire)
        Livewire::test(\App\Livewire\SettingsPage::class)
            ->assertSet('hasPin', true)
            ->assertSet('currentPinSource', 'env')
            ->call('toggleEditPin')
            ->assertSet('isEditingPin', true)
            ->set('newPin', '1234')
            ->set('newPinConfirmation', '1234')
            ->call('savePin')
            ->assertHasNoErrors()
            ->assertSet('hasPin', true)
            ->assertSet('currentPinSource', 'app')
            ->assertDispatched('settings-saved');

        // PIN configurado no banco sobrepõe o .env
        $this->assertSame('1234', Setting::getPin());
        $this->assertTrue(Setting::verifyPin('1234'));
        $this->assertFalse(Setting::verifyPin('31012026'));

        // 5) Remoção do PIN via tela de configurações
        Livewire::test(\App\Livewire\SettingsPage::class)
            ->call('removePin')
            ->assertSet('currentPinSource', 'env');

        // Se remover o config também, fica totalmente desativado
        config(['finance.pin' => null]);
        $this->assertFalse(Setting::hasPin());
        $this->assertNull(Setting::getPin());

        // 6) Tentar ativar biometria sem PIN acusa erro amigável
        Livewire::test(\App\Livewire\SettingsPage::class)
            ->call('enableBiometrics')
            ->assertHasErrors(['biometrics']);
    }

    public function test_category_include_in_budget_flag_and_salary_reference(): void
    {
        $account = Account::firstOrFail();

        // 1) Criar categoria fora do orçamento (ex: Pagamento de Fatura)
        $catFatura = Category::create([
            'name' => 'Fatura Cartão',
            'kind' => 'transfer',
            'color' => '#64748b',
            'include_in_budget' => false,
        ]);

        $catAlim = Category::create([
            'name' => 'Restaurante Teste',
            'kind' => 'expense',
            'color' => '#f97316',
            'include_in_budget' => true,
            'monthly_budget_percent' => 10.0,
        ]);

        $this->assertFalse($catFatura->include_in_budget);
        $this->assertTrue($catAlim->include_in_budget);

        $month = '2026-10';

        // Lançamento de pagamento de fatura: R$ 2.323,57 (não deve inflacionar despesas do mês)
        $txFatura = Transaction::create([
            'account_id' => $account->id,
            'category_id' => $catFatura->id,
            'date' => '2026-10-09',
            'description' => 'Pagamento de fatura Nubank',
            'amount_cents' => -232357,
            'source' => 'manual',
        ]);

        // Lançamento de despesa real: R$ 150,00
        $txAlim = Transaction::create([
            'account_id' => $account->id,
            'category_id' => $catAlim->id,
            'date' => '2026-10-10',
            'description' => 'Jantar',
            'amount_cents' => -15000,
            'source' => 'manual',
        ]);

        // Escopo includedInBudget exclui o pagamento de fatura
        $budgetTxs = Transaction::query()->inMonth($month)->includedInBudget()->get();
        $this->assertTrue($budgetTxs->contains('id', $txAlim->id));
        $this->assertFalse($budgetTxs->contains('id', $txFatura->id));

        // 2) Testar inserção do salário na página de orçamento e cálculo das metas
        Livewire::test(\App\Livewire\BudgetPage::class, ['month' => $month])
            ->set('salaryInput', '5.000,00')
            ->call('saveSalary')
            ->assertSet('salaryCents', 500000)
            ->assertSee('5.000,00');

        $this->assertSame(500000, (int) Setting::get('budget_salary_cents'));

        // 3) Verificar renderização da página com o salário de 5000 e despesa excluindo a fatura
        Livewire::test(\App\Livewire\BudgetPage::class, ['month' => $month])
            ->assertViewHas('referenceSalaryCents', 500000)
            ->assertViewHas('currentExpenseCents', 15000) // apenas os 150 reais, sem os 2323,57
            ->assertViewHas('essentialTargetCents', 250000) // 50% de 5.000 = 2.500
            ->assertViewHas('lifestyleTargetCents', 150000) // 30% de 5.000 = 1.500
            ->assertViewHas('investTargetCents', 100000); // 20% de 5.000 = 1.000

        // 4) Gerenciamento da flag include_in_budget na tela de Categorias
        Livewire::test(\App\Livewire\Categories::class)
            ->call('create')
            ->set('name', 'Nova Categoria Fatura')
            ->set('kind', 'transfer')
            ->assertSet('includeInBudget', false)
            ->call('save')
            ->assertHasNoErrors();

        $savedCat = Category::where('name', 'Nova Categoria Fatura')->firstOrFail();
        $this->assertFalse($savedCat->include_in_budget);
    }
}
