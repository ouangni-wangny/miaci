<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions_paiement', function (Blueprint $table) {
            $table->string('type')->default('cotisation')->after('adherent_id');
            $table->nullableMorphs('payable', 'transactions_paiement_payable_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions_paiement', function (Blueprint $table) {
            $table->dropMorphs('payable', 'transactions_paiement_payable_index');
            $table->dropColumn('type');
        });
    }
};
