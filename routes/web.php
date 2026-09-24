<?php

use App\Http\Controllers\CarteMembreController;
use App\Http\Controllers\CotisationReceiptController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PieceJustificativeController;
use App\Http\Controllers\SoldeCarteController;
use App\Http\Controllers\StatutsController;
use App\Livewire\Dashboard;
use App\Livewire\Gestion\Bilan\Index as BilanIndex;
use App\Livewire\Gestion\Adherents\Fiche as AdherentFiche;
use App\Livewire\Gestion\Audit\Index as AuditIndex;
use App\Livewire\Gestion\Adherents\Formulaire as AdherentFormulaire;
use App\Livewire\Gestion\Adherents\Import as AdherentImport;
use App\Livewire\Gestion\Adherents\Index as AdherentIndex;
use App\Livewire\Gestion\Cotisations\Index as CotisationIndex;
use App\Livewire\Gestion\DonsFinAnnee\Index as DonsFinAnneeIndex;
use App\Livewire\Gestion\Paiements\Index as PaiementIndex;
use App\Livewire\Gestion\Parametres\Cotisation as ParametreCotisationPage;
use App\Livewire\Gestion\Parametres\Site as SitePage;
use App\Livewire\Gestion\Parametres\TypesSinistre\Formulaire as TypeSinistreFormulaire;
use App\Livewire\Gestion\Parametres\TypesSinistre\Index as TypeSinistreIndex;
use App\Livewire\Gestion\Sinistres\Fiche as GestionSinistreFiche;
use App\Livewire\Gestion\Sinistres\Index as GestionSinistreIndex;
use App\Livewire\MonEspace\Cotisations as MonEspaceCotisations;
use App\Livewire\MonEspace\PersonnesACharge as MonEspacePersonnesACharge;
use App\Livewire\MonEspace\Profil as MonEspaceProfil;
use App\Livewire\MonEspace\Sinistres\Fiche as MonEspaceSinistreFiche;
use App\Livewire\MonEspace\Sinistres\Index as MonEspaceSinistreIndex;
use App\Livewire\MonEspace\Sinistres\Soumettre as MonEspaceSinistreSoumettre;
use App\Models\BanniereSite;
use App\Models\ParametreSite;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome', [
        'site' => ParametreSite::actuel(),
        'bannieres' => BanniereSite::actives()->get(),
    ]);
})->name('accueil');

Route::get('/statuts', StatutsController::class)->name('statuts');

// Page publique du solde de cotisation, accessible sans connexion
// uniquement via le lien signé encodé dans le QR code de la carte
// de membre (voir CarteMembreController).
Route::get('/carte/{adherent}/solde', SoldeCarteController::class)
    ->middleware('signed')
    ->name('carte.solde');

