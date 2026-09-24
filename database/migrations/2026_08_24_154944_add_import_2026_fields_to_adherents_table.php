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
            $table->date('date_fin_carence_indicative')->nullable()->after('date_adhesion');
            $table->boolean('nee_apres_1956')->nullable()->after('date_naissance');
        });

        Schema::table('adherents', function (Blueprint $table) {
            $table->enum('sexe', ['M', 'F'])->nullable()->change();
            $table->date('date_naissance')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('adherents', function (Blueprint $table) {
            $table->dropColumn(['date_fin_carence_indicative', 'nee_apres_1956']);
        });
    }
};
