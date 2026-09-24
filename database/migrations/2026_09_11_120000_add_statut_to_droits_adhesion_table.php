<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('droits_adhesion', function (Blueprint $table) {
            $table->enum('statut', ['valide', 'annule'])->default('valide')->after('reference');
        });
    }

    public function down(): void
    {
        Schema::table('droits_adhesion', function (Blueprint $table) {
            $table->dropColumn('statut');
        });
    }
};
