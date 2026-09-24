<?php

namespace App\Console\Commands;

use App\Models\Adherent;
use App\Services\MatriculeGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Renumérote tous les matricules existants selon la nouvelle nomenclature
 * {année d'adhésion}-MIACI-{jour}{mois}A[rang] (voir MatriculeGenerator),
 * afin que l'ancienneté d'un adhérent soit lisible directement dans son
 * matricule. Opération ponctuelle, à lancer une seule fois pour migrer
 * les matricules déjà attribués (les nouveaux adhérents reçoivent déjà
 * cette nomenclature via Formulaire/Inscription).
 */
class RenumeroterMatricules extends Command
{
    protected $signature = 'adherents:renumeroter
        {--dry-run : Affiche la correspondance ancien/nouveau matricule sans rien modifier}
        {--export= : Chemin du fichier CSV de correspondance ancien/nouveau matricule}';

    protected $description = 'Renumérote les matricules existants selon la nomenclature {année}-MIACI-{jour}{mois}A[rang]';

    public function handle(MatriculeGenerator $generator): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $adherents = Adherent::orderBy('date_adhesion')->orderBy('id')->get();

        $correspondances = [];
        $rangParDate = [];

        foreach ($adherents as $adherent) {
            $cle = $adherent->date_adhesion->format('Y-m-d');
            $rangParDate[$cle] = ($rangParDate[$cle] ?? 0) + 1;

            $nouveauMatricule = $generator->construire($adherent->date_adhesion, $rangParDate[$cle]);

            $correspondances[] = [
                'id' => $adherent->id,
                'ancien' => $adherent->matricule,
                'nouveau' => $nouveauMatricule,
                'nom' => $adherent->nom,
                'prenom' => $adherent->prenom,
            ];
        }

        $doublons = collect($correspondances)->duplicates('nouveau');
        if ($doublons->isNotEmpty()) {
            $this->error('Collision détectée dans les nouveaux matricules générés, opération annulée : '.$doublons->implode(', '));

            return self::FAILURE;
        }

        $this->table(
            ['Ancien matricule', 'Nouveau matricule', 'Nom', 'Prénom'],
            collect($correspondances)->map(fn ($c) => [$c['ancien'], $c['nouveau'], $c['nom'], $c['prenom']])->all()
        );

        if ($dryRun) {
            $this->info('Dry-run : '.count($correspondances)." matricules seraient renumérotés. Rien n'a été modifié.");

            return self::SUCCESS;
        }

        DB::transaction(function () use ($correspondances) {
            foreach ($correspondances as $c) {
                Adherent::whereKey($c['id'])->update(['matricule' => $c['nouveau']]);
            }
        });

        $this->info(count($correspondances).' matricules renumérotés.');

        $cheminExport = $this->option('export') ?: storage_path('app/private/imports/correspondance-matricules-'.now()->format('Y-m-d-His').'.csv');

        $fichier = fopen($cheminExport, 'w');
        fputcsv($fichier, ['ancien_matricule', 'nouveau_matricule', 'nom', 'prenom']);
        foreach ($correspondances as $c) {
            fputcsv($fichier, [$c['ancien'], $c['nouveau'], $c['nom'], $c['prenom']]);
        }
        fclose($fichier);

        $this->info("Correspondance ancien/nouveau exportée : {$cheminExport}");

        return self::SUCCESS;
    }
}
