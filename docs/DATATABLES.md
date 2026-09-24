# Tables de données de l'administration

Toutes les listes de l'administration (adhérents, cotisations, paiements,
sinistres, journal d'audit, dons de fin d'année, types de sinistre) reposent
sur **un seul composant réutilisable**, `App\Livewire\Tables\DataTable`, bâti
sur [Rappasoft Laravel Livewire Tables](https://rappasoft.com/docs/laravel-livewire-tables)
et habillé aux couleurs de l'application.

## Sommaire

- [Ce que fait une table](#ce-que-fait-une-table)
- [Pourquoi Rappasoft](#pourquoi-rappasoft)
- [Où se trouve quoi](#où-se-trouve-quoi)
- [Créer une nouvelle table](#créer-une-nouvelle-table)
- [Référence du composant de base](#référence-du-composant-de-base)
- [Colonnes](#colonnes)
- [Filtres](#filtres)
- [Design](#design)
- [Tests](#tests)
- [Pièges rencontrés](#pièges-rencontrés)
- [Périmètre et limites](#périmètre-et-limites)

## Ce que fait une table

| Fonction | Détail |
|---|---|
| **Recherche** | Champ unique, différé de 400 ms, porté par les colonnes déclarées `searchable` |
| **Tri** | Clic sur un en-tête (croissant / décroissant), tri par défaut par table |
| **Filtres** | Affichés en permanence **sur la même ligne que la recherche** (chacun avec son libellé) : listes, dates, etc. Bouton « Effacer » dès qu'une recherche ou un filtre est actif |
| **Pagination** | Pied de page sur une seule ligne : « Affichage de 1 à 15 sur 54 résultat(s) » à gauche, **« Lignes par page »** (10 / 15 / 25 / 50 / 100, 15 par défaut) et navigation numérotée à droite |
| **Colonnes masquables** | Bouton « Colonnes affichées », choix mémorisé pour la session. Colonnes secondaires repliées avec un « + » par ligne sur mobile |
| **Sélection + export** | Cases à cocher ; « Actions sur la sélection » n'apparaît qu'une fois des lignes cochées, avec « Exporter la sélection (Excel) » : le fichier contient les colonnes **affichées** à l'écran |
| **URL partageable** | Recherche, filtres, tri et taille de page sont dans l'URL (`?table-search=…`) : une vue filtrée se partage et survit à un rechargement |
| **Actions de ligne** | Colonne « Actions » avec en-tête : un bouton **Actions** ouvre un menu déroulant (icône + nom de chaque action), liens ou boutons avec confirmation ; retour affiché au-dessus de la table |
| **Contrôle d'accès** | Vérifié à **chaque** requête Livewire, pas seulement à l'ouverture de la page |
| **Responsive** | Barre d'outils empilée, colonnes secondaires repliées, défilement horizontal si besoin |

## Pourquoi Rappasoft

Le projet est en Laravel 13 + Livewire 3 + Tailwind 3. Les options examinées :

- **Rappasoft Laravel Livewire Tables (retenu)** — le plus complet pour Livewire 3 :
  recherche, tri, filtres typés, pagination, colonnes masquables, sélection et
  actions groupées, URL, traductions françaises, vues Tailwind entièrement
  publiables donc personnalisables. Compatible Laravel 13 (v3.8).
- **DataTables.js** — côté navigateur (jQuery) : mal adapté à des données
  paginées côté serveur et à Livewire.
- **Filament Tables** — excellent mais impose tout l'écosystème Filament.
- **PowerGrid** — bon candidat, mais moins de filtres prêts à l'emploi et un
  moteur d'export supplémentaire alors que `maatwebsite/excel` est déjà là.

## Où se trouve quoi

```
app/Livewire/Tables/DataTable.php              ← composant de base réutilisable
app/Exports/DataTableExport.php                ← export Excel générique de la sélection
app/Livewire/Gestion/<Module>/<Nom>Table.php   ← une table par liste (colonnes, filtres, actions)
app/Livewire/Gestion/<Module>/Index.php        ← la page : en-tête + <livewire:…-table />
resources/views/components/badge.blade.php     ← pastille de statut (<x-badge couleur="green">)
resources/views/livewire/tables/               ← cellules communes : actions, messages
resources/views/vendor/livewire-tables/        ← vues du paquet, publiées et habillées
lang/vendor/livewire-tables/fr/core.php        ← surcharge des libellés français
config/livewire-tables.php                     ← configuration du paquet
```

| Page | Table |
|---|---|
| Adhérents tuteurs | `Gestion\Adherents\AdherentsTable` |
| Cotisations | `Gestion\Cotisations\SoldesTable` |
| Paiements | `Gestion\Paiements\TransactionsTable` |
| Sinistres | `Gestion\Sinistres\DemandesTable` |
| Journal d'audit | `Gestion\Audit\JournalTable` |
| Dons de fin d'année | `Gestion\DonsFinAnnee\DonsTable` |
| Types de sinistre | `Gestion\Parametres\TypesSinistre\TypesTable` |

Une **page** (`Index`) ne porte plus que son en-tête, ses boutons et ses cartes
d'information ; la **table** porte tout ce qui touche à la liste, y compris les
actions de ligne (supprimer, marquer versé, activer / désactiver).

## Créer une nouvelle table

Exemple : lister des `Partenaire`.

**1. La table** — `app/Livewire/Gestion/Partenaires/PartenairesTable.php`

```php
<?php

namespace App\Livewire\Gestion\Partenaires;

use App\Livewire\Tables\DataTable;
use App\Models\Partenaire;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class PartenairesTable extends DataTable
{
    protected string $placeholderRecherche = 'Nom, ville…';
    protected string $messageVide = 'Aucun partenaire.';
    protected string $prefixeExport = 'partenaires';

    protected function autoriser(): void
    {
        $this->authorize('viewAny', Partenaire::class);
    }

    protected function configurer(): void
    {
        $this->setDefaultSort('nom', 'asc');
    }

    public function builder(): Builder
    {
        return Partenaire::query();
    }

    public function columns(): array
    {
        return [
            Column::make('Nom', 'nom')->sortable()->searchable(),
            Column::make('Ville', 'ville')->sortable()->searchable(),
            Column::make('Statut', 'actif')
                ->label(fn (Partenaire $p) => $this->badge($p->actif ? 'Actif' : 'Inactif', $p->actif ? 'green' : 'gray'))
                ->html()
                ->sortable(),
            $this->colonneActions(fn (Partenaire $p) => [
                ['libelle' => 'Modifier', 'icone' => 'pencil-square', 'url' => route('gestion.partenaires.modifier', $p)],
            ]),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Statut', 'actif')
                ->options(['' => 'Tous', '1' => 'Actifs', '0' => 'Inactifs'])
                ->filter(fn (Builder $q, string $valeur) => $q->where('actif', $valeur === '1')),
        ];
    }
}
```

**2. La page** — la vue de la page contient simplement :

```blade
<livewire:gestion.partenaires.partenaires-table />
```

(le nom Livewire est le chemin de la classe en minuscules avec des points et des
tirets : `Gestion\Partenaires\PartenairesTable` → `gestion.partenaires.partenaires-table`).

**3. Un test** — copier `tests/Feature/Gestion/Tables/AutresTablesTest.php` et
adapter : accès, affichage, recherche, un filtre, une action.

C'est tout : pagination, tri, colonnes masquables, sélection, export, design,
messages et contrôle d'accès viennent du composant de base.

## Référence du composant de base

### À déclarer dans chaque table

| Méthode | Rôle |
|---|---|
| `autoriser()` | Qui peut voir la table. Appelée à **chaque** requête. `$this->authorize(...)` ou `abort_unless(...)` |
| `builder()` | Requête Eloquent de départ (jointures inutiles : voir [Pièges](#pièges-rencontrés)) |
| `columns()` | Les colonnes |
| `filters()` | Les filtres (facultatif) |

### Réglages facultatifs (propriétés)

| Propriété | Défaut | Rôle |
|---|---|---|
| `$placeholderRecherche` | `Rechercher…` | Texte d'aide du champ de recherche |
| `$messageVide` | `Aucun résultat.` | Message quand rien ne correspond |
| `$parPage` | `15` | Lignes par page à l'ouverture (parmi 10/15/25/50/100) |
| `$exportable` | `true` | `false` : ni cases à cocher ni export (pages de paramétrage) |
| `$prefixeExport` | `export` | Préfixe du fichier Excel (suivi de la date) |
| `$clePrimaire` | `id` | Clé primaire de la requête |

Pour un réglage plus fin, surcharger `configurer()` (appelée après les réglages
communs) : `setDefaultSort()`, `setFilterLayoutPopover()`, `setConfigurableArea()`…
Voir la [documentation du paquet](https://rappasoft.com/docs/laravel-livewire-tables).

### Aides fournies

| Aide | Usage |
|---|---|
| `$this->badge($texte, $couleur)` | Pastille : `blue`, `green`, `yellow`, `red`, `orange`, `gray`. Le texte est échappé |
| `$this->colonneActions(fn ($ligne) => [...])` | Colonne « Actions » (menu déroulant). Chaque action : `['libelle' => ..., 'icone' => 'eye', 'url' => ...]` (lien) ou `['libelle' => ..., 'icone' => 'trash', 'wire' => 'methode(id)', 'confirmation' => ..., 'couleur' => 'red']` (bouton). `icone` : nom d'une icône [Heroicons](https://heroicons.com) « outline » ; `couleur => 'red'` = action destructive, séparée des autres et en rouge. Liste vide : rien n'est affiché |
| `$this->colonneAdherent()` | Colonne « Adhérent tuteur » pour un modèle qui a `adherent()` : nom complet, recherche et tri par nom |
| `$this->fcfa($montant)` | `13 500 FCFA` |

### Export de la sélection

L'action « Exporter la sélection (Excel) » exige la permission `exporter_donnees`
et reprend, pour les lignes cochées, **les colonnes actuellement affichées**
(en-têtes et valeurs en texte, sans HTML). Les colonnes d'actions n'en font pas
partie. Elle est indépendante des exports intégraux du menu (`ExportController`).

## Colonnes

```php
// Champ simple, triable, dans la recherche
Column::make('Matricule', 'matricule')->sortable()->searchable(),

// Mise en forme d'une valeur (texte simple : échappé par le paquet)
Column::make('Adhésion', 'date_adhesion')->format(fn ($valeur) => $valeur?->format('d/m/Y')),

// Contenu calculé ou riche : label() + html() (la vue doit échapper ses données)
Column::make('Statut', 'statut')
    ->label(fn (Adherent $a) => $this->badge($a->statut->libelle(), $a->statut->couleur()))
    ->html(),

// Tri ou recherche personnalisés (relation, calcul SQL, plusieurs champs)
Column::make('Nom', 'nom')
    ->sortable(fn (Builder $q, string $sens) => $q->orderBy('nom', $sens)->orderBy('prenom', $sens))
    ->searchable(fn (Builder $q, string $terme) => $q->orWhere(fn ($w) => $w->recherche($terme))),

// Masquée par défaut / repliée sur mobile
Column::make('Téléphone', 'telephone')->deselected()->collapseOnTablet(),
```

Règle de recherche : une colonne `searchable(callback)` doit utiliser
`orWhere` (les conditions des colonnes sont combinées en OU).

## Filtres

```php
SelectFilter::make('Statut', 'statut')
    ->options(['' => 'Tous'] + [...])
    ->filter(fn (Builder $q, string $valeur) => $q->where('statut', $valeur)),

DateFilter::make('Adhésion à partir du', 'adhesion_depuis')
    ->filter(fn (Builder $q, string $date) => $q->whereDate('date_adhesion', '>=', $date)),
```

Le premier argument est le libellé, le second la clé (unique dans la table). La
clé sert dans les tests : `->set('filterComponents.statut', 'actif')`.

Pour un filtre qui repose sur un **calcul métier** plutôt que sur une colonne
(cotisation « à jour », éligibilité au don de fin d'année), calculer d'abord la
liste des identifiants concernés avec le service, puis filtrer par
`whereIn('id', $ids)` — voir `SoldesTable` et `DonsTable`.

## Design

Le design vit dans **les vues publiées** `resources/views/vendor/livewire-tables/`,
pas dans le paquet : elles sont versionnées avec le projet. Personnalisations
appliquées par rapport au paquet :

- palette `indigo` / `blue` du paquet remplacée par `primary` (orange) ;
- barre d'outils dans une carte `rounded-2xl`, tableau dans une carte
  `rounded-2xl`, contrôles `rounded-xl`, comme le reste de l'application ;
- en-têtes `text-xs uppercase`, cellules `px-4 py-3`, survol de ligne ;
- pagination : page courante en orange ; état vide illustré ;
- barre d'outils sur une ligne : recherche et filtres côte à côte (qui passent à la ligne sur écran étroit), choix des colonnes à droite ; pas de bouton « Filtres », pas de pastilles de tri ni de filtres ;
- taille de page déplacée dans le pied de page, à côté de la navigation ;
- libellés français corrigés dans `lang/vendor/livewire-tables/fr/core.php`
  (« Affichage de 1 à 15 sur 54 résultat(s) »).

Pourquoi publier les vues plutôt que de configurer le paquet ? Le build Vite du
déploiement (`xsel-deploy-mutualise`) s'exécute **sans** `composer install` :
Tailwind ne peut donc pas lire les vues du dossier `vendor/`. Les classes
utilisées doivent se trouver dans `resources/views/` pour être compilées.

**Modifier l'apparence** : éditer le fichier concerné dans
`resources/views/vendor/livewire-tables/` (par exemple
`components/table/td.blade.php` pour les cellules), puis `npm run build`.

**Mettre à jour le paquet** (`composer update rappasoft/laravel-livewire-tables`) :
les vues publiées ne suivent pas automatiquement. Après une montée de version,
comparer les vues du paquet aux nôtres (`diff -r vendor/rappasoft/laravel-livewire-tables/resources/views resources/views/vendor/livewire-tables`),
reporter les correctifs utiles, lancer les tests et parcourir chaque liste.

## Tests

- `tests/Feature/Gestion/Tables/AdherentsTableTest.php` — la table de référence :
  affichage, accès, recherche, filtres, tri, pagination, suppression, export.
- `tests/Feature/Gestion/Tables/AutresTablesTest.php` — les six autres tables.

Commandes utiles avec `Livewire::actingAs($user)->test(MaTable::class)` :

```php
->set('search', 'Traore')                       // recherche
->set('filterComponents.statut', 'actif')       // filtre
->call('sortBy', 'nom')                         // tri (slug de la colonne)
->set('perPage', 10)                            // taille de page
->set('selected', ['12'])->call('exporterSelection')->assertFileDownloaded()
$table->instance()->getRows()                   // lignes retournées (paginateur)
```

Attention : le panneau de filtres contient toutes les valeurs possibles dans
ses `<option>`. Pour vérifier **quelles lignes** sont retournées, préférer
`getRows()` à `assertSee` / `assertDontSee` sur un texte qui peut aussi être une
option de filtre.

## Pièges rencontrés

- **Cellules riches et colonnes sélectionnées.** Le paquet ne charge que les
  champs déclarés dans `Column::make('…', 'champ')`. Le composant de base
  sélectionne donc toute la ligne du modèle (`selectFields()`), sans quoi une
  pastille lisant `$modele->statut` recevrait `null`. Éviter les jointures dans
  `builder()` : préférer `with()` et des tris / recherches par sous-requête
  (voir `colonneAdherent()`).
- **Alias `withCount()`.** `demandes_count` n'est pas une colonne : la déclarer
  avec `label()` et un `sortable(callback)` (voir `TypesTable`).
- **Taille de page par défaut.** Le paquet la choisit avant `configure()` ; le
  composant de base applique donc la configuration dans `mountWithPagination()`.
  Sans cela la table s'ouvrirait à 10 lignes.
- **Menu « Actions » dans un conteneur à défilement.** La table est dans un
  conteneur `overflow-x-auto` qui rognerait un menu `absolute` sur les dernières
  lignes. Le menu (composant `<x-menu>`, `components/menu.blade.php`, utilisé par
  `livewire/tables/actions.blade.php`) est donc en position `fixed`, calculée depuis
  le bouton (Alpine), placé au-dessus du bouton s'il manque de place. Il suit le bouton au défilement au lieu de se fermer : des événements
  de défilement parasites le refermaient juste après l'ouverture. Après un refus de
  `wire:confirm`, le menu reste ouvert (le clic est interrompu par Livewire).
- **Filtres et barre d'outils.** Les filtres ne sont plus rendus par le panneau déroulant du paquet mais par `components/tools/toolbar.blade.php`. `setFilterLayoutSlideDown()` reste appelé : il fournit le style des libellés de filtres.
- **Zones configurables.** `setConfigurableArea('after-tools', 'ma.vue')` ajoute
  une zone ; `setConfigurableAreas([...])` **remplace** toutes les zones (celle
  des messages comprise).
- **`<x-icon>`.** `blade-ui-kit/blade-icons` (dépendance du paquet) réservait
  ce nom et masquait le composant maison `resources/views/components/icon.blade.php`.
  Son composant générique est renommé `blade-icon` dans `config/blade-icons.php`.
  Ne pas remettre `icon`.
- **Messages flash.** Les actions de ligne s'exécutent dans la table : c'est elle
  qui affiche `session('status')` / `session('erreur')`. Ne pas les répéter dans
  la page qui l'héberge (double affichage).
- **`route:cache`.** Vérifié : le paquet enregistre des routes sérialisables,
  compatibles avec le `route:cache` du déploiement.

## Largeur des pages

Toutes les pages d'administration (`resources/views/livewire/gestion/**`) et le
tableau de bord utilisent le même conteneur, `max-w-7xl mx-auto sm:px-6 lg:px-8`,
identique à celui de l'en-tête (`layouts/app.blade.php`). Une nouvelle page
d'administration doit reprendre exactement ce conteneur.

## Périmètre et limites

Convertis : les sept listes ci-dessus (pages réservées aux administrateurs et
gestionnaires).

Volontairement **non convertis** :

- les petits tableaux intégrés à une fiche (personnes à charge, cotisations
  d'un adhérent), à l'historique du paramétrage de cotisation et au comparatif
  du bilan annuel : ce sont des extraits de quelques lignes avec des actions
  propres, pas des listes à explorer ;
- les pages de l'espace adhérent (`mon-espace/…`), qui ne relèvent pas de
  l'administration. Le composant se réutilise tel quel si besoin ;
- l'export « tout le résultat filtré » : seul l'export de la **sélection** est
  proposé (les exports intégraux du menu existent déjà).

Comportements à connaître :

- les colonnes **calculées** (dû / payé / reste, personnes dans le carnet) ne
  sont pas triables : elles ne sont pas stockées en base ;
- le filtre « Situation » des cotisations et la liste des dons calculent
  l'éligibilité de tous les adhérents actifs à chaque affichage (comme avant la
  refonte). À surveiller si le nombre d'adhérents devient très grand.
