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
        Schema::table('personnes_a_charge', function (Blueprint $table) {
            $table->date('date_adhesion')->nullable()->after('lien_parente');
            $table->boolean('droit_adhesion_exonere')->default(false)->after('valide_le');
        });

        // Les personnes à charge déjà présentes ne se voient pas réclamer le
        // droit d'adhésion rétroactivement, et héritent d'une date d'adhésion
        // égale à leur date de déclaration.
        DB::table('personnes_a_charge')->update([
            'droit_adhesion_exonere' => true,
            'date_adhesion' => DB::raw('DATE(created_at)'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personnes_a_charge', function (Blueprint $table) {
            $table->dropColumn(['date_adhesion', 'droit_adhesion_exonere']);
        });
    }
};
