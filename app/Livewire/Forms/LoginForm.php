<?php

namespace App\Livewire\Forms;

use App\Models\Adherent;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string')]
    public string $login = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /**
     * Attempt to authenticate the request's credentials.
     *
     * L'identifiant saisi peut être l'email du compte, l'email de contact de
     * la fiche adhérent ou le matricule, quelle que soit la combinaison
     * renseignée : un compte sans email se connecte par matricule, un adhérent
     * dont l'email de contact diffère de celui de son compte peut utiliser
     * l'un ou l'autre.
     *
     * On authentifie le compte retrouvé par son identifiant interne, jamais par
     * un email : la colonne peut être vide ou avoir changé.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        // Espaces (y compris insécables) autour de l'identifiant : fréquents
        // après un copier-coller, invisibles dans le champ. Jamais appliqué au
        // mot de passe, dont les espaces font partie.
        $this->login = self::nettoyerIdentifiant($this->login);

        $this->ensureIsNotRateLimited();

        $connecte = false;

        // Plusieurs comptes peuvent, rarement, partager un identifiant (deux
        // fiches avec le même email de contact) : on n'en choisit aucun
        // arbitrairement, le mot de passe départage.
        foreach ($this->comptesCorrespondants() as $id) {
            if (Auth::attempt(['id' => $id, 'password' => $this->password], $this->remember)) {
                $connecte = true;

                break;
            }
        }

        if (! $connecte) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'form.login' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /** Retire les espaces (dont insécables et de largeur nulle) au début et à la fin. */
    public static function nettoyerIdentifiant(string $identifiant): string
    {
        return preg_replace('/^[\s\x{00A0}\x{200B}\x{FEFF}]+|[\s\x{00A0}\x{200B}\x{FEFF}]+$/u', '', $identifiant) ?? $identifiant;
    }

    /**
     * Identifiants des comptes que l'identifiant saisi désigne : email du
     * compte, email de contact d'une fiche, ou matricule d'une fiche.
     *
     * @return list<int>
     */
    private function comptesCorrespondants(): array
    {
        if ($this->login === '') {
            return [];
        }

        return collect()
            ->merge(User::where('email', $this->login)->pluck('id'))
            ->merge(Adherent::where('email', $this->login)->whereNotNull('user_id')->pluck('user_id'))
            ->merge(Adherent::where('matricule', $this->login)->whereNotNull('user_id')->pluck('user_id'))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->login).'|'.request()->ip());
    }
}
