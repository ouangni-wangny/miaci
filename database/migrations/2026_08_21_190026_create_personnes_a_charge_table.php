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
        Schema::create('personnes_a_charge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adherent_id')->constrained('adherents')->cascadeOnDelete();
            $table->string('nom');
            $table->string('prenom');
            $table->date('date_naissance');
            $table->string('lien_parente');
            $table->enum('statut', ['active', 'inactive'])->default('active');
            $table->string('piece_justificative_path')->nullable();
            $table->boolean('valide_par_gestionnaire')->default(false);
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_le')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personnes_a_charge');
    }
};
