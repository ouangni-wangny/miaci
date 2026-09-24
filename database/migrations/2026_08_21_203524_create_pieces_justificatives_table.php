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
        Schema::create('pieces_justificatives', function (Blueprint $table) {
            $table->id();
            $table->morphs('justificable');
            $table->foreignId('piece_requise_type_sinistre_id')->nullable()
                ->constrained('pieces_requises_type_sinistre')->nullOnDelete();
            $table->string('fichier_path');
            $table->string('nom_original');
            $table->string('type_mime');
            $table->unsignedInteger('taille');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pieces_justificatives');
    }
};