Route::get('dashboard', Dashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Accessible à tout utilisateur authentifié : l'autorisation fine (propriétaire
// ou gestionnaire) est vérifiée par la policy correspondante dans le contrôleur.
Route::get('/cotisations/{cotisation}/recu', CotisationReceiptController::class)
    ->middleware('auth')
    ->name('cotisations.recu');

Route::get('/pieces-justificatives/{piece}', PieceJustificativeController::class)
    ->middleware('auth')
    ->name('pieces-justificatives.telecharger');

Route::get('/adherents/{adherent}/carte', CarteMembreController::class)
    ->middleware('auth')
    ->name('adherents.carte');

Route::post('/paiement/initier', [PaymentController::class, 'initier'])
    ->middleware(['auth', 'role:ADHERENT'])
    ->name('paiement.initier');

Route::post('/paiement/initier-droit-adhesion', [PaymentController::class, 'initierDroitAdhesion'])
    ->middleware(['auth', 'role:ADHERENT'])
    ->name('paiement.initier-droit-adhesion');

Route::post('/paiement/webhook/cinetpay', [PaymentController::class, 'webhook'])
    ->name('paiement.webhook.cinetpay');

Route::get('/paiement/retour', [PaymentController::class, 'retour'])
    ->middleware('auth')
    ->name('paiement.retour');

Route::middleware(['auth', 'role:ADMIN|GESTIONNAIRE'])
    ->prefix('gestion')
    ->name('gestion.')
    ->group(function () {
        Route::prefix('adherents')->name('adherents.')->group(function () {
            Route::get('/', AdherentIndex::class)->name('index');
            Route::get('/importer', AdherentImport::class)->name('importer');
            Route::get('/importer/modele', function () {
                $entetes = ['matricule', 'nom', 'prenom', 'sexe', 'date_naissance', 'telephone', 'email', 'etablissement', 'fonction', 'date_adhesion'];
                $exemple = ['MIACI-00001', 'Kouassi', 'Jean', 'M', '1985-03-12', '0708091011', 'jean.kouassi@example.ci', 'EPP Abobo 1', 'Instituteur adjoint', '2018-09-01'];

                $contenu = implode(',', $entetes)."\n".implode(',', $exemple)."\n";

                return response($contenu, 200, [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="modele-import-adherents.csv"',
                ]);
            })->name('modele');
            Route::get('/creer', AdherentFormulaire::class)->name('creer');
            Route::get('/{adherent}', AdherentFiche::class)->name('fiche');
            Route::get('/{adherent}/modifier', AdherentFormulaire::class)->name('modifier');
        });

        Route::get('/cotisations', CotisationIndex::class)->name('cotisations.index');
        Route::get('/paiements', PaiementIndex::class)->name('paiements.index');
        Route::get('/dons-fin-annee', DonsFinAnneeIndex::class)->name('dons-fin-annee.index');
        Route::get('/bilan', BilanIndex::class)->name('bilan.index');

        Route::prefix('sinistres')->name('sinistres.')->group(function () {
            Route::get('/', GestionSinistreIndex::class)->name('index');
            Route::get('/{demande}', GestionSinistreFiche::class)->name('fiche');
        });

        Route::prefix('exports')->name('exports.')->group(function () {
            Route::get('/adherents', [ExportController::class, 'adherents'])->name('adherents');
            Route::get('/cotisations', [ExportController::class, 'cotisations'])->name('cotisations');
            Route::get('/sinistres', [ExportController::class, 'sinistres'])->name('sinistres');
            Route::get('/membres', [ExportController::class, 'membres'])->name('membres');
            Route::get('/dons-fin-annee', [ExportController::class, 'donsFinAnnee'])->name('dons-fin-annee');
        });

        Route::middleware('role:ADMIN')->prefix('parametres')->name('parametres.')->group(function () {
            Route::get('/cotisation', ParametreCotisationPage::class)->name('cotisation');
            Route::get('/site', SitePage::class)->name('site');

            Route::prefix('types-sinistre')->name('types-sinistre.')->group(function () {
                Route::get('/', TypeSinistreIndex::class)->name('index');
                Route::get('/creer', TypeSinistreFormulaire::class)->name('creer');
                Route::get('/{typeSinistre}/modifier', TypeSinistreFormulaire::class)->name('modifier');
            });
        });

        Route::middleware('role:ADMIN')->get('/audit', AuditIndex::class)->name('audit.index');
    });

Route::middleware(['auth', 'role:ADHERENT'])
    ->prefix('mon-espace')
    ->name('mon-espace.')
    ->group(function () {
        Route::get('/profil', MonEspaceProfil::class)->name('profil');
        Route::get('/personnes-a-charge', MonEspacePersonnesACharge::class)->name('personnes-a-charge');
        Route::get('/cotisations', MonEspaceCotisations::class)->name('cotisations');

        Route::prefix('sinistres')->name('sinistres.')->group(function () {
            Route::get('/', MonEspaceSinistreIndex::class)->name('index');
            Route::get('/soumettre', MonEspaceSinistreSoumettre::class)->name('soumettre');
            Route::get('/{demande}', MonEspaceSinistreFiche::class)->name('fiche');
        });
    });

require __DIR__.'/auth.php';
