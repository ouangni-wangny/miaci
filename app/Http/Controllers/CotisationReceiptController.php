<?php

namespace App\Http\Controllers;

use App\Models\Cotisation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CotisationReceiptController extends Controller
{
    public function __invoke(Cotisation $cotisation): Response
    {
        Gate::authorize('view', $cotisation);

        $pdf = Pdf::loadView('pdf.recu-cotisation', ['cotisation' => $cotisation]);

        return $pdf->download("recu-cotisation-{$cotisation->id}.pdf");
    }
}
