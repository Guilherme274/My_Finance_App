<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PIN · Gestão Financeira</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex h-full items-center justify-center bg-slate-900 p-4 text-slate-100">
    <div class="w-full max-w-xs space-y-4 rounded-2xl bg-slate-800 p-6 shadow-xl">
        <h1 class="text-center text-lg font-bold">🔒 Gestão Financeira</h1>
        @if (filter_var(\App\Models\Setting::get('settings_biometrics_enabled', false), FILTER_VALIDATE_BOOLEAN))
            <form method="POST" action="{{ route('pin.biometric') }}" class="space-y-3">
                @csrf
                @error('biometrics') <p class="error text-center">{{ $message }}</p> @enderror
                <button class="btn-primary w-full" type="submit">Desbloquear com biometria</button>
            </form>
        @endif
        @if (filled(config('finance.pin')))
            <form method="POST" action="{{ route('pin.verify') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="label">Digite o PIN</label>
                    <input type="password" name="pin" inputmode="numeric" autofocus class="input text-center text-xl tracking-widest">
                    @error('pin') <p class="error text-center">{{ $message }}</p> @enderror
                </div>
                <button class="btn-primary w-full">Entrar com PIN</button>
            </form>
        @endif
    </div>
</body>
</html>

