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
        Schema::create('cotisations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adherent_id')->constrained('adherents')->cascadeOnDelete();
            $table->unsignedInteger('montant');
            $table->date('date_paiement');
            $table->date('periode_debut');
            $table->date('periode_fin');
            $table->enum('mode_paiement', ['especes', 'cheque', 'virement', 'mobile_money', 'carte', 'autre']);
            $table->string('reference')->nullable();
            $table->foreignId('enregistre_par')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('statut', ['valide', 'annule'])->default('valide');
            $table->timestamps();

            $table->index(['adherent_id', 'date_paiement']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotisations');
    }
};
