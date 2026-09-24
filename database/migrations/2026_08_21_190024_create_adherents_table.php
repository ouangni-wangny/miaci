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
        Schema::create('adherents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('matricule')->unique();
            $table->string('nom');
            $table->string('prenom');
            $table->enum('sexe', ['M', 'F']);
            $table->date('date_naissance');
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->string('etablissement')->nullable();
            $table->string('fonction')->nullable();
            $table->date('date_adhesion');
            $table->enum('statut', ['en_attente', 'actif', 'suspendu', 'radie'])->default('actif');
            $table->string('photo_path')->nullable();
            $table->timestamps();

            $table->index('statut');
            $table->index(['nom', 'prenom']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adherents');
    }
};
