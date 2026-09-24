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
        Schema::table('parametres_cotisation', function (Blueprint $table) {
            $table->unsignedInteger('droit_adhesion')->nullable()->after('montant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parametres_cotisation', function (Blueprint $table) {
            $table->dropColumn('droit_adhesion');
        });
    }
};
