<?php

namespace App\Http\Controllers;

use App\Exports\AdherentsExport;
use App\Exports\CotisationsExport;
use App\Exports\DemandesSinistreExport;
use App\Exports\DonsFinAnneeExport;
use App\Exports\MembresExport;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function adherents(): BinaryFileResponse
    {
        Gate::authorize('exporter_donnees');

        return Excel::download(new AdherentsExport, 'adherents-'.now()->format('Y-m-d').'.xlsx');
    }

    public function cotisations(): BinaryFileResponse
    {
        Gate::authorize('exporter_donnees');

        return Excel::download(new CotisationsExport, 'cotisations-'.now()->format('Y-m-d').'.xlsx');
    }

    public function sinistres(): BinaryFileResponse
    {
        Gate::authorize('exporter_donnees');

        return Excel::download(new DemandesSinistreExport, 'sinistres-'.now()->format('Y-m-d').'.xlsx');
    }

    /**
     * Liste exhaustive de tous les membres de la mutuelle — adhérents et
     * personnes à charge — avec la tutelle de chaque personne à charge
     * clairement rattachée à son adhérent.
     */
    public function membres(): BinaryFileResponse
    {
        Gate::authorize('exporter_donnees');

        return Excel::download(new MembresExport, 'liste-membres-miaci-'.now()->format('Y-m-d').'.xlsx');
    }

    /**
     * Liste des adhérents bénéficiaires du don de fin d'année (Article 7),
     * avec l'année considérée et le statut de versement de chacun.
     */
    public function donsFinAnnee(): BinaryFileResponse
    {
        Gate::authorize('exporter_donnees');

        $annee = (int) (request('annee') ?: now()->year);

        return Excel::download(new DonsFinAnneeExport($annee), 'dons-fin-annee-'.$annee.'.xlsx');
    }
}
