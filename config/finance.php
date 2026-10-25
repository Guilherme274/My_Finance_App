<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Papel desta instalação
    |--------------------------------------------------------------------------
    |
    | "hub"    => PC: expõe a API de sincronização e integra com o Mercado Pago.
    | "client" => Celular (APK): funciona offline e sincroniza com o hub.
    |
    | No APK (NativePHP) o papel é sempre "client".
    |
    */

    'role' => env('NATIVEPHP_RUNNING') ? 'client' : env('APP_ROLE', 'hub'),

    /*
    | PIN opcional para abrir o sistema (recomendado no PC, pois ele fica
    | acessível na rede Wi-Fi / Tailscale). Deixe vazio para desativar.
    */
    'pin' => env('APP_PIN'),

    'sync' => [
        // Token compartilhado entre PC e celular (gerado com `php artisan finance:sync-token`).
        'token' => env('SYNC_TOKEN'),

        // Porta em que o PC escuta (usada pelo iniciar.bat e exibida na tela de pareamento).
        'port' => (int) env('SYNC_PORT', 8000),

        'timeout' => (int) env('SYNC_TIMEOUT', 8),
    ],

    'mercadopago' => [
        'base_url' => env('MERCADOPAGO_BASE_URL', 'https://api.mercadopago.com'),
        'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
        // Conta do sistema onde os lançamentos do Mercado Pago serão gravados.
        'account_name' => env('MERCADOPAGO_ACCOUNT_NAME', 'Mercado Pago'),
        'timeout' => (int) env('MERCADOPAGO_TIMEOUT', 20),
    ],

];
