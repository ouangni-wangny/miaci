{{-- Messages flash affichés au-dessus de tout DataTable (zone configurable
     « before-tools »). Les actions d'une ligne (supprimer, marquer versé...)
     s'exécutent dans le composant de la table : c'est donc elle, et non la
     page qui l'héberge, qui doit afficher leur retour. --}}
@if (session('status'))
    <div class="mb-4 bg-green-50 text-green-700 text-sm rounded-xl p-3" role="status">
        {{ session('status') }}
    </div>
@endif

@if (session('erreur'))
    <div class="mb-4 bg-red-50 text-red-700 text-sm rounded-xl p-3" role="alert">
        {{ session('erreur') }}
    </div>
@endif
