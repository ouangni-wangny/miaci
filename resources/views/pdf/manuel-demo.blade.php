<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Manuel de démo — Conformité MIACI</title>
    <style>
        @page { margin: 22mm 18mm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #1f2937; line-height: 1.5; }

        .cover { text-align: center; padding-top: 60px; }
        .cover .eyebrow { font-size: 11px; letter-spacing: 2px; text-transform: uppercase; color: #6b7280; }
        .cover h1 { font-size: 26px; margin: 14px 0 6px; color: #111827; }
        .cover .sous-titre { font-size: 13px; color: #4b5563; max-width: 420px; margin: 0 auto 30px; }
        .cover .date { font-size: 11px; color: #9ca3af; margin-top: 40px; }
        .cover-stats { margin: 30px auto 0; width: 420px; }
        .cover-stats td { padding: 10px; text-align: center; border: 1px solid #e5e7eb; }
        .cover-stats .valeur { display: block; font-size: 18px; font-weight: bold; color: #166534; }
        .cover-stats .libelle { display: block; font-size: 9px; color: #6b7280; margin-top: 2px; }

        .sommaire { page-break-before: always; }
        .sommaire h2 { font-size: 15px; color: #111827; margin-bottom: 14px; }
        .sommaire table { width: 100%; border-collapse: collapse; }
        .sommaire td { padding: 6px 4px; border-bottom: 1px solid #f3f4f6; font-size: 11px; }
        .sommaire td.num { width: 24px; color: #166534; font-weight: bold; }

        .etape { page-break-before: always; }
        .etape-badge { display: inline-block; background: #166534; color: #fff; font-size: 10px; font-weight: bold; padding: 3px 9px; border-radius: 3px; letter-spacing: 0.5px; }
        .etape h2 { font-size: 16px; color: #111827; margin: 10px 0 2px; }
        .etape .article-ref { font-size: 10px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 14px; }

        .bloc { margin-bottom: 14px; border: 1px solid #e5e7eb; border-radius: 4px; overflow: hidden; }
        .bloc-titre { background: #f9fafb; font-size: 9.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4px; color: #6b7280; padding: 5px 10px; border-bottom: 1px solid #e5e7eb; }
        .bloc-corps { padding: 9px 10px; font-size: 11px; }

        .citation { font-style: italic; color: #4b5563; border-left: 2px solid #d1d5db; padding-left: 8px; margin: 0; }

        .action-item { margin: 4px 0 4px 14px; }

        table.donnees { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.donnees td { padding: 4px 6px; border-bottom: 1px solid #f3f4f6; }
        table.donnees td.label { color: #6b7280; width: 45%; }
        table.donnees td.valeur { font-weight: bold; color: #111827; }

        .talking-point { background: #f0fdf4; border-left: 3px solid #16a34a; padding: 7px 10px; font-size: 10.5px; color: #14532d; margin-top: 4px; }
        .alerte { background: #fffbeb; border-left: 3px solid #d97706; padding: 7px 10px; font-size: 10.5px; color: #78350f; }

        .footer-note { margin-top: 30px; font-size: 9px; color: #9ca3af; text-align: center; }

        .table-villes { width: 100%; border-collapse: collapse; }
        .table-villes td { padding: 3px 6px; border-bottom: 1px solid #f3f4f6; }
        .table-villes td.n { text-align: right; font-weight: bold; }
    </style>
</head>
<body>

{{-- COUVERTURE --}}
<div class="cover">
    <p class="eyebrow">MIACI — Mutuelle des Instituteurs et Assimilés de Côte d'Ivoire</p>
    <h1>Manuel de démo</h1>
    <p class="sous-titre">
        Conduite pas à pas de la démonstration : chaque règle des statuts (Articles 6, 7 et 8)
        illustrée par un écran précis de l'application et un exemple réel tiré de la base actuelle.
    </p>

    <table class="cover-stats">
        <tr>
            <td><span class="valeur">{{ $totalAdherents }}</span><span class="libelle">Adhérents tuteurs</span></td>
            <td><span class="valeur">{{ $totalPac }}</span><span class="libelle">Personnes à charge</span></td>
            <td><span class="valeur">{{ $totalVilles }}</span><span class="libelle">Villes</span></td>
        </tr>
    </table>

    <p class="date">Généré le {{ $dateGeneration->format('d/m/Y à H:i') }} — à régénérer juste avant la démo si la base a changé.</p>
</div>

{{-- SOMMAIRE --}}
<div class="sommaire">
    <h2>Plan de la démonstration</h2>
    <table>
        <tr><td class="num">1</td><td>Tableau de bord — vue d'ensemble de la mutuelle</td></tr>
        <tr><td class="num">2</td><td>Article 6 — Admission ouverte à tous, pas seulement aux instituteurs</td></tr>
        <tr><td class="num">3</td><td>Article 7 — Droit d'adhésion (11 000 F) et exonération des membres déjà présents</td></tr>
        <tr><td class="num">4</td><td>Article 7 — Délai de carence selon l'âge, y compris pour chaque personne à charge</td></tr>
        <tr><td class="num">5</td><td>Article 7 — Cotisations : règle du 15 du mois et plafond de 4 500 F</td></tr>
        <tr><td class="num">6</td><td>Article 7 / Article 8 — Délai de grâce, pénalité de retard, signalement (jamais de radiation automatique)</td></tr>
        <tr><td class="num">7</td><td>Article 7 — Montants d'assistance par type d'événement</td></tr>
        <tr><td class="num">8</td><td>Article 7 — Déduction de la cotisation du mois en cours et délais du dossier décès</td></tr>
        <tr><td class="num">9</td><td>Article 7 — Don de fin d'année</td></tr>
        <tr><td class="num">10</td><td>Répartition géographique des adhérents</td></tr>
        <tr><td class="num">11</td><td>Carte de membre, QR code et ayant droit</td></tr>
        <tr><td class="num">12</td><td>Point de vigilance à assumer si la question est posée</td></tr>
    </table>
</div>

{{-- ETAPE 1 : DASHBOARD --}}
<div class="etape">
    <span class="etape-badge">ÉTAPE 1</span>
    <h2>Tableau de bord — vue d'ensemble</h2>
    <p class="article-ref">Écran : Tableau de bord</p>

    <div class="bloc">
        <div class="bloc-titre">Ce qu'il faut montrer</div>
        <div class="bloc-corps">
            <p class="action-item">→ Ouvrez « Tableau de bord » (premier lien du menu).</p>
            <p class="action-item">→ Montrez les indicateurs clés (adhérents actifs, cotisations de l'année, montants versés en assistance).</p>
        </div>
    </div>

    <div class="talking-point">
        À dire : « Cet écran donne une vue d'ensemble en temps réel de la mutuelle — {{ $totalAdherents }}
        adhérents actifs, répartis sur {{ $totalVilles }} villes, avec {{ $totalPac }} personnes à charge
        enregistrées. Tout ce que je vais vous montrer ensuite part de ces mêmes données réelles. »
    </div>
</div>

{{-- ETAPE 2 : ADMISSION --}}
<div class="etape">
    <span class="etape-badge">ÉTAPE 2</span>
    <h2>Admission ouverte à tous</h2>
    <p class="article-ref">Article 6 — Écran : page d'accueil du site (non connecté)</p>

    <div class="bloc">
        <div class="bloc-titre">Ce que dit le règlement</div>
        <div class="bloc-corps">
            <p class="citation">« L'association est ouverte à tous les instituteurs et toutes autres personnes
            se sentant concernées par la vision de ladite mutuelle. »</p>
        </div>
    </div>

    <div class="bloc">
        <div class="bloc-titre">Ce qu'il faut montrer</div>
        <div class="bloc-corps">
            <p class="action-item">→ Ouvrez la page d'accueil publique du site (déconnecté).</p>
            <p class="action-item">→ Pointez l'en-tête : le nom complet de la mutuelle est toujours affiché en entier.</p>
        </div>
    </div>

    <div class="talking-point">
        À dire : « Le site ne réduit jamais la mutuelle aux seuls instituteurs — le nom complet est partout,
        conformément à l'article 6. »
    </div>
</div>

{{-- ETAPE 3 : DROIT D'ADHESION --}}
<div class="etape">
    <span class="etape-badge">ÉTAPE 3</span>
    <h2>Droit d'adhésion</h2>
    <p class="article-ref">Article 7 — Adhésions — Écran : Adhérents tuteurs → fiche d'un adhérent tuteur</p>

    <div class="bloc">
        <div class="bloc-titre">Ce que dit le règlement</div>
        <div class="bloc-corps"><p class="citation">« Droit d'adhésion : 11000f »</p></div>
    </div>

    <div class="bloc">
        <div class="bloc-titre">Donnée en base</div>
        <div class="bloc-corps">
            <table class="donnees">
                <tr><td class="label">Montant paramétré</td><td class="valeur">{{ number_format($droitAdhesion ?? 0, 0, ',', ' ') }} F</td></tr>
                <tr><td class="label">Adhérents tuteurs exonérés (déjà membres avant le paiement en ligne)</td><td class="valeur">{{ $exoneresCount }} / {{ $totalAdherents }}</td></tr>
                @if ($nonExonere)
                    <tr><td class="label">Exemple d'adhérent devant encore payer</td><td class="valeur">{{ $nonExonere->matricule }} — {{ $nonExonere->nomComplet() }}</td></tr>
                @endif
            </table>
        </div>
    </div>

    <div class="bloc">
        <div class="bloc-titre">Ce qu'il faut montrer</div>
        <div class="bloc-corps">
            <p class="action-item">→ Ouvrez « Adhérents tuteurs », recherchez
            @if ($nonExonere) « {{ $nonExonere->matricule }} » @else un adhérent récent @endif.</p>
            <p class="action-item">→ Ouvrez sa fiche : montrez le statut du droit d'adhésion.</p>
        </div>
    </div>

    <div class="talking-point">
        À dire : « Les {{ $exoneresCount }} membres déjà présents avant la mise en ligne du paiement ont été
        exonérés — seuls les nouveaux arrivants doivent s'acquitter des 11 000 F, en ligne ou enregistrés
        par un gestionnaire. »
    </div>
</div>

{{-- ETAPE 4 : CARENCE PAR AGE --}}
<div class="etape">
    <span class="etape-badge">ÉTAPE 4</span>
    <h2>Délai de carence selon l'âge</h2>
    <p class="article-ref">Article 7 — Adhésions — Écran : Adhérents tuteurs → fiche d'un adhérent tuteur</p>

    <div class="bloc">
        <div class="bloc-titre">Ce que dit le règlement</div>
        <div class="bloc-corps">
            <p class="citation">« Délai de carence : 8 mois pour les adhérents nés après 1956 et 12 mois
            pour ceux nés avant cette même date. »</p>
        </div>
    </div>

    <div class="bloc">
        <div class="bloc-titre">Donnée en base</div>
        <div class="bloc-corps">
            <table class="donnees">
                <tr><td class="label">Personnes à charge à 8 mois de carence</td><td class="valeur">{{ $pacHuitMoisCount }}</td></tr>
                <tr><td class="label">Personnes à charge à 12 mois de carence</td><td class="valeur">{{ $pacDouzeMoisCount }}</td></tr>
                @if ($pacDouzeMois)
                    <tr><td class="label">Exemple à 12 mois</td><td class="valeur">{{ $pacDouzeMois->nomComplet() }} (foyer {{ $pacDouzeMois->adherent->matricule }})</td></tr>
                @endif
            </table>
        </div>
    </div>

    <div class="bloc">
        <div class="bloc-titre">Ce qu'il faut montrer</div>
        <div class="bloc-corps">
            <p class="action-item">→ Sur la fiche d'un adhérent tuteur, ouvrez l'onglet des personnes à charge.</p>
            <p class="action-item">→ Expliquez que chaque personne à charge cotise et est soumise à la carence
            comme un membre à part entière, selon sa propre date de naissance.</p>
            <p class="action-item">→ Si on vous demande : montrez que la date de naissance peut être corrigée
            à tout moment depuis « Mon profil » (ou la fiche gestion) — le délai se recalcule aussitôt,
            automatiquement, partout dans l'application.</p>
        </div>
    </div>
</div>

{{-- ETAPE 5 : COTISATIONS --}}
<div class="etape">
    <span class="etape-badge">ÉTAPE 5</span>
    <h2>Cotisations : règle du 15 et plafond mensuel</h2>
    <p class="article-ref">Article 7 — Cotisations — Écran : Cotisations</p>

    <div class="bloc">
        <div class="bloc-titre">Ce que dit le règlement</div>
        <div class="bloc-corps">
            <p class="citation">« Tous les membres qui adhèrent avant le 15 du mois payent les cotisations
            du mois en cours (…) la cotisation par membre par mois n'excède pas 4 500 F. »</p>
        </div>
    </div>

    <div class="bloc">
        <div class="bloc-titre">Donnée en base</div>
        <div class="bloc-corps">
            <table class="donnees">
                <tr><td class="label">Montant de cotisation paramétré</td><td class="valeur">{{ number_format($montantCotisation ?? 0, 0, ',', ' ') }} F / mois</td></tr>
            </table>
        </div>
    </div>

    <div class="bloc">
        <div class="bloc-titre">Ce qu'il faut montrer</div>
        <div class="bloc-corps">
            <p class="action-item">→ Ouvrez « Cotisations » — montrez le relevé période par période d'un adhérent.</p>
            <p class="action-item">→ Expliquez qu'une adhésion avant le 15 démarre dès le mois en cours,
            après le 15 seulement à partir du mois suivant.</p>
        </div>
    </div>
</div>

{{-- ETAPE 6 : RETARD ET SIGNALEMENT --}}
<div class="etape">
    <span class="etape-badge">ÉTAPE 6</span>
    <h2>Délai de grâce, pénalité de retard et signalement</h2>
    <p class="article-ref">Article 7 / Article 8 — Écran : Adhérents tuteurs → fiche d'un adhérent tuteur</p>

    <div class="bloc">
        <div class="bloc-titre">Ce que dit le règlement</div>
        <div class="bloc-corps">
            <p class="citation">« Délai de paiement : au plus tard le 5 du mois suivant. Tous les mutualistes
            qui paieront après le 5 verront leur délai de carence augmenté d'un mois ; au-delà d'un mois
            de retard, augmenté de 3 mois. Au-delà de 3 mois d'arriérés, le mutualiste perd sa qualité de
            membre » (radiation toujours prononcée par le conseil d'administration, après notification).</p>
        </div>
    </div>

    @if ($exempleRetard)
        <div class="bloc">
            <div class="bloc-titre">Donnée en base</div>
            <div class="bloc-corps">
                <table class="donnees">
                    <tr><td class="label">Exemple réel en retard</td><td class="valeur">{{ $exempleRetard['adherent']->matricule }} — {{ $exempleRetard['adherent']->nomComplet() }}</td></tr>
                    <tr><td class="label">Mois d'arriérés constatés</td><td class="valeur">{{ $exempleRetard['arrieres'] }}</td></tr>
                    <tr><td class="label">Délai de carence effectif</td><td class="valeur">{{ $exempleRetard['carence'] }} mois</td></tr>
                    <tr><td class="label">Signalé pour arriérés (&gt; 3 mois) ?</td><td class="valeur">{{ $exempleRetard['signalable'] ? 'Oui' : 'Non' }}</td></tr>
                </table>
            </div>
        </div>

        <div class="bloc">
            <div class="bloc-titre">Ce qu'il faut montrer</div>
            <div class="bloc-corps">
                <p class="action-item">→ Recherchez « {{ $exempleRetard['adherent']->matricule }} » dans « Adhérents tuteurs ».</p>
                <p class="action-item">→ Montrez le badge de statut de cotisation sur la ligne ou la fiche.</p>
            </div>
        </div>

        @if ($exempleRetard['signalable'])
            <div class="alerte">
                Point important à dire : « Cet adhérent est <strong>signalé</strong> — pas radié. L'article 8
                exige une notification et une décision du conseil d'administration ; l'application n'automatise
                jamais cette décision, elle se contente d'alerter le gestionnaire. »
            </div>
        @endif
    @else
        <div class="alerte">
            Aucun adhérent en retard n'a été trouvé au moment de la génération de ce manuel — la base est
            entièrement à jour. Expliquez la règle verbalement, ou utilisez un exemple préparé à l'avance.
        </div>
    @endif
</div>

{{-- ETAPE 7 : MONTANTS ASSISTANCE --}}
<div class="etape">
    <span class="etape-badge">ÉTAPE 7</span>
    <h2>Montants d'assistance par type d'événement</h2>
    <p class="article-ref">Article 7 — Assistances — Écran : Paramètres → Types de sinistre</p>

    <div class="bloc">
        <div class="bloc-titre">Ce que dit le règlement</div>
        <div class="bloc-corps">
            <p class="citation">« Assistance décès : 1 000 000f (…) Assistance Dot, Mariage, Naissance et Prêt :
            montants variables selon la situation — mariage, naissance et prêt actuellement suspendus par
            la mutuelle elle-même. »</p>
        </div>
    </div>

    <div class="bloc">
        <div class="bloc-titre">Donnée en base</div>
        <div class="bloc-corps">
            <table class="donnees">
                @foreach ($typesSinistre as $type)
                    <tr>
                        <td class="label">{{ $type->libelle }}</td>
                        <td class="valeur">{{ number_format($type->plafond_montant, 0, ',', ' ') }} F — {{ $type->actif ? 'actif' : 'suspendu' }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>

    <div class="bloc">
        <div class="bloc-titre">Ce qu'il faut montrer</div>
        <div class="bloc-corps">
            <p class="action-item">→ Ouvrez « Paramètres cotisation » puis « Types de sinistre » (menu Administration).</p>
            <p class="action-item">→ Montrez que mariage, naissance et prêt sont désactivés — pas un oubli,
            une fidélité au statut « Suspendue » écrit noir sur blanc dans le règlement.</p>
        </div>
    </div>
</div>

{{-- ETAPE 8 : DECES --}}
<div class="etape">
    <span class="etape-badge">ÉTAPE 8</span>
    <h2>Déduction cotisation décès et délais du dossier</h2>
    <p class="article-ref">Article 7 — Écran : Sinistres</p>

    <div class="bloc">
        <div class="bloc-titre">Ce que dit le règlement</div>
        <div class="bloc-corps">
            <p class="citation">« Avant l'assistance décès, la Direction se charge de soustraire les cotisations
            du mois en cours du bénéficiaire (…) Le bénéficiaire a un délai de 21 jours pour fournir les
            documents (…) Le délai de l'assistance est de 2 semaines. »</p>
        </div>
    </div>

    @if ($demandesSinistreCount === 0)
        <div class="alerte">
            Aucune demande de sinistre n'existe encore en base ({{ $demandesSinistreCount }}). Pour cette
            étape, faites une démonstration <strong>en direct</strong> : connectez-vous avec le compte de
            {{ $koffiGerard->nomComplet() ?? 'Koffi Gérard' }} (cas test) et soumettez une demande de type
            « Décès » depuis « Mon espace → Sinistres ». Montrez ensuite, côté gestion, que le montant
            proposé est pré-rempli net de la cotisation du mois, avec les deux dates limites affichées.
        </div>
    @else
        <div class="bloc">
            <div class="bloc-titre">Ce qu'il faut montrer</div>
            <div class="bloc-corps">
                <p class="action-item">→ Ouvrez « Sinistres », ouvrez un dossier de type Décès.</p>
                <p class="action-item">→ Montrez le montant pré-rempli et les deux badges de délai.</p>
            </div>
        </div>
    @endif
</div>

{{-- ETAPE 9 : DONS --}}
<div class="etape">
    <span class="etape-badge">ÉTAPE 9</span>
    <h2>Don de fin d'année</h2>
    <p class="article-ref">Article 7 — Écran : Dons de fin d'année</p>

    <div class="bloc">
        <div class="bloc-titre">Ce que dit le règlement</div>
        <div class="bloc-corps">
            <p class="citation">« Dons de 10 000f, tous les 20 du mois de décembre (…) pour les adhérents
            ayant au moins trois (03) membres dans leur carnet. »</p>
        </div>
    </div>

    <div class="bloc">
        <div class="bloc-titre">Donnée en base</div>
        <div class="bloc-corps">
            <table class="donnees">
                <tr><td class="label">Adhérents tuteurs éligibles aujourd'hui</td><td class="valeur">{{ $donsEligiblesCount }}</td></tr>
                @if ($donExemple)
                    <tr><td class="label">Exemple</td><td class="valeur">{{ $donExemple->matricule }} — {{ $donExemple->nomComplet() }}</td></tr>
                @endif
            </table>
        </div>
    </div>

    <div class="bloc">
        <div class="bloc-titre">Ce qu'il faut montrer</div>
        <div class="bloc-corps">
            <p class="action-item">→ Ouvrez « Dons de fin d'année ».</p>
            <p class="action-item">→ Montrez la liste filtrée automatiquement aux {{ $donsEligiblesCount }}
            adhérents éligibles (carnet de 3 membres, cotisation à jour, délai de carence écoulé).</p>
        </div>
    </div>
</div>

{{-- ETAPE 10 : VILLES --}}
<div class="etape">
    <span class="etape-badge">ÉTAPE 10</span>
    <h2>Répartition géographique</h2>
    <p class="article-ref">Écran : Adhérents tuteurs (widget de répartition par ville)</p>

    <div class="bloc">
        <div class="bloc-titre">Donnée en base</div>
        <div class="bloc-corps">
            <table class="table-villes">
                @foreach ($repartitionVilles->take(8) as $v)
                    <tr><td>{{ $v->ville }}</td><td class="n">{{ $v->total }}</td></tr>
                @endforeach
            </table>
        </div>
    </div>

    <div class="bloc">
        <div class="bloc-titre">Ce qu'il faut montrer</div>
        <div class="bloc-corps">
            <p class="action-item">→ Sur « Adhérents tuteurs », montrez le widget de répartition par ville.</p>
            <p class="action-item">→ Cliquez sur une ville pour filtrer la liste instantanément.</p>
        </div>
    </div>
</div>

{{-- ETAPE 11 : CARTE / QR / AYANT DROIT --}}
<div class="etape">
    <span class="etape-badge">ÉTAPE 11</span>
    <h2>Carte de membre, QR code et ayant droit</h2>
    <p class="article-ref">Écran : fiche adhérent tuteur → Carte de membre</p>

    <div class="bloc">
        <div class="bloc-titre">Ce qu'il faut montrer</div>
        <div class="bloc-corps">
            <p class="action-item">→ Ouvrez la fiche de
            @if ($koffiGerard) « {{ $koffiGerard->matricule }} — {{ $koffiGerard->nomComplet() }} » @else votre cas test @endif
            et téléchargez sa carte de membre.</p>
            <p class="action-item">→ Scannez le QR code du recto avec un téléphone (hors connexion à
            l'application) : montrez que l'écran affiche le solde de cotisation sans authentification.</p>
            <p class="action-item">→ Montrez, au verso, le bloc « Ayant droit ».</p>
        </div>
    </div>

    <div class="talking-point">
        À dire : « L'ayant droit — la personne à qui remettre le bénéfice des cotisations en cas de décès
        ou de disparition — est une fonctionnalité demandée par la direction, en plus des statuts : chaque
        adhérent peut la renseigner lui-même, ou un gestionnaire le fait pour lui. »
    </div>
</div>

{{-- ETAPE 12 : POINT DE VIGILANCE --}}
<div class="etape">
    <span class="etape-badge">ÉTAPE 12</span>
    <h2>Point de vigilance à assumer</h2>
    <p class="article-ref">Article 7 — condition « ne doit pas à la mutuelle (prêt) »</p>

    <div class="alerte">
        L'assistance Prêt est déjà suspendue par les statuts eux-mêmes : cette condition ne bloque donc
        aucune assistance active aujourd'hui. Mais l'application ne trace pas encore les décaissements ni
        les remboursements de prêt — si la question est posée en démo, la réponse honnête est : « Cette
        condition sera vérifiable dès que l'assistance Prêt sera réactivée et qu'un module de suivi des
        prêts sera développé — ce n'est pas encore le cas, et nous ne simulons pas un contrôle qui
        n'existerait pas réellement. »
    </div>
</div>

<p class="footer-note">
    Manuel généré automatiquement à partir de la base de données réelle de l'application MIACI —
    {{ $dateGeneration->format('d/m/Y à H:i') }}.
</p>

</body>
</html>
