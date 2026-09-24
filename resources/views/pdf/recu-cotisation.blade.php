<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Reçu de cotisation</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #1f2937; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-size: 18px; margin: 0; }
        .header p { margin: 2px 0; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        td { padding: 8px 4px; border-bottom: 1px solid #e5e7eb; }
        td.label { color: #6b7280; width: 40%; }
        td.value { font-weight: bold; }
        .montant { margin-top: 24px; text-align: center; font-size: 20px; font-weight: bold; }
        .footer { margin-top: 60px; font-size: 10px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>MIACI — Mutuelle des Instituteurs et Assimilés de Côte d'Ivoire</h1>
        <p>Reçu de cotisation n° {{ str_pad($cotisation->id, 6, '0', STR_PAD_LEFT) }}</p>
    </div>

    <table>
        <tr><td class="label">Adhérent tuteur</td><td class="value">{{ $cotisation->adherent->nomComplet() }}</td></tr>
        <tr><td class="label">Matricule</td><td class="value">{{ $cotisation->adherent->matricule }}</td></tr>
        <tr><td class="label">Période couverte</td><td class="value">Du {{ $cotisation->periode_debut->format('d/m/Y') }} au {{ $cotisation->periode_fin->format('d/m/Y') }}</td></tr>
        <tr><td class="label">Date de paiement</td><td class="value">{{ $cotisation->date_paiement->format('d/m/Y') }}</td></tr>
        <tr><td class="label">Mode de paiement</td><td class="value">{{ $cotisation->mode_paiement->libelle() }}</td></tr>
        @if ($cotisation->reference)
            <tr><td class="label">Référence</td><td class="value">{{ $cotisation->reference }}</td></tr>
        @endif
        @if ($cotisation->gestionnaire)
            <tr><td class="label">Enregistré par</td><td class="value">{{ $cotisation->gestionnaire->name }}</td></tr>
        @endif
    </table>

    <p class="montant">{{ number_format($cotisation->montant, 0, ',', ' ') }} FCFA</p>

    <p class="footer">Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}. Conservez ce reçu comme justificatif de paiement.</p>
</body>
</html>
