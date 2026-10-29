<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('transactions', 'receipt_path')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('receipt_path')->nullable()->after('notes');
            });
        }

        if (! Schema::hasTable('investments')) {
            Schema::create('investments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('ticker', 24)->nullable();
                $table->string('asset_class', 24)->default('fixed_income'); // emergency, fixed_income, stocks_fii, crypto_global
                $table->string('subtitle')->nullable(); // ex: "IPCA + 6,24% a.a." ou "120 cotas • Cota R$ 164,80"
                $table->bigInteger('balance_cents')->default(0);
                $table->bigInteger('target_cents')->nullable(); // usado na Reserva de Emergência
                $table->decimal('daily_change_pct', 6, 2)->default(0);
                $table->string('icon', 40)->default('account_balance');
                $table->timestamps();
                $table->softDeletes();
                $table->index('updated_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('investments');
        if (Schema::hasColumn('transactions', 'receipt_path')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('receipt_path');
            });
        }
    }
};
