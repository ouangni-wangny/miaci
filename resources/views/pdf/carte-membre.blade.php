<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Carte de membre — {{ $adherent->nomComplet() }}</title>
    <style>
        @page {
            margin: 0;
            size: 85.6mm 53.98mm;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Helvetica', sans-serif;
        }
        .carte {
            position: relative;
            width: 85.6mm;
            height: 53.98mm;
            overflow: hidden;
        }
        .carte.recto {
            page-break-after: always;
        }
        .fond-recto {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: #172554;
        }
        .bande-bas {
            position: absolute;
            left: 0; right: 0; bottom: 0;
            height: 4mm;
            background: #2563eb;
        }
        .entete {
            position: absolute;
            top: 3mm; left: 4mm; right: 4mm;
            height: 8mm;
        }
        .logo-badge {
            position: absolute;
            top: 0; left: 0;
            width: 8mm; height: 8mm;
            background: #ffffff;
            border-radius: 1.5mm;
            text-align: center;
        }
        .logo-badge img { width: 6.5mm; height: 6.5mm; margin-top: 0.75mm; }
        .titre-mutuelle {
            position: absolute;
            top: 0.5mm; left: 10mm;
            color: #ffffff;
            font-size: 8pt;
            font-weight: bold;
        }
        .sous-titre-mutuelle {
            position: absolute;
            top: 4.2mm; left: 10mm;
            color: #93c5fd;
            font-size: 5pt;
        }
        .label-carte {
            position: absolute;
            top: 0; right: 0;
            color: #93c5fd;
            font-size: 5.5pt;
            letter-spacing: 0.5pt;
        }
        .photo-zone {
            position: absolute;
            top: 14mm; left: 4mm;
            width: 18mm; height: 22mm;
            background: #ffffff;
            border-radius: 1.5mm;
            text-align: center;
            overflow: hidden;
        }
        .photo-zone img {
            width: 18mm; height: 22mm;
            object-fit: cover;
        }
        .photo-zone .initiales {
            color: #172554;
            font-size: 18pt;
            font-weight: bold;
            line-height: 22mm;
        }
        .infos {
            position: absolute;
            top: 15mm; left: 25mm; right: 23mm;
            color: #ffffff;
        }
        /* Le recto a une hauteur fixe : la bande bleue du bas (4 mm) masque tout ce
           qui dépasse. Le nom, seul élément de hauteur variable, réduit sa taille
           selon sa longueur (classes ci-dessous, choisies dans le HTML) pour que le
           champ « Contact » reste au-dessus de la bande. */
        .infos .nom {
            font-size: 10pt;
            line-height: 1.15;
            font-weight: bold;
            margin-bottom: 1.5mm;
        }
        .infos .nom.nom-moyen { font-size: 8pt; }
        .infos .nom.nom-petit { font-size: 7.5pt; }
        .infos .nom.nom-long { font-size: 7pt; }
        .infos .nom.nom-tres-long { font-size: 6.5pt; }
        .infos .champ-label {
            font-size: 5pt;
            color: #93c5fd;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
        }
        .infos .champ-valeur {
            font-size: 7.5pt;
            margin-bottom: 1.8mm;
        }
        .infos .champ-valeur.gras {
            font-weight: bold;
        }
        /* Valeur libre longue (fonction, ville) : taille réduite pour rester sur
           une ligne dans la zone de 37 mm ; sinon elle repousserait « Contact »
           sous la bande du bas. */
        .infos .champ-valeur.compact {
            font-size: 6.3pt;
        }
        .qr-zone {
            position: absolute;
            right: 4mm; bottom: 6mm;
            width: 17mm;
            text-align: center;
        }
        .qr-zone img {
            width: 17mm; height: 17mm;
            border-radius: 1mm;
            background: #ffffff;
            padding: 0.8mm;
        }
        .qr-zone .legende {
            color: #93c5fd;
            font-size: 4pt;
            margin-top: 0.8mm;
            line-height: 1.2;
        }

        /* Verso */
        .fond-verso {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: #f8fafc;
        }
        .verso-entete {
            position: absolute;
            top: 3mm; left: 4mm; right: 4mm;
            font-size: 6pt;
            font-weight: bold;
            color: #172554;
            border-bottom: 0.3mm solid #cbd5e1;
            padding-bottom: 1.5mm;
        }
        /* Le verso a une hauteur fixe (CR80) : le bloc « En cas de perte » est
           ancré en bas avec sa propre place réservée, jamais poussé par le texte
           du dessus (un ayant droit ou un nom long lui passait sous le pied de page).
           Espace disponible pour le corps : de 9 mm à ~37 mm. */
        .verso-corps {
            position: absolute;
            top: 9mm; left: 4mm; width: 46mm;
            font-size: 6.2pt;
            line-height: 1.15;
            color: #334155;
        }
        .verso-corps p { margin: 0 0 1.2mm 0; }
        .verso-corps .titre-section {
            font-weight: bold;
            color: #172554;
            font-size: 5.8pt;
            text-transform: uppercase;
            margin-top: 1.6mm;
        }
        /* Noms très longs (tuteur ou ayant droit) : corps un peu plus petit
           pour rester au-dessus du bloc contact. */
        .verso-corps.dense { font-size: 5.6pt; }
        .verso-corps.dense p { margin-bottom: 0.8mm; }
        .verso-contact {
            position: absolute;
            left: 4mm; bottom: 5.4mm; width: 46mm;
            font-size: 6.2pt;
            line-height: 1.15;
            color: #334155;
        }
        .verso-contact p { margin: 0 0 0.8mm 0; }
        .verso-contact .titre-section {
            font-weight: bold;
            color: #172554;
            font-size: 5.8pt;
            text-transform: uppercase;
            margin-bottom: 0.8mm;
        }
        .verso-pied {
            position: absolute;
            left: 4mm; right: 4mm; bottom: 2.5mm;
            font-size: 5pt;
            color: #64748b;
            text-align: center;
        }
        .verso-cachet {
            position: absolute;
            top: 9mm; right: 3mm; bottom: 6mm;
            width: 29mm;
            display: table;
        }
        .verso-cachet-cellule {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }
        .verso-cachet img {
            width: 29mm;
        }
    </style>
