@php
    $statutCouleur = $adherent->statut->couleur();
    $initiales = strtoupper(mb_substr($adherent->prenom, 0, 1).mb_substr($adherent->nom, 0, 1));
    $photoUrl = $adherent->photo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($adherent->photo_path) : null;
    $pourcentagePaye = $solde['du'] > 0 ? min(100, (int) round($solde['paye'] / $solde['du'] * 100)) : 100;
    $droitAdhesionPaye = $adherent->droitAdhesionPaye();
    $moisImpayes = $periodesImpayees->count();
    $carenceTerminee = $dateFinCarence->isPast();
@endphp

<div>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <nav class="flex items-center gap-1.5 text-sm text-gray-500" aria-label="Fil d'Ariane">
                    <a href="{{ route('gestion.adherents.index') }}" wire:navigate class="hover:text-primary-600">Adhérents tuteurs</a>
                    <x-heroicon-m-chevron-right class="w-4 h-4 text-gray-300" />
                    <span class="text-gray-700">{{ $adherent->nomComplet() }}</span>
                </nav>
                <h2 class="mt-1 text-2xl font-bold text-gray-900">Fiche adhérent</h2>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('adherents.carte', $adherent) }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl shadow-sm hover:bg-gray-50 transition">
                    <x-heroicon-o-identification class="w-4 h-4" />
                    Carte de membre
                </a>

                <x-menu label="Modifier" icon="pencil-square" variant="primary" align="right">
                    <x-menu-item :href="route('gestion.adherents.modifier', ['adherent' => $adherent, 'section' => 'profil'])" icon="user">Profil et photo</x-menu-item>
                    <x-menu-item :href="route('gestion.adherents.modifier', ['adherent' => $adherent, 'section' => 'adhesion'])" icon="calendar-days">Adhésion et statut</x-menu-item>
                    <x-menu-item :href="route('gestion.adherents.fiche', ['adherent' => $adherent, 'onglet' => 'securite'])" icon="lock-closed" separated>Sécurité et accès</x-menu-item>
                </x-menu>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6"
             x-data="{
                onglet: ['apercu', 'cotisations', 'foyer', 'securite'].includes(new URLSearchParams(location.search).get('onglet'))
                    ? new URLSearchParams(location.search).get('onglet')
                    : 'apercu',
                aller(onglet) {
                    this.onglet = onglet;
                    const url = new URL(location.href);
                    url.searchParams.set('onglet', onglet);
                    history.replaceState(history.state, '', url);
                },
             }">

            @if (session('status'))
                <div class="flex items-start gap-3 bg-green-50 text-green-800 text-sm rounded-xl p-4" role="status">
                    <x-heroicon-o-check-circle class="w-5 h-5 shrink-0 text-green-600" />
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if (session('erreur'))
                <div class="flex items-start gap-3 bg-red-50 text-red-800 text-sm rounded-xl p-4" role="alert">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 shrink-0 text-red-600" />
                    <span>{{ session('erreur') }}</span>
                </div>
            @endif

            @if ($signalePourArrieres)
                <div class="flex items-start gap-3 bg-red-50 border border-red-100 text-red-800 text-sm rounded-xl p-4">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 shrink-0 text-red-600" />
                    <p>
                        <strong>Plus de 3 mois d'arriérés de cotisation.</strong>
                        Selon l'article 7 du règlement, cet adhérent peut perdre sa qualité de membre.
                        La radiation reste une décision manuelle : utilisez le menu « Statut » ci-dessous
                        (« Radié ») si le conseil d'administration a statué en ce sens.
                    </p>
                </div>
            @endif

            {{-- ====================== Identité ====================== --}}
            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="h-24 bg-gradient-to-r from-primary-600 via-primary-500 to-primary-400"></div>

                <div class="px-6 pb-6">
                    <div class="-mt-12 flex flex-wrap items-end justify-between gap-4">
                        @if ($photoUrl)
                            <img src="{{ $photoUrl }}" alt="Photo de {{ $adherent->nomComplet() }}" class="h-24 w-24 rounded-2xl object-cover ring-4 ring-white shadow-md">
                        @else
                            <span class="flex h-24 w-24 items-center justify-center rounded-2xl bg-primary-50 text-3xl font-bold text-primary-700 ring-4 ring-white shadow-md">{{ $initiales }}</span>
                        @endif

                        <div class="flex flex-wrap items-center gap-2 pb-1">
                            <x-menu :label="'Statut : '.$adherent->statut->libelle()" icon="arrow-path" align="right">
                                @foreach ($statuts as $s)
                                    @unless ($s === $adherent->statut)
                                        <x-menu-item wire:click="changerStatut('{{ $s->value }}')"
                                                     wire:confirm="Confirmer le passage au statut « {{ $s->libelle() }} » ?"
                                                     icon="arrow-path">
                                            Passer à « {{ $s->libelle() }} »
                                        </x-menu-item>
                                    @endunless
                                @endforeach
                            </x-menu>

                            <button type="button"
                                    x-on:click="aller('cotisations')"
                                    wire:click="ouvrirFormulaireCotisation"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-primary-500 border border-transparent rounded-xl shadow-sm hover:bg-primary-600 transition">
                                <x-heroicon-o-banknotes class="w-4 h-4" />
                                Enregistrer un paiement
                            </button>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                            <h1 class="text-2xl font-bold text-gray-900">{{ $adherent->nomComplet() }}</h1>
                            <x-badge :couleur="$statutCouleur">{{ $adherent->statut->libelle() }}</x-badge>
                            @if ($estAJour)
                                <x-badge couleur="green">Cotisations à jour</x-badge>
                            @else
                                <x-badge couleur="red">Arriérés de cotisation</x-badge>
                            @endif
                            @if ($adherent->user_id)
                                <x-badge couleur="gray">Compte d'accès actif</x-badge>
                            @endif
                        </div>

                        <ul class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-sm text-gray-600">
                            <li class="flex items-center gap-1.5">
                                <x-heroicon-o-identification class="w-4 h-4 text-gray-400" />
                                <span class="font-medium text-gray-800">{{ $adherent->matricule }}</span>
                            </li>
                            @if ($adherent->etablissement)
                                <li class="flex items-center gap-1.5"><x-heroicon-o-building-office-2 class="w-4 h-4 text-gray-400" />{{ $adherent->etablissement }}</li>
                            @endif
                            @if ($adherent->ville)
                                <li class="flex items-center gap-1.5"><x-heroicon-o-map-pin class="w-4 h-4 text-gray-400" />{{ $adherent->ville }}</li>
                            @endif
                            @if ($adherent->telephone)
                                <li class="flex items-center gap-1.5"><x-heroicon-o-phone class="w-4 h-4 text-gray-400" />{{ $adherent->telephone }}</li>
                            @endif
                            @if ($adherent->email)
                                <li class="flex items-center gap-1.5"><x-heroicon-o-envelope class="w-4 h-4 text-gray-400" />{{ $adherent->email }}</li>
                            @endif
                            <li class="flex items-center gap-1.5">
                                <x-heroicon-o-calendar-days class="w-4 h-4 text-gray-400" />
                                Membre depuis le {{ $adherent->date_adhesion->format('d/m/Y') }}
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            {{-- ====================== Indicateurs ====================== --}}
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="flex items-start gap-4 bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <span @class([
                        'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl',
                        'bg-green-50 text-green-600' => $estAJour,
                        'bg-red-50 text-red-600' => ! $estAJour,
                    ])>
                        <x-heroicon-o-banknotes class="w-6 h-6" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm text-gray-500">Reste à payer</p>
                        <p @class(['text-xl font-bold', 'text-green-600' => $estAJour, 'text-red-600' => ! $estAJour])>
                            {{ number_format($solde['reste'], 0, ',', ' ') }} FCFA
                        </p>
                        <p class="mt-0.5 text-xs text-gray-500">
                            {{ number_format($solde['paye'], 0, ',', ' ') }} payés sur {{ number_format($solde['du'], 0, ',', ' ') }}
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-4 bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
                        <x-heroicon-o-shield-check class="w-6 h-6" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm text-gray-500">Fin de carence</p>
                        <p class="text-xl font-bold text-gray-900">{{ $dateFinCarence->format('d/m/Y') }}</p>
                        <p class="mt-0.5 text-xs text-gray-500">
                            @if ($carenceTerminee)
                                Carence terminée
                            @else
                                En cours
                            @endif
                            · {{ $delaiCarenceMinimum }} mois requis
                            @if ($penaliteRetardMois > 0)
                                <span class="text-primary-600">(dont +{{ $penaliteRetardMois }} de pénalité)</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-4 bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <span @class([
                        'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl',
                        'bg-green-50 text-green-600' => $droitAdhesionPaye,
                        'bg-red-50 text-red-600' => ! $droitAdhesionPaye,
                    ])>
                        <x-heroicon-o-credit-card class="w-6 h-6" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm text-gray-500">Droit d'adhésion</p>
                        <p @class(['text-xl font-bold', 'text-green-600' => $droitAdhesionPaye, 'text-red-600' => ! $droitAdhesionPaye])>
                            {{ $droitAdhesionPaye ? 'Payé' : 'Impayé' }}
                        </p>
                        <p class="mt-0.5 text-xs text-gray-500">
                            @if ($adherent->droit_adhesion_exonere)
                                Adhérent exonéré
                            @elseif ($droitAdhesionAdherent)
                                Réglé le {{ $droitAdhesionAdherent->date_paiement->format('d/m/Y') }}
                            @else
                                Paiement à enregistrer
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-4 bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
                        <x-heroicon-o-users class="w-6 h-6" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm text-gray-500">Personnes à charge</p>
                        <p class="text-xl font-bold text-gray-900">{{ $personnesACharge->count() }}</p>
                        <p class="mt-0.5 text-xs text-gray-500">{{ $adherent->tailleCarnet() }} validée(s) dans le carnet</p>
                    </div>
                </div>
            </div>

            {{-- Paiement d'un droit d'adhésion (adhérent ou personne à charge) : déclenché
                 depuis deux onglets, le formulaire s'affiche donc au-dessus des onglets. --}}
            @if ($formulaireDroitAdhesionOuvert)
                <x-panel titre="Enregistrer le droit d'adhésion"
                         icone="credit-card"
                         :description="$droitAdhesionPayableType === 'adherent' ? 'Pour l\'adhérent tuteur' : 'Pour une personne à charge'"
                         x-init="$el.scrollIntoView({ behavior: 'smooth', block: 'center' })">
                    <form wire:submit="enregistrerDroitAdhesion" id="form-droit-adhesion" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <x-input-label for="droit_adhesion_montant" value="Montant (FCFA)" />
                            <x-text-input type="number" wire:model="droit_adhesion_montant" id="droit_adhesion_montant" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('droit_adhesion_montant')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="droit_adhesion_date_paiement" value="Date de paiement" />
                            <x-text-input type="date" wire:model="droit_adhesion_date_paiement" id="droit_adhesion_date_paiement" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('droit_adhesion_date_paiement')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="droit_adhesion_mode_paiement" value="Mode de paiement" />
                            <x-select-input wire:model="droit_adhesion_mode_paiement" id="droit_adhesion_mode_paiement" class="mt-1 block w-full">
                                @foreach ($modesPaiement as $m)
                                    <option value="{{ $m->value }}">{{ $m->libelle() }}</option>
                                @endforeach
                            </x-select-input>
                            <x-input-error :messages="$errors->get('droit_adhesion_mode_paiement')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="droit_adhesion_reference" value="Référence (optionnel)" />
                            <x-text-input wire:model="droit_adhesion_reference" id="droit_adhesion_reference" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('droit_adhesion_reference')" class="mt-2" />
                        </div>
                    </form>

                    <x-slot:footer>
                        <button type="button" wire:click="fermerFormulaireDroitAdhesion" class="text-sm font-medium text-gray-600 hover:text-gray-900">Annuler</button>
                        <x-primary-button form="form-droit-adhesion">Enregistrer le paiement</x-primary-button>
                    </x-slot:footer>
                </x-panel>
            @endif

            {{-- ====================== Onglets ====================== --}}
            <nav class="flex gap-1 overflow-x-auto bg-white rounded-2xl border border-gray-100 shadow-sm p-1.5" role="tablist" aria-label="Sections de la fiche">
                @php
                    $onglets = [
                        'apercu' => ['Vue d\'ensemble', 'user-circle', null],
                        'cotisations' => ['Cotisations', 'banknotes', $moisImpayes > 0 ? $moisImpayes : null],
                        'foyer' => ['Personnes à charge', 'users', $personnesACharge->count() ?: null],
                        'securite' => ['Sécurité', 'lock-closed', null],
                    ];
                @endphp
                @foreach ($onglets as $cle => [$libelle, $icone, $compteur])
                    <button type="button"
                            role="tab"
                            x-on:click="aller('{{ $cle }}')"
                            x-bind:aria-selected="onglet === '{{ $cle }}'"
                            x-bind:class="onglet === '{{ $cle }}' ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                            class="inline-flex shrink-0 items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-xl transition">
                        <x-dynamic-component :component="'heroicon-o-'.$icone" class="w-5 h-5" />
                        {{ $libelle }}
                        @if ($compteur)
                            <span @class([
                                'inline-flex min-w-5 items-center justify-center rounded-full px-1.5 text-xs font-semibold',
                                'bg-red-100 text-red-700' => $cle === 'cotisations',
                                'bg-gray-100 text-gray-600' => $cle !== 'cotisations',
                            ])>{{ $compteur }}</span>
                        @endif
                    </button>
                @endforeach
            </nav>

            {{-- ============ Onglet : vue d'ensemble ============ --}}
            <div x-show="onglet === 'apercu'" x-cloak role="tabpanel" class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <x-panel titre="Profil" description="Identité, coordonnées et situation professionnelle" icone="user">
                        <x-slot:actions>
                            <a href="{{ route('gestion.adherents.modifier', ['adherent' => $adherent, 'section' => 'profil']) }}" wire:navigate
                               class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-600 hover:text-primary-800">
                                <x-heroicon-o-pencil-square class="w-4 h-4" /> Modifier
                            </a>
                        </x-slot:actions>

                        <dl class="grid gap-x-6 gap-y-5 sm:grid-cols-2">
                            <x-field label="Sexe" :value="$adherent->sexe?->libelle()" />
                            <x-field label="Date de naissance">
                                @if ($adherent->date_naissance)
                                    {{ $adherent->date_naissance->format('d/m/Y') }}
                                    <span class="text-gray-500">({{ $adherent->date_naissance->age }} ans)</span>
                                @endif
                            </x-field>
                            <x-field label="Téléphone" :value="$adherent->telephone" />
                            <x-field label="Email" :value="$adherent->email" />
                            <x-field label="Établissement" :value="$adherent->etablissement" />
                            <x-field label="Ville" :value="$adherent->ville" />
                            <x-field label="Fonction" :value="$adherent->fonction" class="sm:col-span-2" />
                        </dl>
                    </x-panel>

                    <x-panel titre="Adhésion et carence" description="Statut, dates et droit d'adhésion" icone="calendar-days">
                        <x-slot:actions>
                            <a href="{{ route('gestion.adherents.modifier', ['adherent' => $adherent, 'section' => 'adhesion']) }}" wire:navigate
                               class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-600 hover:text-primary-800">
                                <x-heroicon-o-pencil-square class="w-4 h-4" /> Modifier
                            </a>
                        </x-slot:actions>

                        <dl class="grid gap-x-6 gap-y-5 sm:grid-cols-2">
                            <x-field label="Matricule" :value="$adherent->matricule" />
                            <x-field label="Statut">
                                <x-badge :couleur="$statutCouleur">{{ $adherent->statut->libelle() }}</x-badge>
                            </x-field>
                            <x-field label="Date d'adhésion" :value="$adherent->date_adhesion->format('d/m/Y')" />
                            <x-field label="Délai de carence requis">
                                {{ $delaiCarenceMinimum }} mois — fin le {{ $dateFinCarence->format('d/m/Y') }}
                                @if ($penaliteRetardMois > 0)
                                    <span class="block text-xs text-primary-600 mt-0.5">
                                        Dont +{{ $penaliteRetardMois }} mois pour retard de cotisation (Article 7).
                                    </span>
                                @endif
                            </x-field>
                            <x-field label="Droit d'adhésion" class="sm:col-span-2">
                                @if ($adherent->droit_adhesion_exonere)
                                    <x-badge couleur="green">Payé</x-badge>
                                    <span class="ml-2 text-xs text-gray-500">Adhérent exonéré</span>
                                @elseif ($droitAdhesionPaye)
                                    <x-badge couleur="green">Payé</x-badge>
                                    @if ($droitAdhesionAdherent)
                                        @can('annuler', $droitAdhesionAdherent)
                                            <button type="button" wire:click="annulerDroitAdhesion('adherent', null)" wire:confirm="Annuler ce droit d'adhésion ?" class="ml-3 text-xs font-medium text-red-600 hover:underline">Annuler</button>
                                        @endcan
                                    @endif
                                @else
                                    <x-badge couleur="red">Impayé</x-badge>
                                    <button type="button" wire:click="ouvrirFormulaireDroitAdhesion('adherent')" class="ml-3 text-xs font-medium text-primary-600 hover:underline">Enregistrer le paiement</button>
                                @endif
                            </x-field>
                        </dl>
                    </x-panel>
                </div>

                <div class="space-y-6">
                    <x-panel titre="Compte d'accès" description="Connexion à « Mon espace »" icone="lock-closed">
                        @if ($adherent->user_id)
                            <div class="flex items-center gap-2">
                                <x-badge couleur="green">Compte actif</x-badge>
                            </div>
                            <p class="mt-3 text-sm text-gray-600">
                                L'adhérent se connecte avec son <strong>matricule</strong> ou l'adresse email du compte.
                            </p>
                            <dl class="mt-4 grid gap-4">
                                <x-field label="Email du compte" :value="$adherent->user?->email" />
                            </dl>
                        @else
                            <x-badge couleur="gray">Aucun compte</x-badge>
                            <p class="mt-3 text-sm text-gray-600">Cet adhérent n'a pas de compte d'accès à « Mon espace ».</p>
                        @endif

                        <button type="button" x-on:click="aller('securite')"
                                class="mt-5 inline-flex w-full items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition">
                            <x-heroicon-o-key class="w-4 h-4" />
                            Gérer la sécurité
                        </button>
                    </x-panel>

                    <x-panel titre="Ayant droit" description="Reçoit ce que l'adhérent a cotisé en cas de décès ou de disparition." icone="users">
                        @if ($formulaireAyantDroitOuvert)
                            <form wire:submit="enregistrerAyantDroit" class="space-y-4">
                                <div>
                                    <x-input-label for="ayant_droit_nom" value="Nom complet" />
                                    <x-text-input wire:model="ayant_droit_nom" id="ayant_droit_nom" class="mt-1 block w-full" />
                                    <x-input-error :messages="$errors->get('ayant_droit_nom')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="ayant_droit_telephone" value="Téléphone" />
                                    <x-text-input wire:model="ayant_droit_telephone" id="ayant_droit_telephone" class="mt-1 block w-full" />
                                    <x-input-error :messages="$errors->get('ayant_droit_telephone')" class="mt-2" />
                                </div>
                                <div class="flex items-center gap-4">
                                    <x-primary-button>Enregistrer</x-primary-button>
                                    <button type="button" wire:click="fermerFormulaireAyantDroit" class="text-sm font-medium text-gray-600 hover:text-gray-900">Annuler</button>
                                </div>
                            </form>
                        @elseif ($adherent->aUnAyantDroit())
                            <dl class="grid gap-4">
                                <x-field label="Nom complet" :value="$adherent->ayant_droit_nom" />
                                <x-field label="Téléphone" :value="$adherent->ayant_droit_telephone" />
                            </dl>
                            <div class="mt-5 flex items-center gap-4 text-sm font-medium">
                                <button type="button" wire:click="ouvrirFormulaireAyantDroit" class="text-primary-600 hover:underline">Modifier</button>
                                <button type="button" wire:click="supprimerAyantDroit" wire:confirm="Retirer l'ayant droit de cet adhérent ?" class="text-red-600 hover:underline">Retirer</button>
                            </div>
                        @else
                            <p class="text-sm text-gray-500">Aucun ayant droit renseigné.</p>
                            <button type="button" wire:click="ouvrirFormulaireAyantDroit"
                                    class="mt-4 inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-primary-700 bg-primary-50 rounded-xl hover:bg-primary-100 transition">
                                <x-heroicon-o-plus class="w-4 h-4" /> Ajouter un ayant droit
                            </button>
                        @endif
                    </x-panel>

                    @can('delete', $adherent)
                        <section class="bg-white rounded-2xl border border-red-100 shadow-sm p-6">
                            <h3 class="text-base font-semibold text-red-700">Zone sensible</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                La suppression est définitive et impossible si l'adhérent a un historique
                                (cotisations, sinistres, personnes à charge). Utilisez alors le statut « Radié ».
                            </p>
                            <button type="button"
                                    wire:click="supprimer"
                                    wire:confirm="Supprimer définitivement {{ $adherent->matricule }} ? Cette action bloque si un historique de cotisations/sinistres existe déjà."
                                    class="mt-4 inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-red-600 bg-white border border-red-200 rounded-xl hover:bg-red-50 transition">
                                <x-heroicon-o-trash class="w-4 h-4" />
                                Supprimer cet adhérent
                            </button>
                        </section>
                    @endcan
                </div>
            </div>

            {{-- ============ Onglet : cotisations ============ --}}
            <div x-show="onglet === 'cotisations'" x-cloak role="tabpanel" class="space-y-6">
                <x-panel titre="Situation des cotisations" icone="banknotes">
                    <x-slot:actions>
                        <button type="button" wire:click="ouvrirFormulaireCotisation"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-500 rounded-xl shadow-sm hover:bg-primary-600 transition">
                            <x-heroicon-o-plus class="w-4 h-4" /> Enregistrer un paiement
                        </button>
                    </x-slot:actions>

                    <dl class="grid gap-6 sm:grid-cols-3">
                        <x-field label="Dû">{{ number_format($solde['du'], 0, ',', ' ') }} FCFA</x-field>
                        <x-field label="Payé">{{ number_format($solde['paye'], 0, ',', ' ') }} FCFA</x-field>
                        <x-field label="Reste à payer">
                            <span @class(['font-semibold', 'text-green-600' => $estAJour, 'text-red-600' => ! $estAJour])>
                                {{ number_format($solde['reste'], 0, ',', ' ') }} FCFA
                            </span>
                            @unless ($estAJour)
                                <span class="block text-xs text-red-500">Arriéré sur un ou plusieurs mois échus</span>
                            @endunless
                        </x-field>
                    </dl>

                    <div class="mt-5">
                        <div class="flex items-center justify-between text-xs text-gray-500 mb-1.5">
                            <span>Progression du règlement</span>
                            <span>{{ $pourcentagePaye }} %</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-gray-100" role="progressbar" aria-valuenow="{{ $pourcentagePaye }}" aria-valuemin="0" aria-valuemax="100">
                            <div @class(['h-full rounded-full', 'bg-green-500' => $estAJour, 'bg-primary-500' => ! $estAJour]) style="width: {{ $pourcentagePaye }}%"></div>
                        </div>
                    </div>
                </x-panel>

                @if ($formulaireCotisationOuvert)
                    <x-panel titre="Enregistrer un paiement" description="Choisissez les mois réglés, puis les détails du paiement." icone="credit-card"
                             x-init="$el.scrollIntoView({ behavior: 'smooth', block: 'center' })">
                        <form wire:submit="enregistrerCotisation" id="form-cotisation" class="space-y-6">
                            <div>
                                <p class="text-sm font-medium text-gray-700 mb-2">Mois à régler</p>

                                @if ($periodesImpayees->isEmpty())
                                    <p class="text-sm text-green-700">Aucun mois en attente — l'adhérent est à jour.</p>
                                @else
                                    <div class="space-y-2">
                                        @foreach ($periodesImpayees as $p)
                                            @php
                                                $cle = $p['debut']->format('Y-m-d');
                                                $reste = $p['du'] - $p['paye'];
                                            @endphp
                                            <div class="border border-gray-200 rounded-xl p-3" wire:key="periode-{{ $cle }}">
                                                <label class="flex flex-wrap items-center gap-2 cursor-pointer">
                                                    <input type="checkbox" wire:model.live="periodesAPayer.{{ $cle }}.selectionne" class="rounded border-gray-300 text-primary-600">
                                                    <span class="font-medium text-gray-900">
                                                        {{ \App\Enums\Mois::from($p['debut']->month)->libelle() }} {{ $p['debut']->year }}
                                                    </span>
                                                    <span class="text-xs text-gray-500">
                                                        — dû {{ number_format($p['du'], 0, ',', ' ') }} F, déjà payé {{ number_format($p['paye'], 0, ',', ' ') }} F,
                                                        reste {{ number_format($reste, 0, ',', ' ') }} F
                                                    </span>
                                                </label>

                                                @if ($periodesAPayer[$cle]['selectionne'] ?? false)
                                                    <div class="mt-3 ml-6 flex flex-wrap items-center gap-4">
                                                        <label class="flex items-center gap-1.5 text-sm text-gray-700">
                                                            <input type="radio" wire:model.live="periodesAPayer.{{ $cle }}.type" value="complet" class="text-primary-600">
                                                            Complet ({{ number_format($reste, 0, ',', ' ') }} FCFA)
                                                        </label>
                                                        <label class="flex items-center gap-1.5 text-sm text-gray-700">
                                                            <input type="radio" wire:model.live="periodesAPayer.{{ $cle }}.type" value="partiel" class="text-primary-600">
                                                            Partiel
                                                        </label>

                                                        @if (($periodesAPayer[$cle]['type'] ?? 'complet') === 'partiel')
                                                            <input type="number" wire:model="periodesAPayer.{{ $cle }}.montant_partiel"
                                                                   placeholder="Montant FCFA" min="1"
                                                                   class="rounded-lg border-gray-300 shadow-sm text-sm w-32">
                                                        @endif
                                                    </div>
                                                    <x-input-error :messages="$errors->get('periodesAPayer.'.$cle.'.montant_partiel')" class="mt-1 ml-6" />
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                    <x-input-error :messages="$errors->get('periodesAPayer')" class="mt-2" />
                                @endif
                            </div>

                            <div class="grid gap-4 sm:grid-cols-3">
                                <div>
                                    <x-input-label for="date_paiement" value="Date de paiement" />
                                    <x-text-input type="date" wire:model="date_paiement" id="date_paiement" class="mt-1 block w-full" />
                                    <x-input-error :messages="$errors->get('date_paiement')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="mode_paiement" value="Mode de paiement" />
                                    <x-select-input wire:model="mode_paiement" id="mode_paiement" class="mt-1 block w-full">
                                        @foreach ($modesPaiement as $m)
                                            <option value="{{ $m->value }}">{{ $m->libelle() }}</option>
                                        @endforeach
                                    </x-select-input>
                                    <x-input-error :messages="$errors->get('mode_paiement')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="reference" value="Référence (optionnel)" />
                                    <x-text-input wire:model="reference" id="reference" class="mt-1 block w-full" />
                                    <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                                </div>
                            </div>
                        </form>

                        <x-slot:footer>
                            <button type="button" wire:click="$set('formulaireCotisationOuvert', false)" class="text-sm font-medium text-gray-600 hover:text-gray-900">Annuler</button>
                            <x-primary-button form="form-cotisation">Enregistrer le paiement</x-primary-button>
                        </x-slot:footer>
                    </x-panel>
                @endif

                <div class="grid gap-6 xl:grid-cols-5">
                    <x-panel titre="Historique des paiements" icone="document-text" class="xl:col-span-3">
                        <x-slot:actions>
                            <div class="flex flex-wrap items-end gap-2">
                                <x-select-input wire:model.live="anneeFiltre" aria-label="Année" class="!py-1.5 text-sm">
                                    <option value="">Toutes les années</option>
                                    @foreach ($anneesDisponibles as $annee)
                                        <option value="{{ $annee }}">{{ $annee }}</option>
                                    @endforeach
                                </x-select-input>
                                <x-select-input wire:model.live="moisFiltre" aria-label="Mois" class="!py-1.5 text-sm">
                                    <option value="">Tous les mois</option>
                                    @foreach ($moisListe as $m)
                                        <option value="{{ $m->value }}">{{ $m->libelle() }}</option>
                                    @endforeach
                                </x-select-input>
                                @if ($anneeFiltre || $moisFiltre)
                                    <button type="button" wire:click="$set('anneeFiltre', ''); $set('moisFiltre', '')" class="pb-1.5 text-sm font-medium text-primary-600 hover:underline">
                                        Réinitialiser
                                    </button>
                                @endif
                            </div>
                        </x-slot:actions>

                        @if ($cotisations->isEmpty())
                            <div class="flex flex-col items-center gap-2 py-8 text-center">
                                <x-heroicon-o-document-text class="w-8 h-8 text-gray-300" />
                                <p class="text-sm text-gray-500">
                                    {{ ($anneeFiltre || $moisFiltre) ? 'Aucun paiement pour cette période.' : 'Aucun paiement enregistré.' }}
                                </p>
                            </div>
                        @else
                            <div class="-mx-6 -my-6 overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            <th class="px-6 py-3">Date</th>
                                            <th class="px-3 py-3">Période</th>
                                            <th class="px-3 py-3">Montant</th>
                                            <th class="px-3 py-3">Mode</th>
                                            <th class="px-3 py-3">Statut</th>
                                            <th class="px-6 py-3 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($cotisations as $c)
                                            <tr wire:key="cotisation-{{ $c->id }}" @class(['hover:bg-gray-50', 'opacity-60' => $c->statut->value === 'annule'])>
                                                <td class="px-6 py-3 font-medium text-gray-900 whitespace-nowrap">{{ $c->date_paiement->format('d/m/Y') }}</td>
                                                <td class="px-3 py-3 text-gray-600 whitespace-nowrap">{{ $c->periode_debut->format('d/m/Y') }} – {{ $c->periode_fin->format('d/m/Y') }}</td>
                                                <td class="px-3 py-3 text-gray-900 whitespace-nowrap">{{ number_format($c->montant, 0, ',', ' ') }} FCFA</td>
                                                <td class="px-3 py-3 text-gray-600">{{ $c->mode_paiement->libelle() }}</td>
                                                <td class="px-3 py-3">
                                                    @if ($c->statut->value === 'annule')
                                                        <x-badge couleur="gray">Annulée</x-badge>
                                                    @else
                                                        <x-badge couleur="green">Validée</x-badge>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-3 text-right">
                                                    <x-menu label="Actions" size="sm" align="right">
                                                        <x-menu-item :href="route('cotisations.recu', $c)" :navigate="false" icon="document-arrow-down">Reçu PDF</x-menu-item>
                                                        @can('annuler', $c)
                                                            @if ($c->statut->value !== 'annule')
                                                                <x-menu-item wire:click="annulerCotisation({{ $c->id }})"
                                                                             wire:confirm="Confirmer l'annulation de ce paiement de {{ number_format($c->montant, 0, ',', ' ') }} FCFA ? Il ne sera plus compté dans le solde de l'adhérent."
                                                                             icon="x-circle" danger separated>
                                                                    Annuler le paiement
                                                                </x-menu-item>
                                                            @endif
                                                        @endcan
                                                    </x-menu>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </x-panel>

                    <x-panel titre="Relevé mensuel" description="Mois par mois, depuis l'adhésion" icone="calendar-days" class="xl:col-span-2">
                        <div class="max-h-[32rem] overflow-y-auto -mr-2 pr-2">
                            @include('partials.releve-cotisation', ['sansTitre' => true])
                        </div>
                    </x-panel>
                </div>
            </div>

            {{-- ============ Onglet : personnes à charge ============ --}}
            <div x-show="onglet === 'foyer'" x-cloak role="tabpanel" class="space-y-6">
                @if ($formulairePersonneOuvert)
                    <x-panel :titre="$personneEnEditionId ? 'Modifier la personne à charge' : 'Ajouter une personne à charge'" icone="users"
                             x-init="$el.scrollIntoView({ behavior: 'smooth', block: 'center' })">
                        <form wire:submit="enregistrerPersonne" id="form-personne" class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="personne_nom" value="Nom" />
                                <x-text-input wire:model="personne_nom" id="personne_nom" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('personne_nom')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="personne_prenom" value="Prénom" />
                                <x-text-input wire:model="personne_prenom" id="personne_prenom" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('personne_prenom')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="personne_date_naissance" value="Date de naissance" />
                                <x-text-input type="date" wire:model="personne_date_naissance" id="personne_date_naissance" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('personne_date_naissance')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="personne_lien_parente" value="Lien de parenté" />
                                <x-text-input wire:model="personne_lien_parente" id="personne_lien_parente" placeholder="Enfant, conjoint, père, mère..." class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('personne_lien_parente')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="personne_date_adhesion" value="Date d'adhésion" />
                                <x-text-input type="date" wire:model="personne_date_adhesion" id="personne_date_adhesion" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('personne_date_adhesion')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="personne_date_fin_carence_indicative" value="Date de fin de carence (forcée)" />
                                <x-text-input type="date" wire:model="personne_date_fin_carence_indicative" id="personne_date_fin_carence_indicative" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('personne_date_fin_carence_indicative')" class="mt-2" />
                                <p class="mt-1 text-xs text-gray-500">
                                    Laisser vide pour un calcul automatique. Une pénalité de retard de cotisation (passée ou future) s'ajoute toujours par-dessus, ne l'inclus pas dans la date saisie.
                                </p>
                            </div>
                        </form>

                        <x-slot:footer>
                            <button type="button" wire:click="fermerFormulairePersonne" class="text-sm font-medium text-gray-600 hover:text-gray-900">Annuler</button>
                            <x-primary-button form="form-personne">Enregistrer</x-primary-button>
                        </x-slot:footer>
                    </x-panel>
                @endif

                <x-panel titre="Personnes à charge"
                         :description="$personnesACharge->count().' personne(s) déclarée(s) · '.$adherent->tailleCarnet().' validée(s) dans le carnet'"
                         icone="users">
                    <x-slot:actions>
                        <button type="button" wire:click="ouvrirFormulairePersonne"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-500 rounded-xl shadow-sm hover:bg-primary-600 transition">
                            <x-heroicon-o-plus class="w-4 h-4" /> Ajouter
                        </button>
                    </x-slot:actions>

                    @if ($personnesACharge->isEmpty())
                        <div class="flex flex-col items-center gap-2 py-8 text-center">
                            <x-heroicon-o-users class="w-8 h-8 text-gray-300" />
                            <p class="text-sm text-gray-500">Aucune personne à charge déclarée.</p>
                        </div>
                    @else
                        <div class="-mx-6 -my-6 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        <th class="px-6 py-3">Personne</th>
                                        <th class="px-3 py-3">Naissance</th>
                                        <th class="px-3 py-3">Adhésion</th>
                                        <th class="px-3 py-3">Carence</th>
                                        <th class="px-3 py-3">Droit d'adhésion</th>
                                        <th class="px-6 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($personnesACharge as $personne)
                                        @php($carence = $carencePersonnes[$personne->id])
                                        <tr wire:key="personne-{{ $personne->id }}" class="hover:bg-gray-50">
                                            <td class="px-6 py-3">
                                                <div class="flex items-center gap-3">
                                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-600">
                                                        {{ strtoupper(mb_substr((string) $personne->prenom, 0, 1).mb_substr((string) $personne->nom, 0, 1)) ?: '?' }}
                                                    </span>
                                                    <div>
                                                        <p class="font-medium text-gray-900">{{ $personne->nomComplet() }}</p>
                                                        <p class="text-xs text-gray-500">{{ $personne->lien_parente ?: 'Lien non précisé' }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-3 py-3 text-gray-600 whitespace-nowrap">
                                                @if ($personne->date_naissance)
                                                    {{ $personne->date_naissance->format('d/m/Y') }}
                                                    <span class="text-xs text-gray-400">({{ $personne->date_naissance->age }} ans)</span>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="px-3 py-3 text-gray-600 whitespace-nowrap">{{ $personne->date_adhesion?->format('d/m/Y') ?? '—' }}</td>
                                            <td class="px-3 py-3 text-gray-600">
                                                <span class="whitespace-nowrap">{{ $carence['dateFin']->format('d/m/Y') }}</span>
                                                <span class="block text-xs text-gray-400">{{ $carence['delai'] }} mois requis</span>
                                            </td>
                                            <td class="px-3 py-3">
                                                @if ($personne->droit_adhesion_exonere || $personne->droitAdhesionPaye())
                                                    <x-badge couleur="green">Payé</x-badge>
                                                @else
                                                    <x-badge couleur="red">Impayé</x-badge>
                                                @endif
                                            </td>
                                            <td class="px-6 py-3 text-right">
                                                <x-menu label="Actions" size="sm" align="right">
                                                    <x-menu-item wire:click="ouvrirFormulairePersonne({{ $personne->id }})" icon="pencil-square">Modifier</x-menu-item>
                                                    @unless ($personne->droitAdhesionPaye())
                                                        <x-menu-item wire:click="ouvrirFormulaireDroitAdhesion('personne', {{ $personne->id }})" icon="credit-card">Enregistrer le droit d'adhésion</x-menu-item>
                                                    @elseif ($carence['droitAdhesion'])
                                                        @can('annuler', $carence['droitAdhesion'])
                                                            <x-menu-item wire:click="annulerDroitAdhesion('personne', {{ $personne->id }})" wire:confirm="Annuler ce droit d'adhésion ?" icon="x-circle">Annuler le droit d'adhésion</x-menu-item>
                                                        @endcan
                                                    @endunless
                                                    <x-menu-item wire:click="supprimerPersonne({{ $personne->id }})" wire:confirm="Retirer cette personne à charge ?" icon="trash" danger separated>Retirer du carnet</x-menu-item>
                                                </x-menu>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-panel>
            </div>

            {{-- ============ Onglet : sécurité ============ --}}
            <div x-show="onglet === 'securite'" x-cloak role="tabpanel" class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <x-panel titre="Accès à « Mon espace »" description="Compte de connexion de l'adhérent" icone="lock-closed">
                        @if ($adherent->user_id)
                            <dl class="grid gap-x-6 gap-y-5 sm:grid-cols-2">
                                <x-field label="État du compte"><x-badge couleur="green">Compte actif</x-badge></x-field>
                                <x-field label="Identifiants acceptés">Matricule <strong>{{ $adherent->matricule }}</strong> ou email du compte</x-field>
                                <x-field label="Email du compte" :value="$adherent->user?->email" />
                                <x-field label="Mot de passe actuel">
                                    <span class="text-gray-500">Jamais affiché : il n'est conservé que chiffré.</span>
                                </x-field>
                            </dl>
                        @else
                            <div class="flex items-start gap-3 rounded-xl bg-gray-50 p-4 text-sm text-gray-600">
                                <x-heroicon-o-information-circle class="w-5 h-5 shrink-0 text-gray-400" />
                                <p>
                                    Cet adhérent n'a pas de compte d'accès : il ne peut pas se connecter à « Mon espace ».
                                    Le compte se crée à l'enregistrement d'un nouvel adhérent.
                                </p>
                            </div>
                        @endif
                    </x-panel>

                    @if ($adherent->user_id)
                        {{-- wire:confirm doit être sur l'élément qui porte l'action (le formulaire), pas sur son bouton. --}}
                        <form wire:submit="enregistrerSecurite"
                              wire:confirm="Réinitialiser le mot de passe de {{ $adherent->nomComplet() }} ? L'ancien mot de passe ne fonctionnera plus.">
                            <x-panel titre="Réinitialiser le mot de passe" description="Définit un nouveau mot de passe de connexion pour cet adhérent." icone="key">
                                <div class="max-w-xl"
                                     x-data="{ copie: false, copier() { navigator.clipboard.writeText($refs.champ.value); this.copie = true; setTimeout(() => this.copie = false, 1800); } }">
                                    <x-input-label for="nouveau_mot_de_passe" value="Nouveau mot de passe" />
                                    <div class="mt-1 flex gap-2">
                                        <x-text-input type="text" x-ref="champ" wire:model="nouveau_mot_de_passe" id="nouveau_mot_de_passe"
                                                      autocomplete="off" placeholder="6 caractères minimum" class="block w-full font-mono" />
                                        <button type="button" wire:click="genererMotDePasse"
                                                class="inline-flex shrink-0 items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition">
                                            <x-heroicon-o-arrow-path class="w-4 h-4" /> Générer
                                        </button>
                                        <button type="button" x-on:click="copier()"
                                                class="inline-flex shrink-0 items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition">
                                            <x-heroicon-o-clipboard-document class="w-4 h-4" />
                                            <span x-text="copie ? 'Copié' : 'Copier'"></span>
                                        </button>
                                    </div>
                                    <x-input-error :messages="$errors->get('nouveau_mot_de_passe')" class="mt-2" />

                                    <p class="mt-3 flex items-start gap-2 text-xs text-gray-500">
                                        <x-heroicon-o-eye class="w-4 h-4 shrink-0 text-gray-400" />
                                        <span>
                                            Le mot de passe est visible pendant la saisie pour que vous puissiez le noter et le
                                            communiquer à l'adhérent. Une fois enregistré, il n'est plus affiché. L'opération
                                            est inscrite au journal d'audit (sans le mot de passe).
                                        </span>
                                    </p>
                                </div>

                                <x-slot:footer>
                                    <x-primary-button>
                                        <span wire:loading.remove wire:target="enregistrerSecurite">Réinitialiser le mot de passe</span>
                                        <span wire:loading wire:target="enregistrerSecurite">Enregistrement…</span>
                                    </x-primary-button>
                                </x-slot:footer>
                            </x-panel>
                        </form>
                    @endif
                </div>

                <x-panel titre="Dernières opérations" description="Mot de passe et statut, d'après le journal d'audit" icone="clock">
                    @forelse ($journalSecurite as $entree)
                        <div @class(['flex items-start gap-3', 'mt-4 pt-4 border-t border-gray-100' => ! $loop->first])>
                            <span @class([
                                'mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg',
                                'bg-primary-50 text-primary-600' => $entree->action === 'adherent.mot_de_passe_reinitialise',
                                'bg-gray-100 text-gray-500' => $entree->action !== 'adherent.mot_de_passe_reinitialise',
                            ])>
                                @if ($entree->action === 'adherent.mot_de_passe_reinitialise')
                                    <x-heroicon-o-key class="w-4 h-4" />
                                @else
                                    <x-heroicon-o-arrow-path class="w-4 h-4" />
                                @endif
                            </span>
                            <div class="min-w-0 text-sm">
                                <p class="font-medium text-gray-900">
                                    {{ $entree->action === 'adherent.mot_de_passe_reinitialise' ? 'Mot de passe réinitialisé' : 'Statut modifié' }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ $entree->user?->name ?? 'Système' }} · {{ $entree->created_at->format('d/m/Y à H:i') }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Aucune opération enregistrée pour le moment.</p>
                    @endforelse
                </x-panel>
            </div>
        </div>
    </div>
</div>
