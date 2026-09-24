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
        Schema::create('pieces_requises_type_sinistre', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_sinistre_id')->constrained('types_sinistre')->cascadeOnDelete();
            $table->string('libelle');
            $table->boolean('obligatoire')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pieces_requises_type_sinistre');
    }
};
