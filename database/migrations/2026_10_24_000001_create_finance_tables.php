<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabelas do domínio financeiro.
 *
 * Todas as tabelas sincronizáveis usam UUID, timestamps e soft delete para que
 * o PC (hub) e o celular (client) possam trocar alterações com "last-write-wins".
 * Valores monetários são guardados em centavos (inteiros, com sinal:
 * negativo = despesa / saída, positivo = receita / entrada).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('type', 20)->default('checking'); // checking, credit_card, cash, wallet, investment
            $table->bigInteger('initial_balance_cents')->default(0);
            $table->string('color', 9)->default('#0ea5e9');
            $table->unsignedTinyInteger('closing_day')->nullable(); // cartão: dia de fechamento
            $table->unsignedTinyInteger('due_day')->nullable();     // cartão: dia de vencimento
            $table->boolean('archived')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index('updated_at');
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('kind', 10)->default('expense'); // expense, income, transfer
            $table->string('color', 9)->default('#64748b');
            $table->string('icon', 16)->nullable();
            $table->uuid('parent_id')->nullable();
            $table->bigInteger('monthly_budget_cents')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('updated_at');
        });

        Schema::create('category_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('pattern'); // trecho da descrição (sem acento / case-insensitive)
            $table->uuid('category_id');
            $table->integer('priority')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index('updated_at');
        });

        Schema::create('bank_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('delimiter', 3)->default(',');
            $table->string('encoding', 20)->default('UTF-8');
            $table->string('date_format', 20)->default('Y-m-d');
            $table->string('decimal_separator', 1)->default(',');
            $table->json('column_map'); // {"date": "date", "description": "title", "amount": "amount"}
            $table->boolean('invert_sign')->default(false); // ex.: fatura de cartão (positivo = gasto)
            $table->unsignedSmallInteger('skip_rows')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index('updated_at');
        });

        Schema::create('import_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->uuid('bank_profile_id')->nullable();
            $table->string('source', 20)->default('csv'); // csv, mercadopago
            $table->string('filename')->nullable();
            $table->unsignedInteger('rows_imported')->default(0);
            $table->unsignedInteger('rows_skipped')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index('updated_at');
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->uuid('category_id')->nullable();
            $table->date('date');
            $table->string('description');
            $table->bigInteger('amount_cents'); // negativo = despesa
            $table->unsignedSmallInteger('installment')->nullable();
            $table->unsignedSmallInteger('installments')->nullable();
            $table->string('source', 20)->default('manual'); // manual, csv, mercadopago
            $table->string('external_id')->nullable();  // id na origem (ex.: pagamento do Mercado Pago)
            $table->string('hash', 40)->nullable();      // deduplicação de importações
            $table->uuid('import_batch_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'date']);
            $table->index('date');
            $table->index('hash');
            $table->index('external_id');
            $table->index('updated_at');
        });

        // Configurações locais (NÃO sincronizadas): URLs do hub, token, último sync etc.
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('import_batches');
        Schema::dropIfExists('bank_profiles');
        Schema::dropIfExists('category_rules');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('accounts');
    }
};
