<?php

namespace App\Livewire;

use App\Models\Setting;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Native\Mobile\Facades\Biometrics;
use Throwable;

#[Layout('layouts.app')]
#[Title('Configurações')]
class SettingsPage extends Component
{
    public bool $showCents = true;

    public bool $privacyMode = false;

    public string $monthlyCycleStart = '1';

    public string $budgetMethod = '50-30-20';

    public string $accent = '#4edea3';

    public bool $biometricsEnabled = false;

    public bool $hasPin = false;

    public string $currentPinSource = 'none';

    public string $newPin = '';

    public string $newPinConfirmation = '';

    public bool $isEditingPin = false;

    public function mount(): void
    {
        $this->showCents = filter_var(Setting::get('settings_show_cents', true), FILTER_VALIDATE_BOOLEAN);
        $this->privacyMode = filter_var(Setting::get('settings_privacy_mode', false), FILTER_VALIDATE_BOOLEAN);
        $this->monthlyCycleStart = (string) Setting::get('settings_monthly_cycle_start', '1');
        $this->budgetMethod = (string) Setting::get('settings_budget_method', '50-30-20');
        $this->accent = (string) Setting::get('settings_accent', '#4edea3');
        $this->biometricsEnabled = filter_var(Setting::get('settings_biometrics_enabled', false), FILTER_VALIDATE_BOOLEAN);
        $this->refreshPinState();
    }

    public function refreshPinState(): void
    {
        $dbPin = Setting::get('settings_app_pin');
        $envPin = config('finance.pin');

        if (filled($dbPin)) {
            $this->hasPin = true;
            $this->currentPinSource = 'app';
        } elseif (filled($envPin)) {
            $this->hasPin = true;
            $this->currentPinSource = 'env';
        } else {
            $this->hasPin = false;
            $this->currentPinSource = 'none';
        }
    }

    public function updatedShowCents(): void
    {
        Setting::put('settings_show_cents', $this->showCents);
        $this->dispatch('settings-saved', message: 'Preferência de centavos salva.');
    }

    public function updatedPrivacyMode(): void
    {
        Setting::put('settings_privacy_mode', $this->privacyMode);
        $this->dispatch('settings-saved', message: 'Modo de privacidade salvo.');
    }

    public function updatedMonthlyCycleStart(): void
    {
        $this->validate(['monthlyCycleStart' => ['required', 'integer', 'between:1,28']]);
        Setting::put('settings_monthly_cycle_start', $this->monthlyCycleStart);
        $this->dispatch('settings-saved', message: 'Início do ciclo mensal salvo.');
    }

    public function updatedBudgetMethod(): void
    {
        $this->validate(['budgetMethod' => ['required', Rule::in(['50-30-20', '60-20-20', 'zero-based'])]]);
        Setting::put('settings_budget_method', $this->budgetMethod);
        $this->dispatch('settings-saved', message: 'Metodologia orçamentária salva.');
    }

    public function chooseAccent(string $accent): void
    {
        $this->accent = $accent;
        $this->validate(['accent' => ['required', Rule::in(['#4edea3', '#3b82f6', '#8b5cf6', '#f59e0b'])]]);
        Setting::put('settings_accent', $this->accent);
        $this->dispatch('settings-saved', message: 'Cor de destaque salva.', accent: $this->accent);
    }

    public function enableBiometrics(): void
    {
        if (! Setting::hasPin()) {
            $this->addError('biometrics', 'Defina um PIN de acesso abaixo antes de ativar a biometria para manter uma alternativa de acesso.');

            return;
        }

        try {
            $authenticated = Biometrics::prompt()->prompt();
        } catch (Throwable) {
            $authenticated = false;
        }

        if (! $authenticated) {
            $this->addError('biometrics', 'Não foi possível confirmar a biometria. Verifique se o aparelho tem biometria cadastrada e tente novamente.');

            return;
        }

        $this->biometricsEnabled = true;
        Setting::put('settings_biometrics_enabled', true);
        session()->put('pin_ok', true);
        $this->dispatch('biometrics-enabled');
        $this->dispatch('settings-saved', message: 'Acesso biométrico ativado.');
    }

    public function disableBiometrics(): void
    {
        $this->biometricsEnabled = false;
        Setting::put('settings_biometrics_enabled', false);
        $this->dispatch('settings-saved', message: 'Acesso biométrico desativado.');
    }

    public function toggleEditPin(): void
    {
        $this->isEditingPin = ! $this->isEditingPin;
        $this->newPin = '';
        $this->newPinConfirmation = '';
        $this->resetErrorBag(['newPin', 'newPinConfirmation']);
    }

    public function savePin(): void
    {
        $this->validate([
            'newPin' => ['required', 'numeric', 'digits_between:4,8'],
            'newPinConfirmation' => ['required', 'same:newPin'],
        ], [
            'newPin.required' => 'Informe o PIN.',
            'newPin.numeric' => 'O PIN deve conter apenas números.',
            'newPin.digits_between' => 'O PIN deve ter entre 4 e 8 dígitos.',
            'newPinConfirmation.required' => 'Confirme o PIN.',
            'newPinConfirmation.same' => 'A confirmação do PIN não confere.',
        ]);

        Setting::setPin($this->newPin);
        $this->newPin = '';
        $this->newPinConfirmation = '';
        $this->isEditingPin = false;
        $this->resetErrorBag(['newPin', 'newPinConfirmation', 'biometrics']);
        $this->refreshPinState();
        $this->dispatch('settings-saved', message: 'PIN de acesso salvo com sucesso.');
    }

    public function removePin(): void
    {
        if ($this->biometricsEnabled) {
            $this->addError('biometrics', 'Desative a biometria antes de remover o PIN de acesso.');

            return;
        }

        Setting::setPin(null);
        $this->newPin = '';
        $this->newPinConfirmation = '';
        $this->isEditingPin = false;
        $this->refreshPinState();
        $this->dispatch('settings-saved', message: 'PIN de acesso removido.');
    }

    public function render()
    {
        return view('livewire.settings-page');
    }
}
