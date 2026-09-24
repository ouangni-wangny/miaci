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
        Schema::create('transactions_paiement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adherent_id')->constrained('adherents')->cascadeOnDelete();
            $table->foreignId('cotisation_id')->nullable()->constrained('cotisations')->nullOnDelete();
            $table->unsignedInteger('montant');
            $table->enum('fournisseur', ['cinetpay']);
            $table->string('reference_fournisseur')->nullable();
            $table->string('reference_interne')->unique();
            $table->enum('statut', ['en_attente', 'reussi', 'echoue', 'rembourse'])->default('en_attente');
            $table->json('payload_retour')->nullable();
            $table->timestamp('initie_le')->nullable();
            $table->timestamp('complete_le')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions_paiement');
    }
};
