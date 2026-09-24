<?php

namespace App\Enums;

enum Permission: string
{
    case GererAdherents = 'gerer_adherents';
    case GererCotisations = 'gerer_cotisations';
    case GererSinistres = 'gerer_sinistres';
    case ConfigurerParametres = 'configurer_parametres';
    case VoirJournalAudit = 'voir_journal_audit';
    case ExporterDonnees = 'exporter_donnees';

    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