</head>
<body>
    <!-- RECTO -->
    <div class="carte recto">
        <div class="fond-recto"></div>

        <div class="entete">
            <div class="logo-badge"><img src="{{ $logo }}" alt="MIACI"></div>
            <div class="titre-mutuelle">MIACI</div>
            <div class="sous-titre-mutuelle">Pour votre sécurité sociale</div>
            <div class="label-carte">CARTE DE MEMBRE</div>
        </div>

        <div class="photo-zone">
            @if ($photo)
                <img src="{{ $photo }}" alt="Photo">
            @else
                <div class="initiales">{{ strtoupper(mb_substr($adherent->prenom, 0, 1).mb_substr($adherent->nom, 0, 1)) }}</div>
            @endif
        </div>

        @php
            // Seuils mesurés sur des noms en majuscules dans la zone de 37 mm :
            // jusqu'à 22 caractères le nom tient sur une ligne ; au-delà il passe
            // sur deux lignes, en taille réduite, sans faire descendre « Contact »
            // sous la bande du bas.
            $longueurNom = mb_strlen($adherent->nomComplet());
            $classeNom = match (true) {
                $longueurNom <= 14 => '',
                $longueurNom <= 19 => 'nom-moyen',
                $longueurNom <= 22 => 'nom-petit',
                $longueurNom <= 28 => 'nom-long',
                default => 'nom-tres-long',
            };
        @endphp

        @php
            // Une seule ligne dans la zone de 37 mm. La largeur est estimée en
            // « unités de texte courant » : une majuscule pèse ~1,2 fois une
            // minuscule (« DIRECTEUR D'ÉCOLE PRIMAIRE » est plus large que
            // « Service Examens et Concours », pourtant de même longueur).
            // Calibré sur des rendus réels : au-delà de 27 unités la police se
            // réduit (6,3 pt) ; au-delà de 33 la valeur est coupée (...) plutôt
            // que de passer à la ligne et de repousser « Contact » sous la bande
            // du bas.
            $unites = fn (string $texte) => mb_strlen($texte) + 0.2 * preg_match_all('/\p{Lu}/u', $texte);
            $champLibre = function (?string $valeur) use ($unites): string {
                if (blank($valeur)) {
                    return '—';
                }

                $texte = trim($valeur);

                if ($unites($texte) <= 33) {
                    return $texte;
                }

                while ($texte !== '' && $unites($texte.'...') > 33) {
                    $texte = mb_substr($texte, 0, -1);
                }

                return rtrim($texte).'...';
            };
            $fonctionCarte = $champLibre($adherent->fonction);
            $villeCarte = $champLibre($adherent->ville);
        @endphp

        <div class="infos">
            <div class="nom {{ $classeNom }}">{{ $adherent->nomComplet() }}</div>

            <div class="champ-label">Matricule</div>
            <div class="champ-valeur">{{ $adherent->matricule }}</div>

            <div class="champ-label">Fonction</div>
            <div @class(['champ-valeur', 'compact' => $unites($fonctionCarte) > 27])>{{ $fonctionCarte }}</div>

            <div class="champ-label">Ville</div>
            <div @class(['champ-valeur', 'compact' => $unites($villeCarte) > 27])>{{ $villeCarte }}</div>

            <div class="champ-label">Contact</div>
            <div class="champ-valeur gras">{{ $adherent->telephone ?: '—' }}</div>
        </div>

        @if ($qr)
            <div class="qr-zone">
                <img src="{{ $qr }}" alt="QR code solde de cotisation">
                <div class="legende">Scanner pour<br>voir mon solde</div>
            </div>
        @endif

        <div class="bande-bas"></div>
    </div>

    <!-- VERSO -->
    <div class="carte">
        <div class="fond-verso"></div>

        <div class="verso-entete">
            MUTUELLE DES INSTITUTEURS ET ASSIMILÉS DE CÔTE D'IVOIRE
        </div>

        @php
            $versoDense = mb_strlen($adherent->nomComplet()) > 30
                || mb_strlen((string) $adherent->ayant_droit_nom) > 26;
        @endphp

        <div @class(['verso-corps', 'dense' => $versoDense])>
            <p><strong>Adhérent(e) tuteur :</strong> {{ $adherent->nomComplet() }}</p>
            <p><strong>Matricule :</strong> {{ $adherent->matricule }}</p>
            <p><strong>Adhésion :</strong> {{ $adherent->date_adhesion->format('d/m/Y') }}</p>

            <div class="titre-section">Ayant droit</div>
            @if ($adherent->aUnAyantDroit())
                <p>{{ $adherent->ayant_droit_nom }}<br><strong>{{ $adherent->ayant_droit_telephone }}</strong></p>
            @else
                <p>Non renseigné</p>
            @endif
        </div>

        <div class="verso-contact">
            <div class="titre-section">En cas de perte</div>
            <p>Merci de contacter la mutuelle :</p>
            <p><strong>07 08 15 08 30 / 01 03 38 01 10</strong><br>mutuellemiaci@hotmail.com</p>
        </div>

        @if ($cachet)
            <div class="verso-cachet">
                <div class="verso-cachet-cellule">
                    <img src="{{ $cachet }}" alt="Cachet et signature MIACI">
                </div>
            </div>
        @endif

        <div class="verso-pied">
            Cette carte est strictement personnelle et non cessible.
        </div>
    </div>
</body>
</html>
