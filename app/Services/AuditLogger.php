<?php

namespace App\Services;

use App\Models\JournalAudit;
use Illuminate\Database\Eloquent\Model;

/**
 * Journalise les actions sensibles de l'application (décisions sur les
 * sinistres, modifications de cotisation, changements de statut adhérent...)
 * pour permettre de retracer qui a fait quoi et quand.
 */
class AuditLogger
{
    public function log(
        string $action,
        Model $entite,
        ?array $donneesAvant = null,
        ?array $donneesApres = null,
        ?string $description = null,
    ): JournalAudit {
        return JournalAudit::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'entite_type' => $entite::class,
            'entite_id' => $entite->getKey(),
            'description' => $description,
            'donnees_avant' => $donneesAvant,
            'donnees_apres' => $donneesApres,
            'ip_address' => request()?->ip(),
        ]);
    }
}
