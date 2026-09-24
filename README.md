# MIACI — Application de gestion de la mutuelle

Application web de gestion des adhérents, des cotisations et des demandes de
prise en charge (sinistres) pour la Mutuelle des Instituteurs et Assimilés de
Côte d'Ivoire (MIACI).

## Sommaire

- [Stack technique](#stack-technique)
- [Modules](#modules)
- [Installation locale](#installation-locale)
- [Comptes de démonstration](#comptes-de-démonstration)
- [Paramétrage du règlement de la mutuelle](#paramétrage-du-règlement-de-la-mutuelle)
- [Paiement en ligne (CinetPay)](#paiement-en-ligne-cinetpay)
- [Emails](#emails)
- [Tests](#tests)
- [Sécurité](#sécurité)
- [Déploiement](#déploiement)
- [Limites connues / prochaines étapes](#limites-connues--prochaines-étapes)

## Stack technique

- **Laravel 13** (PHP 8.3+), **MySQL**
- **Livewire 3 + Volt** (Breeze) pour l'interface, **Tailwind CSS**
- **Spatie Laravel-Permission** pour les rôles (ADMIN, GESTIONNAIRE, ADHERENT)
- **barryvdh/laravel-dompdf** pour les reçus de cotisation en PDF
- **maatwebsite/laravel-excel** pour les exports CSV/Excel
- **Rappasoft Livewire Tables** pour les listes de l'administration (recherche,
  filtres, tri, pagination, export) — voir [docs/DATATABLES.md](docs/DATATABLES.md)
- Fiche adhérent restructurée et modification par sections (profil / adhésion / sécurité) —
  voir [docs/FICHE-ADHERENT.md](docs/FICHE-ADHERENT.md)
- Passerelle de paiement **CinetPay**, intégrée derrière une interface
  `PaymentGatewayInterface` découplée (voir plus bas)

## Modules

1. **Authentification & rôles** — connexion par email/mot de passe,
   réinitialisation de mot de passe, trois rôles (ADMIN, GESTIONNAIRE,
   ADHERENT), interface entièrement en français.
2. **Adhérents** — fiche complète, recherche/filtres, activation/suspension/
   radiation, import CSV en masse, création automatique du compte adhérent
   (email d'invitation à définir un mot de passe), personnes à charge
   déclarées par l'adhérent et validées par un gestionnaire.
3. **Cotisations** — paramétrage du montant/de la fréquence par un
   administrateur, enregistrement manuel des paiements, calcul du solde
   (dû/payé/reste à payer), reçus PDF, tableau de bord avec adhérents en
   retard.
4. **Paiement en ligne** — l'adhérent paie sa cotisation via CinetPay
   (Orange Money, MTN Money, Moov Money, Wave, carte bancaire) ; la
   cotisation n'est enregistrée qu'après confirmation serveur-à-serveur
   (jamais depuis le retour navigateur), avec un journal des transactions.
5. **Sinistres** — types de sinistre paramétrables (plafond, délai de
   carence, cotisation à jour requise, pièces justificatives requises),
   soumission par l'adhérent avec pré-vérification d'éligibilité indicative,
   examen et décision par un gestionnaire (approuver/rejeter/demander un
   complément), notification email à chaque changement de statut.
6. **Tableau de bord, exports & audit** — indicateurs clés pour les
   gestionnaires, export Excel des adhérents/cotisations/sinistres, journal
   d'audit des actions sensibles (décisions sur les sinistres, cotisations
   enregistrées, changements de statut adhérent), consultable par un
   administrateur.

## Installation locale

### Prérequis

- PHP 8.3+ avec les extensions habituelles de Laravel
- Composer
- Node.js 18+ et npm
- MySQL 8+ (ou MariaDB équivalent)

### Étapes

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate
```

Modifier `.env` si besoin (par défaut : base `miaci` sur `127.0.0.1:3306`,
utilisateur `root` sans mot de passe — adapter selon votre installation
MySQL locale). Créer la base :

```sql
CREATE DATABASE miaci CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Puis :

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

L'application est accessible sur `http://127.0.0.1:8000`.

Pour le développement avec rechargement à chaud des assets :

```bash
npm run dev
```

## Comptes de démonstration

Le seeder crée les comptes suivants (mot de passe : `password` pour tous) :

| Rôle | Email | Notes |
|---|---|---|
| Administrateur | `admin@miaci.ci` | Accès complet, y compris paramétrage des règles de sinistre |
| Gestionnaire | `gestionnaire@miaci.ci` | Gestion quotidienne (adhérents, cotisations, sinistres) |
| Adhérent | `aya.koffi@miaci.ci` | À jour de cotisation, une demande de sinistre approuvée |
| Adhérent | `ibrahim.ouattara@miaci.ci` | En retard de cotisation, une demande en cours d'examen |
| Adhérent | `fatoumata.bamba@miaci.ci` | Aucun paiement, une demande rejetée (carence non respectée) |
| Adhérent | `jean.yao@miaci.ci` | Auto-inscrit, en attente de validation par un gestionnaire |

Une vingtaine d'adhérents supplémentaires (sans compte de connexion) sont
également créés pour peupler les listes et la recherche.

## Inscription des adhérents

Un adhérent peut créer lui-même son compte depuis `/inscription` (lien
« S'inscrire » sur la page de connexion et la page d'accueil). Sa fiche est
alors créée avec le statut **« En attente de validation »** : il peut se
connecter et compléter son profil, mais ne peut ni soumettre de demande de
sinistre ni payer de cotisation en ligne tant qu'un gestionnaire n'a pas
vérifié son dossier (matricule provisoire à corriger, etc.) et activé son
compte depuis *Adhérents → sa fiche → Actif*.

Un gestionnaire peut aussi continuer à créer directement une fiche adhérent
« Actif » (avec ou sans compte de connexion associé), comme décrit dans le
module Adhérents ci-dessus — les deux parcours coexistent.

## Paramétrage du règlement de la mutuelle

**Les valeurs ci-dessous (montant de cotisation, types de sinistre, plafonds,
délais de carence) sont des exemples de démonstration.** Le moteur de règles
a été conçu pour être entièrement reconfigurable depuis l'interface
d'administration, sans toucher au code, une fois le règlement définitif de
la mutuelle communiqué :

- **Cotisation** : menu *Administration → Paramètres cotisation* (montant et
  fréquence — mensuelle, trimestrielle, annuelle). Chaque changement est
  historisé.
- **Types de sinistre** : menu *Administration → Types de sinistre*. Pour
  chaque type : plafond de prise en charge, délai de carence (en mois),
  obligation d'être à jour de cotisation, et liste des pièces justificatives
  requises (avec indicateur obligatoire/optionnel).

La pré-vérification d'éligibilité (`App\Services\Sinistre\EligibiliteService`)
lit ce paramétrage dynamiquement ; aucune règle métier n'est codée en dur.

## Paiement en ligne (CinetPay)

L'intégration est isolée derrière `App\Contracts\PaymentGatewayInterface`
(implémentation actuelle : `App\Services\Payment\CinetPayGateway`), afin de
pouvoir changer de prestataire ou en ajouter un second (ex. PayDunya) sans
modifier `PaymentService` ni les écrans.

Pour l'activer, renseigner dans `.env` :

```
CINETPAY_API_KEY=...
CINETPAY_SITE_ID=...
CINETPAY_SECRET_KEY=...
CINETPAY_NOTIFY_URL="${APP_URL}/paiement/webhook/cinetpay"
CINETPAY_RETURN_URL="${APP_URL}/paiement/retour"
```

Sans ces clés, le bouton « Payer en ligne » de l'espace adhérent affichera un
message d'indisponibilité plutôt qu'une fausse confirmation de paiement.

**Important** : la forme exacte des champs de l'API CinetPay (v2 Checkout)
utilisée dans `CinetPayGateway` a été implémentée à partir de leur
documentation publique au moment de l'écriture. Elle est à revérifier contre
la documentation à jour (https://docs.cinetpay.com) une fois de vraies clés
API obtenues, avant toute mise en production — l'intégration ne valide
jamais un paiement à partir du seul contenu du webhook : elle interroge
systématiquement l'API de vérification de CinetPay en serveur-à-serveur.

## Emails

Par défaut (`MAIL_MAILER=log`), les emails (invitation adhérent,
réinitialisation de mot de passe, notification de sinistre) sont écrits dans
`storage/logs/laravel.log` plutôt qu'envoyés réellement. Pour les visualiser
dans un client de messagerie local, si vous utilisez Laragon, activez
Mailpit puis dans `.env` :

```
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
```

et consultez `http://127.0.0.1:8025`.

## Tests

```bash
php artisan test
```

La suite (base SQLite en mémoire, indépendante de la base MySQL de
développement) couvre : authentification et rôles, CRUD adhérents/personnes
à charge, calcul de solde et cotisations, paiement en ligne (webhook simulé
via `Http::fake()`, y compris les cas d'échec et l'idempotence), soumission
et décision de sinistres (moteur d'éligibilité), contrôle d'accès aux pièces
justificatives, exports et journal d'audit.

## Sécurité

- Mots de passe hashés (bcrypt), protection CSRF sur toutes les routes web
  (le webhook de paiement en est explicitement exempté car appelé par un
  serveur tiers, sa fiabilité repose sur la vérification serveur-à-serveur).
- Autorisations vérifiées par des *policies* Laravel pour chaque entité
  (un adhérent ne voit que ses propres données ; un gestionnaire ne peut pas
  configurer les règles de sinistre, réservé à l'administrateur).
- Les pièces justificatives de sinistre sont stockées sur un disque **non
  public** (`storage/app/private`) et ne sont accessibles que via une route
  authentifiée qui vérifie l'autorisation (adhérent concerné ou
  gestionnaire) avant de servir le fichier.
- Les champs sensibles (description des circonstances d'un sinistre, motifs)
  sont exclus des exports Excel en masse et ne restent consultables que sur
  la fiche détaillée, pour limiter leur diffusion.

## Déploiement

Le déploiement est automatisé par GitHub Actions (CI sur chaque PR, puis
déploiement SSH vers le cPanel à chaque push sur `main`) : voir
[docs/CI-CD.md](docs/CI-CD.md) pour la mise en route, le `.env` de
production, le retour arrière et le dépannage.

Procédure manuelle équivalente, sur un hébergement mutualisé classique
(PHP + MySQL) :

1. Déployer le code (le dossier `public/` doit être la racine web, ou
   configurer un alias/`.htaccess` selon l'hébergeur).
2. `composer install --no-dev --optimize-autoloader`
3. `npm install && npm run build` (ou déployer le dossier `public/build`
   déjà construit).
4. Copier `.env.example` vers `.env`, renseigner les accès MySQL réels,
   `APP_URL`, les clés CinetPay, la configuration SMTP, puis
   `php artisan key:generate`.
5. `php artisan migrate --force` (et `--seed` uniquement pour un premier
   jeu de données de démonstration — à éviter en production réelle, créer
   plutôt un compte administrateur dédié).
6. `php artisan storage:link`
7. `php artisan config:cache && php artisan route:cache && php artisan view:cache`
8. Configurer l'URL de notification CinetPay (`CINETPAY_NOTIFY_URL`) pour
   qu'elle pointe vers `https://votre-domaine/paiement/webhook/cinetpay`.

## Limites connues / prochaines étapes

- Les notifications email sont envoyées de façon synchrone. Pour un volume
  important, activer une file d'attente (`QUEUE_CONNECTION=database` est
  déjà configuré) en ajoutant `ShouldQueue` à
  `App\Notifications\StatutDemandeSinistreModifie` et en exécutant
  `php artisan queue:work` (ou un worker supervisé) en production.
- Un seul prestataire de paiement (CinetPay) est implémenté ; l'ajout d'un
  second (ex. PayDunya) consiste à créer une nouvelle classe implémentant
  `PaymentGatewayInterface` et à l'enregistrer dans
  `AppServiceProvider::register()`.
- Les montants de cotisation et de sinistre sont stockés en FCFA (entiers,
  pas de décimales), conformément à l'usage courant en Côte d'Ivoire.
