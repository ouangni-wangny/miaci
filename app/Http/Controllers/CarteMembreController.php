<?php

namespace App\Http\Controllers;

use App\Models\Adherent;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Génère la carte de membre au format PVC standard (CR80, 85,6 x 53,98 mm),
 * recto et verso, prête à imprimer.
 */
class CarteMembreController extends Controller
{
    private const LARGEUR_MM = 85.6;

    private const HAUTEUR_MM = 53.98;

    public function __invoke(Adherent $adherent): Response
    {
        Gate::authorize('view', $adherent);

        $logo = base64_encode(file_get_contents(public_path('images/logo.png')));

        $cachet = null;
        foreach (['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg'] as $extension => $mime) {
            $chemin = public_path("images/cachet-signature.{$extension}");
            if (is_file($chemin)) {
                $cachet = "data:{$mime};base64,".base64_encode(file_get_contents($chemin));
                break;
            }
        }

        $photo = null;
        if ($adherent->photo_path && Storage::disk('public')->exists($adherent->photo_path)) {
            $contenu = Storage::disk('public')->get($adherent->photo_path);
            $mime = Storage::disk('public')->mimeType($adherent->photo_path);
            $photo = 'data:'.$mime.';base64,'.base64_encode($contenu);
        }

        // Lien signé (non falsifiable) vers la page publique de solde :
        // scanner ce QR code affiche le solde de cotisation sans connexion,
        // sans jamais exposer les données d'un autre adhérent (toute
        // modification de l'identifiant dans l'URL invalide la signature).
        $urlSolde = URL::signedRoute('carte.solde', ['adherent' => $adherent]);
        $qrCode = new QrCode(
            data: $urlSolde,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 300,
            margin: 4,
        );
        $qr = 'data:image/png;base64,'.base64_encode((new PngWriter())->write($qrCode)->getString());

        $pdf = Pdf::loadView('pdf.carte-membre', [
            'adherent' => $adherent,
            'logo' => 'data:image/png;base64,'.$logo,
            'cachet' => $cachet,
            'photo' => $photo,
            'qr' => $qr,
        ]);

        $largeurPt = self::LARGEUR_MM * 72 / 25.4;
        $hauteurPt = self::HAUTEUR_MM * 72 / 25.4;
        $pdf->setPaper([0, 0, $largeurPt, $hauteurPt]);

        return $pdf->download('carte-membre-'.$adherent->matricule.'.pdf');
    }
}
