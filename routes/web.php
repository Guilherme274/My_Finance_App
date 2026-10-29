<?php

use App\Livewire\Accounts;
use App\Livewire\BudgetPage;
use App\Livewire\CalendarPage;
use App\Livewire\Categories;
use App\Livewire\Dashboard;
use App\Livewire\ImportCsv;
use App\Livewire\Integrations;
use App\Livewire\InvestmentsPage;
use App\Livewire\NewTransaction;
use App\Livewire\SettingsPage;
use App\Livewire\SyncPage;
use App\Livewire\Transactions;
use App\Models\Setting;
use App\Services\MercadoPago\KnownAccountsDictionary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Native\Mobile\Facades\Biometrics;

Route::get('/', Dashboard::class)->name('dashboard');
Route::get('/agenda', CalendarPage::class)->name('calendar');
Route::get('/calendario', CalendarPage::class);
Route::get('/nova-transacao', NewTransaction::class)->name('transactions.create');
Route::get('/orcamento', BudgetPage::class)->name('budget');
Route::get('/investimentos', InvestmentsPage::class)->name('investments');

Route::get('/lancamentos', Transactions::class)->name('transactions');
Route::get('/contas', Accounts::class)->name('accounts');
Route::get('/categorias', Categories::class)->name('categories');
Route::get('/importar', ImportCsv::class)->name('import');
Route::get('/sincronizar', SyncPage::class)->name('sync');
Route::get('/mercado-pago', Integrations::class)->name('integrations');
Route::get('/configuracoes', SettingsPage::class)->name('settings');

Route::post('/mercado-pago/dicionario', function (Request $request, KnownAccountsDictionary $dictionary) {
    $key = (string) $request->input('key', '');
    $label = (string) $request->input('label', '');

    if ($key === '') {
        return response()->json(['ok' => false, 'message' => 'Chave inválida.'], 422);
    }

    $updated = $dictionary->updateLabel($key, $label);

    return response()->json([
        'ok' => true,
        'key' => $key,
        'label' => trim($label),
        'updated_transactions' => $updated,
    ]);
})->name('integrations.dictionary.save');

Route::view('/pin', 'pin')->name('pin.show');
Route::post('/pin', function (Request $request) {
    if (Setting::verifyPin($request->input('pin'))) {
        $request->session()->put('pin_ok', true);

        return redirect()->intended(route('dashboard'));
    }

    return back()->withErrors(['pin' => 'PIN incorreto.']);
})->name('pin.verify');

Route::post('/pin/biometria', function () {
    abort_unless(filter_var(Setting::get('settings_biometrics_enabled', false), FILTER_VALIDATE_BOOLEAN), 403);

    try {
        $authenticated = Biometrics::prompt()->prompt();
    } catch (Throwable) {
        $authenticated = false;
    }

    if ($authenticated) {
        request()->session()->put('pin_ok', true);

        return redirect()->intended(route('dashboard'));
    }

    return back()->withErrors(['biometrics' => 'A autenticação biométrica não foi concluída. Você pode tentar novamente ou usar o PIN.']);
})->name('pin.biometric');

Route::post('/pin/bloquear', function (Request $request) {
    $request->session()->forget('pin_ok');

    return response()->noContent();
})->name('pin.lock');
