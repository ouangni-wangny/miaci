<?php

namespace App\Http\Controllers;

use App\Models\PieceJustificative;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PieceJustificativeController extends Controller
{
    public function __invoke(PieceJustificative $piece): StreamedResponse
    {
        Gate::authorize('view', $piece);

        abort_unless(Storage::disk('local')->exists($piece->fichier_path), 404);

        return Storage::disk('local')->response($piece->fichier_path, $piece->nom_original);
    }
}
