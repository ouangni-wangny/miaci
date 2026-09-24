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
        Schema::create('demandes_sinistre', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adherent_id')->constrained('adherents')->cascadeOnDelete();
            $table->foreignId('type_sinistre_id')->constrained('types_sinistre');
            $table->enum('beneficiaire_type', ['adherent', 'personne_a_charge']);
            $table->foreignId('personne_a_charge_id')->nullable()->constrained('personnes_a_charge')->nullOnDelete();
            $table->text('description');
            $table->unsignedInteger('montant_demande');
            $table->unsignedInteger('montant_accorde')->nullable();
            $table->date('date_evenement');
            $table->enum('statut', ['soumise', 'en_cours_examen', 'approuvee', 'rejetee', 'complement_demande'])
                ->default('soumise');
            $table->enum('preresultat_eligibilite', ['probablement_eligible', 'probablement_non_eligible'])->nullable();
            $table->text('motif_preresultat')->nullable();
            $table->text('motif_decision')->nullable();
            $table->foreignId('traite_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('traite_le')->nullable();
            $table->timestamps();

            $table->index(['adherent_id', 'statut']);
            $table->index(['type_sinistre_id', 'statut']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demandes_sinistre');
    }
};
