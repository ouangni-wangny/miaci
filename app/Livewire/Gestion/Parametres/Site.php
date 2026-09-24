<?php

namespace App\Livewire\Gestion\Parametres;

use App\Models\BanniereSite;
use App\Models\ParametreSite;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Site extends Component
{
    use WithFileUploads;

    public string $hero_badge = '';

    public string $hero_titre_ligne1 = '';

    public string $hero_titre_ligne2 = '';

    public string $hero_texte = '';

    public string $avantage_1_titre = '';

    public string $avantage_1_texte = '';

    public string $avantage_2_titre = '';

    public string $avantage_2_texte = '';

    public string $avantage_3_titre = '';

    public string $avantage_3_texte = '';

    public string $avantage_4_titre = '';

    public string $avantage_4_texte = '';

    public string $a_propos_texte = '';

    public string $siege_social = '';

    public string $feature_1_titre = '';

    public string $feature_1_texte = '';

    public string $feature_2_titre = '';

    public string $feature_2_texte = '';

    public string $feature_3_titre = '';

    public string $feature_3_texte = '';

    public string $contact_telephone_1 = '';

    public string $contact_telephone_2 = '';

    public string $contact_email = '';

    public string $cta_titre = '';

    public string $cta_texte = '';

    public string $footer_texte = '';

    public $nouvelleBanniere = null;

    public function mount(): void
    {
        $this->authorize('gerer', ParametreSite::class);

        $site = ParametreSite::actuel();

        foreach ($site->getAttributes() as $champ => $valeur) {
            if (property_exists($this, $champ)) {
                $this->{$champ} = (string) $valeur;
            }
        }
    }

    protected function rules(): array
    {
        return [
            'hero_badge' => ['required', 'string', 'max:255'],
            'hero_titre_ligne1' => ['required', 'string', 'max:255'],
            'hero_titre_ligne2' => ['required', 'string', 'max:255'],
            'hero_texte' => ['required', 'string', 'max:1000'],
            'avantage_1_titre' => ['required', 'string', 'max:100'],
            'avantage_1_texte' => ['required', 'string', 'max:255'],
            'avantage_2_titre' => ['required', 'string', 'max:100'],
            'avantage_2_texte' => ['required', 'string', 'max:255'],
            'avantage_3_titre' => ['required', 'string', 'max:100'],
            'avantage_3_texte' => ['required', 'string', 'max:255'],
            'avantage_4_titre' => ['required', 'string', 'max:100'],
            'avantage_4_texte' => ['required', 'string', 'max:255'],
            'a_propos_texte' => ['required', 'string', 'max:2000'],
            'siege_social' => ['required', 'string', 'max:255'],
            'feature_1_titre' => ['required', 'string', 'max:100'],
            'feature_1_texte' => ['required', 'string', 'max:255'],
            'feature_2_titre' => ['required', 'string', 'max:100'],
            'feature_2_texte' => ['required', 'string', 'max:255'],
            'feature_3_titre' => ['required', 'string', 'max:100'],
            'feature_3_texte' => ['required', 'string', 'max:255'],
            'contact_telephone_1' => ['required', 'string', 'max:30'],
            'contact_telephone_2' => ['nullable', 'string', 'max:30'],
            'contact_email' => ['required', 'email', 'max:255'],
            'cta_titre' => ['required', 'string', 'max:255'],
            'cta_texte' => ['required', 'string', 'max:1000'],
            'footer_texte' => ['required', 'string', 'max:500'],
        ];
    }

    public function enregistrer(): void
    {
        $this->authorize('gerer', ParametreSite::class);

        $valides = $this->validate();

        ParametreSite::actuel()->update($valides);

        session()->flash('status', 'Contenu du site mis à jour.');
    }

    public function ajouterBanniere(): void
    {
        $this->authorize('gerer', ParametreSite::class);

        $this->validate(['nouvelleBanniere' => ['required', 'image', 'max:4096']]);

        $ordreMax = (int) BanniereSite::max('ordre');

        BanniereSite::create([
            'image_path' => $this->nouvelleBanniere->store('bannieres', 'public'),
            'ordre' => $ordreMax + 1,
            'actif' => true,
        ]);

        $this->reset('nouvelleBanniere');

        session()->flash('status', 'Bannière ajoutée.');
    }

    public function basculerBanniere(int $id): void
    {
        $this->authorize('gerer', ParametreSite::class);

        $banniere = BanniereSite::findOrFail($id);
        $banniere->update(['actif' => ! $banniere->actif]);
    }

    public function supprimerBanniere(int $id): void
    {
        $this->authorize('gerer', ParametreSite::class);

        $banniere = BanniereSite::findOrFail($id);

        Storage::disk('public')->delete($banniere->image_path);
        $banniere->delete();

        session()->flash('status', 'Bannière supprimée.');
    }

    public function render(): View
    {
        return view('livewire.gestion.parametres.site', [
            'bannieres' => BanniereSite::orderBy('ordre')->get(),
        ]);
    }
}
