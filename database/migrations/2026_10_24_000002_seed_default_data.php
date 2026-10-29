<?php

use Database\Seeders\DefaultDataSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Cria os dados padrão automaticamente (no PC e no celular, que roda as
 * migrations ao abrir o app). IDs determinísticos evitam duplicação no sync.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new DefaultDataSeeder)->run();
    }

    public function down(): void
    {
        //
    }
};
