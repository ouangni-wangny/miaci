# Connexion

Écran : `resources/views/livewire/pages/auth/login.blade.php` ; logique :
`App\Livewire\Forms\LoginForm`.

## Identifiants acceptés

Le champ « Email ou matricule » accepte, **quelle que soit la combinaison renseignée** :

| Saisie | Correspond à |
|---|---|
| Email du compte | `users.email` (adhérents, gestionnaires, administrateurs) |
| Email de contact | `adherents.email` (peut différer de celui du compte) |
| Matricule | `adherents.matricule` |

- Un compte **sans email** se connecte par matricule ; un adhérent dont l'email de
  contact diffère de celui de son compte peut utiliser l'un ou l'autre.
- On authentifie le compte retrouvé par son **identifiant interne**, jamais par un
  email (la colonne peut être vide ou avoir changé).
- Si plusieurs comptes partagent un identifiant (deux fiches avec le même email de
  contact), aucun n'est choisi arbitrairement : le **mot de passe** départage. Un
  mot de passe qui ne correspond à aucun d'eux est refusé.
- Les **espaces au début et à la fin de l'identifiant** (espace collé, insécable,
  de largeur nulle) sont ignorés. Ceux du **mot de passe** ne le sont jamais.
- Limitation de tentatives inchangée : 5 échecs par identifiant et par adresse IP.
- Le message d'échec reste volontairement identique quelle que soit la cause
  (identifiant inconnu ou mot de passe faux).

## Afficher / masquer le mot de passe

Composant `<x-password-input>` (`resources/views/components/password-input.blade.php`),
utilisé à la place de `<x-text-input type="password">` partout : connexion,
inscription, réinitialisation, confirmation, « Mon profil » et changement de mot de
passe dans « Mon espace ». Le bouton œil bascule le champ entre `password` et `text`
(la saisie est conservée), n'est pas dans l'ordre de tabulation et annonce son état
aux lecteurs d'écran. Les extensions de gestion de mots de passe ajoutent leur propre
icône à droite du champ : elle peut chevaucher le bouton œil, sans conséquence sur
son fonctionnement.

## Origine de l'email vide d'un compte

« Mon espace → Mon profil » recopiait un champ email vidé (chaîne vide) dans
`users.email`. Corrigé : l'email du compte n'est jamais vidé, et l'email de contact
vidé est enregistré comme absent (`null`).

## Tests

`tests/Feature/Auth/AuthenticationTest.php` (identifiants, espaces, compte sans email,
email partagé, mot de passe jamais « trimé », présence du bouton) et
`tests/Feature/MonEspace/ProfilTest.php` (email du compte préservé).
