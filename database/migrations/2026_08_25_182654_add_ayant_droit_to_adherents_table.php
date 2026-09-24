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
        Schema::table('adherents', function (Blueprint $table) {
            $table->string('ayant_droit_nom')->nullable()->after('statut');
            $table->string('ayant_droit_telephone', 30)->nullable()->after('ayant_droit_nom');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('adherents', function (Blueprint $table) {
            $table->dropColumn(['ayant_droit_nom', 'ayant_droit_telephone']);
        });
    }
};
