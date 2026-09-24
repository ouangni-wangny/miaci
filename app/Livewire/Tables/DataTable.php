<?php

namespace App\Livewire\Tables;

use App\Exports\DataTableExport;
use App\Models\Adherent;
use BackedEnum;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Maatwebsite\Excel\Facades\Excel;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Table de données de l'application (recherche, tri, filtres, pagination,
 * colonnes masquables, sélection et export) — base commune de toutes les
 * listes de l'administration. Voir docs/DATATABLES.md.
 *
 * Une table concrète n'a que trois choses à déclarer :
 *   - autoriser()  : qui a le droit de voir la table ;
 *   - builder()    : la requête Eloquent de départ ;
 *   - columns()    : les colonnes (et filters() s'il y a des filtres).
 * Tout le reste — réglages, design, messages, export — vit ici et dans les
 * vues resources/views/vendor/livewire-tables.
 *
 * Réglages propres à une table : surcharger configurer() (tri par défaut,
 * texte du champ de recherche...). Les propriétés ci-dessous couvrent les
 * cas courants.
 */
abstract class DataTable extends DataTableComponent
{
    /** Colonne (de la requête) qui sert de clé primaire. */
    protected string $clePrimaire = 'id';

    /** Texte d'aide du champ de recherche. */
    protected string $placeholderRecherche = 'Rechercher…';

    /** Message affiché quand aucun résultat ne correspond. */
    protected string $messageVide = 'Aucun résultat.';

    /** Nombre de lignes par page à l'ouverture. */
    protected int $parPage = 15;

    /** Propose « Exporter la sélection » (permission exporter_donnees). */
    protected bool $exportable = true;

    /** Préfixe du fichier Excel exporté (suivi de la date). */
    protected string $prefixeExport = 'export';

    /**
     * Contrôle d'accès : appelé à chaque requête Livewire (pas seulement au
     * premier affichage), pour qu'un droit retiré en cours de session
     * s'applique immédiatement. Lever une AuthorizationException / abort(403).
     */
    abstract protected function autoriser(): void;

    public function booted(): void
    {
        parent::booted();

        $this->autoriser();
    }

    /**
     * Rappasoft choisit la taille de page au « mount », donc avant que
     * configure() (lancé au « booted ») n'ait déclaré les tailles acceptées
     * et la taille par défaut : sans ceci, la table s'ouvrirait toujours avec
     * la valeur du paquet (10) au lieu de $parPage.
     */
    public function mountWithPagination(): void
    {
        $this->runCoreConfiguration();

        parent::mountWithPagination();
    }

    public function configure(): void
    {
        $this->setPrimaryKey($this->clePrimaire);

        // Ergonomie : recherche différée pour ne pas interroger la base à
        // chaque frappe, filtres dans un panneau déroulant, pagination
        // choisie par l'utilisateur, colonnes masquables (choix mémorisé).
        $this->setSearchDebounce(400);
        $this->setSearchPlaceholder($this->placeholderRecherche);
        $this->setEmptyMessage($this->messageVide);
        // Les filtres sont affichés en permanence, à côté de la recherche
        // (resources/views/vendor/livewire-tables/components/tools/toolbar.blade.php) :
        // ni bouton « Filtres », ni panneau, ni pastilles de filtres appliqués.
        $this->setFilterLayoutSlideDown();
        $this->setFilterPillsDisabled();

        // Les flèches des en-têtes indiquent déjà le tri en cours : la
        // pastille « Tris appliqués » ferait doublon (le tri par défaut y
        // apparaîtrait dès l'ouverture).
        $this->setSortingPillsDisabled();
        $this->setPerPageAccepted([10, 15, 25, 50, 100]);
        $this->setDefaultPerPage($this->parPage);
        $this->setColumnSelectEnabled();
        $this->setColumnSelectHiddenOnMobile();
        $this->setRememberColumnSelectionEnabled();

        // Recherche, filtres et tri dans l'URL : la vue filtrée se partage
        // et survit à un rechargement.
        $this->setQueryStringEnabled();

        // « Actions sur la sélection » n'apparaît qu'une fois des lignes cochées.
        $this->setHideBulkActionsWhenEmptyEnabled();

        // Sans export, pas de cases à cocher inutiles.
        if (! $this->exportable) {
            $this->setBulkActionsDisabled();
        }

        // L'en-tête de la colonne « Actions » est aligné à droite, comme son bouton.
        $this->setThAttributes(fn (Column $colonne) => [
            'default' => true,
            'default-colors' => true,
            'default-styling' => true,
            'class' => $colonne->getSlug() === 'actions' ? 'text-right' : '',
        ]);

        // Retour des actions de ligne (voir livewire/tables/messages.blade.php).
        $this->setConfigurableArea('before-tools', 'livewire.tables.messages');

        $this->configurer();
    }

    /**
     * Par défaut Rappasoft ne sélectionne en base que les colonnes qui ont un
     * champ. Or les cellules « riches » (pastilles, actions, nom complet...)
     * lisent d'autres attributs du modèle : on sélectionne donc toute la ligne.
     */
    protected function selectFields(): Builder
    {
        $builder = $this->getBuilder();
        $this->setBuilder($builder->addSelect($builder->getModel()->qualifyColumn('*')));

        return parent::selectFields();
    }

    /**
     * Réglages propres à la table (setDefaultSort, setFilterLayoutPopover...).
     * Appelé après les réglages communs, qu'il peut donc surcharger.
     */
    protected function configurer(): void {}

    // ---------------------------------------------------------------------
    // Helpers de colonnes — les cellules « riches » sont des vues Blade qui
    // échappent leurs données ; ->html() n'est donc jamais appliqué à une
    // valeur brute venant de la base.
    // ---------------------------------------------------------------------

    /**
     * Pastille colorée (statut...). $couleur : blue|green|yellow|red|orange|gray.
     */
    protected function badge(string $texte, string $couleur = 'gray'): string
    {
        return view('components.badge', ['couleur' => $couleur, 'slot' => new HtmlString(e($texte))])->render();
    }

    /**
     * Colonne « Actions » d'une ligne : un menu déroulant (icône + nom de
     * chaque action). $actions reçoit la ligne et renvoie la liste des actions,
     * chacune avec 'libelle', 'icone' (Heroicons outline) et 'url' ou 'wire'
     * (voir livewire/tables/actions.blade.php).
     *
     * @param  callable(Model): array<int, array<string, string>>  $actions
     */
    protected function colonneActions(callable $actions, string $titre = 'Actions'): Column
    {
        return Column::make($titre)
            ->label(fn ($row) => view('livewire.tables.actions', ['actions' => $actions($row)])->render())
            ->html()
            ->excludeFromColumnSelect();
    }

    /**
     * Colonne « Adhérent tuteur » pour une table dont le modèle a une
     * relation adherent() : affiche le nom complet, se recherche avec le
     * scope Adherent::recherche() et se trie par nom d'adhérent.
     */
    protected function colonneAdherent(string $titre = 'Adhérent tuteur'): Column
    {
        return Column::make($titre)
            ->label(fn ($ligne) => $ligne->adherent?->nomComplet() ?? '—')
            ->sortable(fn (Builder $q, string $sens) => $q->orderBy(
                Adherent::select('nom')->whereColumn('adherents.id', $q->getModel()->qualifyColumn('adherent_id')),
                $sens
            ))
            ->searchable(fn (Builder $q, string $terme) => $q->orWhereHas(
                'adherent',
                fn (Builder $a) => $a->recherche($terme)
            ));
    }

    protected function fcfa(int|float|null $montant): string
    {
        return number_format((float) $montant, 0, ',', ' ').' FCFA';
    }

    // ---------------------------------------------------------------------
    // Export des lignes sélectionnées
    // ---------------------------------------------------------------------

    public function bulkActions(): array
    {
        if (! $this->exportable || ! auth()->user()?->can('exporter_donnees')) {
            return [];
        }

        return ['exporterSelection' => 'Exporter la sélection (Excel)'];
    }

    /**
     * Exporte les lignes cochées avec les colonnes actuellement affichées
     * (ce que l'utilisateur voit à l'écran est ce qu'il obtient dans Excel).
     */
    public function exporterSelection(): BinaryFileResponse
    {
        abort_unless(auth()->user()?->can('exporter_donnees'), 403);

        $identifiants = $this->getSelectedRows();
        $colonnes = $this->getSelectableSelectedColumns();

        $lignes = $this->baseQuery()
            ->whereIn($this->getBuilder()->getModel()->getQualifiedKeyName(), $identifiants)
            ->get()
            ->map(fn ($ligne) => $colonnes
                ->map(fn (Column $colonne) => $this->texteBrut($colonne->renderContents($ligne)))
                ->all())
            ->all();

        $entetes = $colonnes->map(fn (Column $colonne) => $colonne->getTitle())->all();

        $this->clearSelected();

        return Excel::download(
            new DataTableExport($entetes, $lignes),
            $this->prefixeExport.'-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    /** Texte d'une cellule, sans HTML (pastilles, liens...). */
    private function texteBrut(mixed $contenu): string
    {
        if ($contenu instanceof BackedEnum) {
            return (string) $contenu->value;
        }

        if ($contenu instanceof View) {
            $contenu = $contenu->render();
        }

        $texte = html_entity_decode(strip_tags((string) $contenu), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $texte));
    }
}
