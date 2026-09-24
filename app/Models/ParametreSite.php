<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Contenu texte de la page d'accueil publique, modifiable depuis le
 * back-office (Gestion > Paramètres > Site) sans toucher au code. Table à
 * ligne unique (id=1), pré-remplie à la migration.
 *
 * Volontairement absents de ce paramétrage : la liste "Nos objectifs" (qui
 * reprend l'Article 2 des statuts) et les statuts eux-mêmes — pour ne pas
 * risquer une dérive silencieuse par rapport au document légal signé.
 */
#[Fillable([
    'hero_badge', 'hero_titre_ligne1', 'hero_titre_ligne2', 'hero_texte',
    'avantage_1_titre', 'avantage_1_texte', 'avantage_2_titre', 'avantage_2_texte',
    'avantage_3_titre', 'avantage_3_texte', 'avantage_4_titre', 'avantage_4_texte',
    'a_propos_texte', 'siege_social',
    'feature_1_titre', 'feature_1_texte', 'feature_2_titre', 'feature_2_texte',
    'feature_3_titre', 'feature_3_texte',
    'contact_telephone_1', 'contact_telephone_2', 'contact_email',
    'cta_titre', 'cta_texte', 'footer_texte',
])]
class ParametreSite extends Model
{
    protected $table = 'parametres_site';

    public static function actuel(): self
    {
        return self::findOrFail(1);
    }
}
