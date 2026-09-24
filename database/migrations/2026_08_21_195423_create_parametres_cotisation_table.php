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
        Schema::create('parametres_cotisation', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('montant');
            $table->enum('frequence', ['mensuelle', 'trimestrielle', 'annuelle']);
            $table->date('date_debut');
            $table->boolean('actif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parametres_cotisation');
    }
};
