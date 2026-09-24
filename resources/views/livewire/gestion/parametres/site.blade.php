<div>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            Contenu du site
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm rounded-xl p-3">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-sm rounded-xl p-4">
                Ce que tu modifies ici s'affiche directement sur la page d'accueil publique (avant connexion).
                La liste "Nos objectifs" et les statuts téléchargeables ne sont volontairement pas modifiables
                ici : ils reprennent le texte légal des statuts signés de la mutuelle.
            </div>

            <form wire:submit="enregistrer" class="space-y-6">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">En-tête (bandeau d'accueil)</h3>
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="hero_badge" value="Badge (petit texte au-dessus du titre)" />
                            <x-text-input wire:model="hero_badge" id="hero_badge" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('hero_badge')" class="mt-2" />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="hero_titre_ligne1" value="Titre — 1ère ligne" />
                                <x-text-input wire:model="hero_titre_ligne1" id="hero_titre_ligne1" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('hero_titre_ligne1')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="hero_titre_ligne2" value="Titre — 2e ligne (mise en valeur)" />
                                <x-text-input wire:model="hero_titre_ligne2" id="hero_titre_ligne2" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('hero_titre_ligne2')" class="mt-2" />
                            </div>
                        </div>
                        <div>
                            <x-input-label for="hero_texte" value="Texte d'introduction" />
                            <textarea wire:model="hero_texte" id="hero_texte" rows="3" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm"></textarea>
                            <x-input-error :messages="$errors->get('hero_texte')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Pourquoi nous rejoindre (4 blocs)</h3>
                    <div class="space-y-4">
                        @foreach ([1, 2, 3, 4] as $n)
                            <div class="grid gap-4 sm:grid-cols-2 pb-4 {{ $n < 4 ? 'border-b border-gray-100 dark:border-gray-700' : '' }}">
                                <div>
                                    <x-input-label for="avantage_{{ $n }}_titre" value="Titre {{ $n }}" />
                                    <x-text-input wire:model="avantage_{{ $n }}_titre" id="avantage_{{ $n }}_titre" class="mt-1 block w-full" />
                                    <x-input-error :messages="$errors->get('avantage_'.$n.'_titre')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="avantage_{{ $n }}_texte" value="Texte {{ $n }}" />
                                    <x-text-input wire:model="avantage_{{ $n }}_texte" id="avantage_{{ $n }}_texte" class="mt-1 block w-full" />
                                    <x-input-error :messages="$errors->get('avantage_'.$n.'_texte')" class="mt-2" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Qui sommes-nous</h3>
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="a_propos_texte" value="Texte de présentation" />
                            <textarea wire:model="a_propos_texte" id="a_propos_texte" rows="4" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm"></textarea>
                            <x-input-error :messages="$errors->get('a_propos_texte')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="siege_social" value="Siège social" />
                            <x-text-input wire:model="siege_social" id="siege_social" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('siege_social')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Ce que vous pouvez faire en ligne (3 blocs)</h3>
                    <div class="space-y-4">
                        @foreach ([1, 2, 3] as $n)
                            <div class="grid gap-4 sm:grid-cols-2 pb-4 {{ $n < 3 ? 'border-b border-gray-100 dark:border-gray-700' : '' }}">
                                <div>
                                    <x-input-label for="feature_{{ $n }}_titre" value="Titre {{ $n }}" />
                                    <x-text-input wire:model="feature_{{ $n }}_titre" id="feature_{{ $n }}_titre" class="mt-1 block w-full" />
                                    <x-input-error :messages="$errors->get('feature_'.$n.'_titre')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="feature_{{ $n }}_texte" value="Texte {{ $n }}" />
                                    <x-text-input wire:model="feature_{{ $n }}_texte" id="feature_{{ $n }}_texte" class="mt-1 block w-full" />
                                    <x-input-error :messages="$errors->get('feature_'.$n.'_texte')" class="mt-2" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Contact</h3>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="contact_telephone_1" value="Téléphone 1" />
                            <x-text-input wire:model="contact_telephone_1" id="contact_telephone_1" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('contact_telephone_1')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="contact_telephone_2" value="Téléphone 2 (optionnel)" />
                            <x-text-input wire:model="contact_telephone_2" id="contact_telephone_2" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('contact_telephone_2')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="contact_email" value="Email" />
                            <x-text-input type="email" wire:model="contact_email" id="contact_email" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('contact_email')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Bandeau final & pied de page</h3>
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="cta_titre" value="Titre du bandeau final" />
                            <x-text-input wire:model="cta_titre" id="cta_titre" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('cta_titre')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="cta_texte" value="Texte du bandeau final" />
                            <textarea wire:model="cta_texte" id="cta_texte" rows="2" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm"></textarea>
                            <x-input-error :messages="$errors->get('cta_texte')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="footer_texte" value="Texte du pied de page" />
                            <textarea wire:model="footer_texte" id="footer_texte" rows="2" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm"></textarea>
                            <x-input-error :messages="$errors->get('footer_texte')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <x-primary-button>Enregistrer le contenu</x-primary-button>
            </form>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-1">Bannières</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Images affichées dans un bandeau sur la page d'accueil, juste sous l'en-tête. Ajoutes-en
                    autant que tu veux ; seules celles marquées « Actif » apparaissent sur le site.
                </p>

                <form wire:submit="ajouterBanniere" class="flex items-end gap-4 mb-6">
                    <div class="flex-1">
                        <x-input-label for="nouvelleBanniere" value="Ajouter une image" />
                        <input type="file" wire:model="nouvelleBanniere" id="nouvelleBanniere" accept="image/*" class="block w-full text-sm text-gray-700 dark:text-gray-300 mt-1">
                        <x-input-error :messages="$errors->get('nouvelleBanniere')" class="mt-2" />
                    </div>
                    <x-primary-button>Ajouter</x-primary-button>
                </form>

                @if ($bannieres->isEmpty())
                    <p class="text-sm text-gray-400 dark:text-gray-500 italic">Aucune bannière ajoutée.</p>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        @foreach ($bannieres as $banniere)
                            <div wire:key="banniere-{{ $banniere->id }}" class="relative rounded-xl overflow-hidden border border-gray-100 dark:border-gray-700">
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($banniere->image_path) }}" alt="Bannière" class="w-full h-24 object-cover {{ $banniere->actif ? '' : 'opacity-40' }}">
                                <div class="p-2 flex items-center justify-between text-xs">
                                    <button wire:click="basculerBanniere({{ $banniere->id }})" class="{{ $banniere->actif ? 'text-green-600' : 'text-gray-400' }} hover:underline">
                                        {{ $banniere->actif ? 'Actif' : 'Inactif' }}
                                    </button>
                                    <button wire:click="supprimerBanniere({{ $banniere->id }})" wire:confirm="Supprimer cette bannière ?" class="text-red-600 hover:underline">
                                        Supprimer
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
