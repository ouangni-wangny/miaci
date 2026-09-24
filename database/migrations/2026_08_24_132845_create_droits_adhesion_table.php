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
        Schema::create('droits_adhesion', function (Blueprint $table) {
            $table->id();
            $table->morphs('payable'); // Adherent ou PersonneACharge
            $table->unsignedInteger('montant');
            $table->date('date_paiement');
            $table->enum('mode_paiement', ['especes', 'cheque', 'virement', 'mobile_money', 'carte', 'autre']);
            $table->string('reference')->nullable();
            $table->foreignId('enregistre_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('droits_adhesion');
    }
};
