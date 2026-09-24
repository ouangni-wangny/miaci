<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('adherents', function (Blueprint $table) {
            $table->boolean('droit_adhesion_exonere')->default(false)->after('statut');
        });

        // Les adhérents déjà présents avant l'instauration du suivi du droit
        // d'adhésion ne se voient pas réclamer cette somme rétroactivement.
        DB::table('adherents')->update(['droit_adhesion_exonere' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('adherents', function (Blueprint $table) {
            $table->dropColumn('droit_adhesion_exonere');
        });
    }
};
