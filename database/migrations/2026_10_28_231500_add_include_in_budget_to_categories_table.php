<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('include_in_budget')->default(true)->after('monthly_budget_percent');
        });

        // Categorias de transferência ou faturas por padrão não entram no orçamento/gráficos de despesas
        DB::table('categories')
            ->where('kind', 'transfer')
            ->orWhere('name', 'like', '%transfer%')
            ->orWhere('name', 'like', '%fatura%')
            ->update(['include_in_budget' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('include_in_budget');
        });
    }
};
