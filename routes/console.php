<?php

use App\Services\MercadoPago\MercadoPagoClient;
use App\Services\MercadoPago\MercadoPagoImporter;
use App\Services\Pluggy\PluggyClient;
use App\Services\Pluggy\PluggyImporter;
use App\Services\Sync\SyncSettings;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Gestão Financeira
|--------------------------------------------------------------------------
*/

Artisan::command('finance:pairing', function (SyncSettings $settings) {
    $this->info('Dados para parear o celular com este PC:');
    $this->line('Token: '.$settings->hubToken());
    foreach ($settings->localAddresses() as $address) {
        $this->line(sprintf('%-20s http://%s:%d', $address['type'], $address['ip'], config('finance.sync.port')));
    }
})->purpose('Mostra token e endereços para parear o celular');

Artisan::command('mercadopago:test', function (MercadoPagoClient $client) {
    $me = $client->me();
    $this->info('Conexão OK com o Mercado Pago.');
    $this->table(['id', 'nickname', 'email', 'país'], [[
        $me['id'] ?? '-', $me['nickname'] ?? '-', $me['email'] ?? '-', $me['site_id'] ?? '-',
    ]]);
})->purpose('Testa o Access Token do Mercado Pago');

Artisan::command('mercadopago:import {--days=365 : Quantidade de dias para trás}', function (MercadoPagoImporter $importer) {
    $batch = $importer->import((int) $this->option('days'));
    $this->info("Importados: {$batch->rows_imported} | Já existentes: {$batch->rows_skipped}");
})->purpose('Importa pagamentos aprovados do Mercado Pago');

Artisan::command('pluggy:test', function (PluggyClient $client) {
    $apiKey = $client->apiKey(true);
    $connectToken = $client->createConnectToken(null, 'gestao-financeira');
    $this->info('Conexão OK com a API da Pluggy Open Finance!');
    $this->line('API Key (2h): '.substr($apiKey, 0, 28).'...');
    $this->line('Connect Token (30m): '.substr($connectToken, 0, 28).'...');
})->purpose('Testa as credenciais Client ID / Client Secret da Pluggy');

Artisan::command('pluggy:sync {--days=90 : Quantidade de dias para trás}', function (PluggyImporter $importer) {
    $items = $importer->connectedItems();
    if (! $items) {
        $this->warn('Nenhuma conta Open Finance vinculada ainda. Acesse http://localhost:8000/mercado-pago para conectar o Nubank.');

        return;
    }

    foreach ($items as $item) {
        $stats = $importer->syncItem($item['id'], (int) $this->option('days'));
        $this->info("[{$item['connector_name']}] Contas: {$stats['accounts_synced']} | Novas transações: {$stats['imported']} | Ignoradas: {$stats['skipped']} | Investimentos: {$stats['investments_synced']}");
    }
})->purpose('Sincroniza todas as contas conectadas via Pluggy Open Finance');

Artisan::command('finance:apk', function (SyncSettings $settings) {
    $basePath = base_path();
    $javaHome = 'C:\\Users\\guilh\\AppData\\Local\\Programs\\Eclipse Adoptium\\jdk-17.0.20.101-hotspot';
    $androidHome = getenv('LOCALAPPDATA') ? getenv('LOCALAPPDATA').'\\Android\\Sdk' : 'C:\\Users\\guilh\\AppData\\Local\\Android\\Sdk';

    $this->info('1/4 Compilando CSS e assets (Vite)...');
    Process::path($basePath)->forever()->run('npm.cmd run build', function (string $type, string $output) {
        $this->output->write($output);
    });

    // Remove APKs antigos antes de empacotar para não duplicar tamanho no bundle
    @unlink($basePath.DIRECTORY_SEPARATOR.'gestao-financeira.apk');
    @unlink($basePath.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'gestao-financeira.apk');

    $this->info('2/4 Empacotando código Laravel + SQLite e compilando APK Android...');
    $env = [
        'JAVA_HOME' => $javaHome,
        'ANDROID_HOME' => $androidHome,
        'PATH' => $javaHome.'\\bin;'.$androidHome.'\\platform-tools;'.getenv('PATH'),
    ];

    Process::path($basePath)
        ->env($env)
        ->forever()
        ->run([PHP_BINARY, 'artisan', 'native:run', 'android', '--build=release', '--no-interaction', '--no-tty'], function (string $type, string $output) {
            $this->output->write($output);
        });

    $builtApk = $basePath.'\\nativephp\\android\\app\\build\\outputs\\apk\\release\\app-release.apk';
    if (! is_file($builtApk)) {
        $this->error('Não foi possível localizar o APK gerado em: '.$builtApk);

        return self::FAILURE;
    }

    $rootApk = $basePath.'\\gestao-financeira.apk';
    $publicApk = $basePath.'\\public\\gestao-financeira.apk';

    copy($builtApk, $rootApk);
    copy($builtApk, $publicApk);

    $sizeMb = number_format(filesize($rootApk) / 1048576, 2, ',', '.');
    $this->newLine();
    $this->info("3/4 APK atualizado com sucesso ({$sizeMb} MB):");
    $this->line("  - {$rootApk}");
    $this->line("  - {$publicApk}");

    // Tenta instalar direto via ADB se o celular estiver conectado com Depuração USB
    $adbExe = $androidHome.'\\platform-tools\\adb.exe';
    if (is_file($adbExe)) {
        $this->info('4/4 Verificando celular conectado via USB (ADB)...');
        $install = Process::run([$adbExe, 'install', '-r', $rootApk]);
        if ($install->successful() && str_contains($install->output(), 'Success')) {
            $this->info('📱 APK instalado/atualizado automaticamente no seu celular via USB!');

            return self::SUCCESS;
        }
    }

    $this->newLine();
    $this->comment('Para baixar e atualizar no celular pelo Wi-Fi, acesse no navegador do celular:');
    foreach ($settings->localAddresses() as $address) {
        $this->line(sprintf('  http://%s:%d/gestao-financeira.apk', $address['ip'], config('finance.sync.port')));
    }

    return self::SUCCESS;
})->purpose('Compila os assets e atualiza o APK Android (gestao-financeira.apk)');
