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
        Schema::create('dons_fin_annee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adherent_id')->constrained('adherents')->cascadeOnDelete();
            $table->unsignedSmallInteger('annee');
            $table->unsignedInteger('montant');
            $table->date('date_versement');
            $table->foreignId('enregistre_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['adherent_id', 'annee']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dons_fin_annee');
    }
};
