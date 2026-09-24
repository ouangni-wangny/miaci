<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Sert le document officiel des statuts de la MIACI (signé), téléchargeable
 * depuis la page d'accueil sans authentification. Le fichier lui-même
 * (public/documents/statuts-miaci.pdf) est le document réel fourni par la
 * mutuelle, signatures et cachet déjà inclus — pas une reconstitution
 * générée par l'application. Stocké sous public/ (comme le logo et le
 * cachet) pour qu'il soit versionné avec le projet et survive un nouveau
 * déploiement.
 */
class StatutsController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        $chemin = public_path('documents/statuts-miaci.pdf');

        abort_unless(is_file($chemin), 404);

        return response()->download($chemin, 'statuts-miaci.pdf');
    }
}
