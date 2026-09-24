<?php

namespace App\Console\Commands;

use App\Models\Adherent;
use App\Models\PersonneACharge;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;

/**
 * Génère le briefing de conformité (PDF) : bilan complet, article par
 * article des statuts, de ce qui est fait et de ce qui ne l'est pas dans
 * l'application — à partir des chiffres réels de la base au moment de la
 * génération.
 */
class GenererBriefingConformite extends Command
{
    protected $signature = 'demo:briefing {--sortie= : Chemin du PDF de sortie}';

    protected $description = 'Génère le briefing de conformité (PDF) : ce qui est fait / non fait par rapport aux statuts';

    public function handle(): int
    {
        $donnees = [
            'dateGeneration' => now(),
            'totalAdherents' => Adherent::count(),
            'totalPac' => PersonneACharge::count(),
            'totalVilles' => Adherent::whereNotNull('ville')->where('ville', '!=', '')->distinct('ville')->count('ville'),
        ];

        $pdf = Pdf::loadView('pdf.briefing-conformite', $donnees)->setPaper('a4', 'portrait');

        $chemin = $this->option('sortie') ?: storage_path('app/private/exports/briefing-conformite-miaci.pdf');

        if (! is_dir(dirname($chemin))) {
            mkdir(dirname($chemin), 0755, true);
        }

        file_put_contents($chemin, $pdf->output());

        $this->info("Briefing généré : {$chemin}");

        return self::SUCCESS;
    }
}
