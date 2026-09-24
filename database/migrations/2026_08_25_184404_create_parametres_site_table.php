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
        Schema::create('parametres_site', function (Blueprint $table) {
            $table->id();

            $table->string('hero_badge');
            $table->string('hero_titre_ligne1');
            $table->string('hero_titre_ligne2');
            $table->text('hero_texte');

            $table->string('avantage_1_titre');
            $table->string('avantage_1_texte');
            $table->string('avantage_2_titre');
            $table->string('avantage_2_texte');
            $table->string('avantage_3_titre');
            $table->string('avantage_3_texte');
            $table->string('avantage_4_titre');
            $table->string('avantage_4_texte');

            $table->text('a_propos_texte');
            $table->string('siege_social');

            $table->string('feature_1_titre');
            $table->string('feature_1_texte');
            $table->string('feature_2_titre');
            $table->string('feature_2_texte');
            $table->string('feature_3_titre');
            $table->string('feature_3_texte');

            $table->string('contact_telephone_1');
            $table->string('contact_telephone_2')->nullable();
            $table->string('contact_email');

            $table->string('cta_titre');
            $table->text('cta_texte');

            $table->text('footer_texte');

            $table->timestamps();
        });

        // Ligne unique (id=1), pré-remplie avec le contenu déjà en place sur
        // la page d'accueil : l'admin part de ce texte pour l'ajuster, la
        // page ne dépend jamais d'un texte codé en dur dans le blade.
        DB::table('parametres_site')->insert([
            'id' => 1,
            'hero_badge' => "Mutuelle d'entraide agréée · Abidjan, Côte d'Ivoire",
            'hero_titre_ligne1' => 'Cotisez aujourd\'hui,',
            'hero_titre_ligne2' => 'protégez demain',
            'hero_texte' => 'Suivez vos cotisations, consultez votre solde en temps réel et soumettez vos demandes de prise en charge directement depuis votre espace personnel.',
            'avantage_1_titre' => 'Sécurité',
            'avantage_1_texte' => 'Un règlement clair, appliqué avec rigueur à chaque adhérent.',
            'avantage_2_titre' => 'Solidarité',
            'avantage_2_texte' => "L'épargne de chacun protège tous les membres de la mutuelle.",
            'avantage_3_titre' => 'Transparence',
            'avantage_3_texte' => 'Votre solde de cotisation est consultable à tout moment.',
            'avantage_4_titre' => 'Simplicité',
            'avantage_4_texte' => 'Adhésion, paiement et demandes, entièrement en ligne.',
            'a_propos_texte' => "La MIACI est une mutuelle d'entraide apolitique, fondée par et pour les instituteurs et toute personne se sentant concernée par sa vision. Elle met en commun l'épargne de ses membres pour la faire fructifier à leur bénéfice, et les accompagne dans les moments importants de leur vie comme dans leurs démarches administratives.",
            'siege_social' => 'Abidjan, Cocody Angré',
            'feature_1_titre' => 'Cotisations',
            'feature_1_texte' => 'Consultez votre historique de paiements et réglez votre cotisation en ligne (Mobile Money, Wave, carte bancaire).',
            'feature_2_titre' => 'Assistances',
            'feature_2_texte' => 'Soumettez vos demandes de prise en charge avec vos pièces justificatives et suivez leur instruction en temps réel.',
            'feature_3_titre' => 'Profil & famille',
            'feature_3_texte' => 'Gérez vos informations personnelles et déclarez vos personnes à charge (enfants, conjoint...).',
            'contact_telephone_1' => '07 08 15 08 30',
            'contact_telephone_2' => '01 03 38 01 10',
            'contact_email' => 'mutuellemiaci@hotmail.com',
            'cta_titre' => 'Prêt à rejoindre la mutuelle ?',
            'cta_texte' => "L'inscription se fait en quelques minutes. Votre dossier sera examiné par un gestionnaire avant validation.",
            'footer_texte' => "Mutuelle des Instituteurs et Assimilés de Côte d'Ivoire. Pour votre sécurité sociale.",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parametres_site');
    }
};
