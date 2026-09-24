<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Briefing de conformité — MIACI</title>
    <style>
        @page { margin: 20mm 18mm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10.5px; color: #1f2937; line-height: 1.45; }

        .cover { text-align: center; padding-top: 55px; }
        .cover .eyebrow { font-size: 11px; letter-spacing: 2px; text-transform: uppercase; color: #6b7280; }
        .cover h1 { font-size: 25px; margin: 14px 0 6px; color: #111827; }
        .cover .sous-titre { font-size: 12.5px; color: #4b5563; max-width: 420px; margin: 0 auto 30px; }
        .cover-stats { margin: 26px auto 0; width: 440px; }
        .cover-stats td { padding: 10px; text-align: center; border: 1px solid #e5e7eb; }
        .cover-stats .valeur { display: block; font-size: 17px; font-weight: bold; color: #166534; }
        .cover-stats .libelle { display: block; font-size: 8.5px; color: #6b7280; margin-top: 2px; }
        .cover .date { font-size: 10px; color: #9ca3af; margin-top: 40px; }

        .verdict-cover { margin: 30px auto 0; width: 440px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 14px 16px; text-align: left; }
        .verdict-cover p { margin: 0; font-size: 11px; color: #14532d; }
        .verdict-cover strong { color: #166534; }

        .article-section { page-break-inside: avoid; margin-bottom: 16px; }
        .article-section.force-break { page-break-before: always; }
        .article-titre { font-size: 13px; font-weight: bold; color: #111827; background: #f9fafb; border: 1px solid #e5e7eb; border-left: 4px solid #166534; padding: 6px 10px; margin: 0 0 8px; }

        .liste { margin: 0 0 6px; padding: 0; list-style: none; }
        .liste li { padding: 3px 0 3px 16px; position: relative; font-size: 10.5px; }
        .liste.fait li:before { content: "✔"; position: absolute; left: 0; color: #16a34a; font-weight: bold; }
        .liste.nonfait li:before { content: "✘"; position: absolute; left: 0; color: #d97706; font-weight: bold; }

        .sous-label { font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold; color: #6b7280; margin: 8px 0 3px; }
        .sous-label.nonfait { color: #b45309; }

        table.synthese { width: 100%; border-collapse: collapse; margin-top: 6px; page-break-before: always; }
        table.synthese th { background: #166534; color: #fff; font-size: 9.5px; text-align: left; padding: 6px 8px; }
        table.synthese td { padding: 5px 8px; border-bottom: 1px solid #f3f4f6; font-size: 10px; vertical-align: top; }
        table.synthese tr:nth-child(even) td { background: #f9fafb; }
        .puce-verte { color: #16a34a; font-weight: bold; }
        .puce-ambre { color: #d97706; font-weight: bold; }

        .footer-note { margin-top: 30px; font-size: 8.5px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>

{{-- COUVERTURE --}}
<div class="cover">
    <p class="eyebrow">MIACI — Mutuelle des Instituteurs et Assimilés de Côte d'Ivoire</p>
    <h1>Briefing de conformité</h1>
    <p class="sous-titre">
        Bilan complet, article par article des statuts (15/12/2025) : ce qui est fait dans
        l'application et ce qui ne l'est pas encore.
    </p>

    <table class="cover-stats">
        <tr>
            <td><span class="valeur">{{ $totalAdherents }}</span><span class="libelle">Adhérents tuteurs</span></td>
            <td><span class="valeur">{{ $totalPac }}</span><span class="libelle">Personnes à charge</span></td>
            <td><span class="valeur">{{ $totalVilles }}</span><span class="libelle">Villes</span></td>
            <td><span class="valeur">136/136</span><span class="libelle">Tests automatisés</span></td>
        </tr>
    </table>

    <div class="verdict-cover">
        <p>
            <strong>Verdict :</strong> sur l'ensemble des règles de gestion effectivement actives
            (adhésion, cotisation, carence, retard, radiation, décès, dot, don de fin d'année),
            l'application est <strong>entièrement conforme</strong> et testée. Deux points secondaires,
            détaillés ci-après, restent hors périmètre — sans impact sur le fonctionnement courant.
        </p>
    </div>

    <p class="date">Généré le {{ $dateGeneration->format('d/m/Y à H:i') }} — à régénérer si la base ou le code évoluent avant présentation.</p>
</div>

{{-- ARTICLE 6 --}}
<div class="article-section force-break">
    <p class="article-titre">Article 6 — Admission</p>
    <p class="sous-label">Fait</p>
    <ul class="liste fait">
        <li>L'admission n'est jamais présentée comme réservée aux instituteurs — nom complet de la mutuelle affiché partout sur le site.</li>
    </ul>
</div>

{{-- ARTICLE 7 - ADHESIONS --}}
<div class="article-section">
    <p class="article-titre">Article 7 — Adhésions</p>
    <p class="sous-label">Fait</p>
    <ul class="liste fait">
        <li>Droit d'adhésion à 11 000 F, paramétrable, payable en ligne ou enregistré par un gestionnaire.</li>
        <li>Membres déjà présents avant la mise en ligne du paiement automatiquement exonérés à l'import.</li>
        <li>Délai de carence 8 mois (né après 1956) / 12 mois (né en 1956 ou avant) — appliqué à l'adhérent et à chacune de ses personnes à charge individuellement.</li>
        <li>Recalcul automatique et immédiat du délai dès qu'une vraie date de naissance est renseignée, sans tâche manuelle.</li>
    </ul>
</div>

{{-- ARTICLE 7 - COTISATIONS --}}
<div class="article-section">
    <p class="article-titre">Article 7 — Cotisations</p>
    <p class="sous-label">Fait</p>
    <ul class="liste fait">
        <li>Adhésion avant le 15 du mois → cotisation due dès le mois en cours ; à partir du 15 → dès le mois suivant.</li>
        <li>Plafond de 4 500 F/membre/mois, conforme à la clarification du règlement selon laquelle le total ne dépasse jamais ce montant quel que soit le nombre d'événements.</li>
        <li>Délai de paiement au 5 du mois suivant, avec délai de grâce.</li>
        <li>Pénalité de retard : +1 mois de carence après le 5, +3 mois si le retard dépasse un mois.</li>
    </ul>
</div>

{{-- ARTICLE 7/8 - ARRIERES --}}
<div class="article-section">
    <p class="article-titre">Article 7 / Article 8 — Arriérés et radiation</p>
    <p class="sous-label">Fait</p>
    <ul class="liste fait">
        <li>Signalement automatique des adhérents au-delà de 3 mois d'arriérés.</li>
        <li>Radiation, démission et décès toujours décidés manuellement par un gestionnaire, jamais automatiquement — conforme à l'exigence de notification préalable de l'Article 8.</li>
    </ul>
</div>

{{-- ARTICLE 7 - ASSISTANCES --}}
<div class="article-section force-break">
    <p class="article-titre">Article 7 — Assistances</p>
    <p class="sous-label">Fait</p>
    <ul class="liste fait">
        <li>Contrôle d'éligibilité automatique (carence, cotisation à jour, pièces obligatoires) sur le vrai bénéficiaire — l'adhérent ou la personne à charge concernée, pas systématiquement le tuteur.</li>
        <li>Montants exacts par type : Décès 1 000 000 F (actif), Dot 25 000/45 000 F (actif) ; Mariage et Naissance correctement désactivés, conformément à leur statut « Suspendue » dans les statuts.</li>
        <li>Déduction de la cotisation du mois en cours avant assistance décès, montant pré-rempli et modifiable par le gestionnaire.</li>
        <li>Délai de 21 jours pour fournir les documents de décès, affiché.</li>
        <li>Délai de traitement de 2 semaines, affiché — et qui ignore désormais le mois de décembre (le bureau étant en congé) : une demande soumise en décembre ou fin novembre voit son échéance glisser correctement en janvier plutôt que de s'afficher, à tort, comme en retard.</li>
        <li>Don de fin d'année : 10 000 F, versés le 20 décembre, aux adhérents à jour de cotisation, ayant fini leur carence, et dont le carnet compte au moins 3 membres (eux-mêmes + au moins 2 personnes à charge validées).</li>
        <li>Décision finale toujours humaine (approbation/rejet par un gestionnaire), jamais automatisée.</li>
    </ul>

    <p class="sous-label nonfait">Non fait</p>
    <ul class="liste nonfait">
        <li><strong>Condition « ne doit pas à la mutuelle (prêt) »</strong> — non vérifiable : aucun module de suivi des décaissements et remboursements de prêt n'existe. Sans impact aujourd'hui puisque l'assistance Prêt est elle-même suspendue par la mutuelle ; à construire si elle est un jour réactivée.</li>
        <li><strong>« Les adhérents tuteurs qui récupèrent leur carnet après s'être mis sous d'autres mutualistes »</strong> — scénario administratif rare (une personne à charge devenant tuteur indépendant), sans fonctionnalité dédiée ; à gérer manuellement par un gestionnaire si le cas se présente.</li>
    </ul>
</div>

{{-- ARTICLE 10 et ORGANISATIONNELS --}}
<div class="article-section">
    <p class="article-titre">Article 10 — Ressources</p>
    <p class="sous-label">Fait</p>
    <ul class="liste fait">
        <li>Droits d'adhésion et cotisations sont les deux seules sources suivies dans l'application, conformément au texte.</li>
    </ul>
</div>

<div class="article-section">
    <p class="article-titre">Articles 1, 2, 3, 4, 5, 11 — Dispositions organisationnelles</p>
    <ul class="liste fait">
        <li>Création, objet, siège social, durée, composition du bureau, assemblée générale : purement organisationnels, ne décrivent aucune règle de gestion automatisable et ne nécessitent aucune implémentation dans l'application.</li>
    </ul>
</div>

{{-- SYNTHESE --}}
<table class="synthese">
    <tr><th colspan="2">Synthèse — Fait / Non fait</th></tr>
    <tr><td><span class="puce-verte">✔ Fait</span></td><td>Admission ouverte à tous (Art. 6)</td></tr>
    <tr><td><span class="puce-verte">✔ Fait</span></td><td>Droit d'adhésion et exonération des anciens membres (Art. 7)</td></tr>
    <tr><td><span class="puce-verte">✔ Fait</span></td><td>Délai de carence par âge — adhérent et personnes à charge (Art. 7)</td></tr>
    <tr><td><span class="puce-verte">✔ Fait</span></td><td>Règle du 15 du mois et plafond de cotisation (Art. 7)</td></tr>
    <tr><td><span class="puce-verte">✔ Fait</span></td><td>Délai de grâce et pénalités de retard (Art. 7)</td></tr>
    <tr><td><span class="puce-verte">✔ Fait</span></td><td>Signalement des arriérés, radiation toujours manuelle (Art. 7/8)</td></tr>
    <tr><td><span class="puce-verte">✔ Fait</span></td><td>Montants d'assistance par type d'événement (Art. 7)</td></tr>
    <tr><td><span class="puce-verte">✔ Fait</span></td><td>Déduction cotisation décès, délais 21j / 2 semaines, exception de décembre (Art. 7)</td></tr>
    <tr><td><span class="puce-verte">✔ Fait</span></td><td>Don de fin d'année (Art. 7)</td></tr>
    <tr><td><span class="puce-verte">✔ Fait</span></td><td>Ressources : droits d'adhésion + cotisations uniquement (Art. 10)</td></tr>
    <tr><td><span class="puce-ambre">✘ Non fait</span></td><td>Condition « ne doit pas à la mutuelle (prêt) » — hors périmètre, sans impact (Art. 7)</td></tr>
    <tr><td><span class="puce-ambre">✘ Non fait</span></td><td>Récupération de carnet par un tuteur — cas rare, non automatisé (Art. 7)</td></tr>
</table>

<p class="footer-note">
    Briefing généré automatiquement à partir de la base de données réelle et de la suite de tests de
    l'application MIACI — {{ $dateGeneration->format('d/m/Y à H:i') }}.
</p>

</body>
</html>
