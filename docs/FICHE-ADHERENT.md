# Fiche adhérent et modification par sections

Page `gestion/adherents/{id}` (consultation, avec l'onglet Sécurité) et `gestion/adherents/{id}/modifier`
(modification du profil et de l'adhésion). Composants :
`App\Livewire\Gestion\Adherents\Fiche` et `App\Livewire\Gestion\Adherents\Formulaire`,
qui partagent le trait `App\Livewire\Concerns\ReinitialiseMotDePasse`.

## Sommaire

- [Structure de la fiche](#structure-de-la-fiche)
- [Modification : sections indépendantes](#modification--sections-indépendantes)
- [Sécurité](#sécurité)
- [Composants réutilisables](#composants-réutilisables)
- [Ce qui n'a pas changé](#ce-qui-na-pas-changé)
- [Tests](#tests)

## Structure de la fiche

```
En-tête        fil d'Ariane · [Carte de membre] · [Modifier ▾ Profil | Adhésion | Sécurité (→ onglet)]
Identité       bandeau, photo (ou initiales), nom, pastilles (statut, cotisations,
               compte), matricule, établissement, ville, téléphone, email, ancienneté
               · menu « Statut » · bouton « Enregistrer un paiement »
Indicateurs    Reste à payer · Fin de carence · Droit d'adhésion · Personnes à charge
Onglets        Vue d'ensemble | Cotisations (n mois impayés) | Personnes à charge (n) | Sécurité
```

| Onglet | Contenu |
|---|---|
| **Vue d'ensemble** | Profil (identité, coordonnées, situation professionnelle) · Adhésion et carence (matricule, statut, dates, droit d'adhésion) · Compte d'accès (résumé + accès à la section Sécurité) · Ayant droit · Zone sensible (suppression) |
| **Cotisations** | Situation (dû / payé / reste, barre de progression) · formulaire de paiement · historique filtrable (année, mois) avec menu d'actions · relevé mensuel |
| **Personnes à charge** | Formulaire d'ajout / modification · tableau (naissance et âge, adhésion, fin de carence, droit d'adhésion) avec menu d'actions |
| **Sécurité** | État du compte et identifiants acceptés · réinitialisation du mot de passe (saisie, Générer, Copier, confirmation) · « Dernières opérations » (réinitialisations de mot de passe et changements de statut de cet adhérent, d'après le journal d'audit) |

- Les onglets changent **instantanément** (Alpine, sans aller-retour serveur) et
  se retrouvent dans l'URL : `?onglet=cotisations`, `?onglet=foyer` ou
  `?onglet=securite` ouvre directement l'onglet.
- Les boutons « Enregistrer un paiement » (bandeau) et « Enregistrer le droit
  d'adhésion » ouvrent leur formulaire et font défiler la page dessus, quel que soit
  l'onglet d'où on part.
- La **suppression** a quitté l'en-tête, où un clic malheureux était possible : elle
  est dans une « Zone sensible », visible seulement pour qui a le droit de supprimer.
- Une valeur vide s'affiche « — », jamais un blanc.

## Modification : sections indépendantes

Avant, un seul long formulaire mélangeait identité, statut, dates de carence, photo
et mot de passe : changer un téléphone envoyait aussi le statut et le mot de passe.
Chaque section a maintenant **son propre formulaire, son propre bouton et sa propre
validation** :

| Section (`?section=`) | Champs | Méthode |
|---|---|---|
| **Profil et photo** (`profil`, défaut) | Photo, nom, prénom, sexe, naissance, téléphone, email, établissement, ville, fonction | `enregistrerProfil()` |
| **Adhésion et statut** (`adhesion`) | Statut, date d'adhésion, fin de carence forcée (matricule affiché, non modifiable) | `enregistrerAdhesion()` |

La **sécurité** n'est plus une section de cette page : c'est l'onglet « Sécurité »
de la fiche (voir plus bas). L'ancien lien `…/modifier?section=securite` redirige
vers `…/adherents/{id}?onglet=securite`.

- L'enregistrement d'une section **ne touche pas** aux autres, même si leurs champs ont
  été modifiés à l'écran sans être enregistrés (testé).
- On reste sur la page après l'enregistrement (message de confirmation), au lieu d'être
  renvoyé vers la liste.
- Un lien profond existe pour chaque section ; une valeur inconnue retombe sur `profil`.
- La **création** reste un seul formulaire (`enregistrer()`), présenté en panneaux
  (Profil et photo · Adhésion et statut · Accès à « Mon espace »). `enregistrer()`
  sait aussi enregistrer tout d'un coup pour un adhérent existant (conservé pour la
  compatibilité, non utilisé par l'interface).

## Sécurité

- **Réinitialisation du mot de passe** (onglet « Sécurité » de la fiche, logique dans le
  trait `ReinitialiseMotDePasse`, partagé avec `Formulaire::enregistrer()`) : champ obligatoire (6 caractères minimum),
  bouton **Générer** (10 caractères alphanumériques) et **Copier**. Le mot de passe
  reste visible pendant la saisie — besoin métier : le gestionnaire le communique à
  l'adhérent — mais il n'est **plus jamais renvoyé** au navigateur après
  l'enregistrement.
- **Confirmation** avant la réinitialisation (`wire:confirm` sur le formulaire ;
  placé sur le bouton il n'aurait aucun effet, Livewire ne l'applique qu'à l'élément
  qui porte `wire:submit` / `wire:click`).
- **Journal d'audit** : `adherent.mot_de_passe_reinitialise` (qui, quand, quel
  adhérent — jamais le mot de passe) ; `adherent.statut_modifie` (avant / après)
  aussi quand le statut est changé depuis la section Adhésion, comme depuis le menu
  « Statut » de la fiche. Ces deux types d'entrées alimentent « Dernières opérations ».
- Autorisations : `update` pour les sections et pour la réinitialisation, plus
  `changerStatut` si le statut change. Les sections n'existent pas en création (404) ;
  sans compte d'accès, l'onglet Sécurité n'offre aucun formulaire et l'appel direct est
  refusé (422).
- La fiche n'affiche jamais le mot de passe ni son empreinte (testé).
- Choix volontaire : **aucun bouton « créer un compte d'accès »** pour un adhérent qui
  n'en a pas. Le compte se crée à la création de l'adhérent (avec le mot de passe par
  défaut) ; un second chemin de création de comptes à mot de passe par défaut est une
  décision de sécurité à prendre séparément.

## Composants réutilisables

Créés pour cette page, utilisables partout :

| Composant | Rôle |
|---|---|
| `<x-panel titre icone description>` | Carte avec en-tête (icône, titre, description, slot `actions`) et pied (slot `footer`, pour les boutons d'un formulaire) |
| `<x-field label :value>` | Une information en lecture seule ; valeur vide → « — » ; le slot remplace la valeur (badge, lien…) |
| `<x-menu label icon variant size align>` + `<x-menu-item href\|wire:click icon danger separated>` | Menu déroulant : bouton + entrées avec icône. Positionné en `fixed` pour ne jamais être rogné par un conteneur à défilement. Utilisé par la fiche **et** par la colonne « Actions » des tables |
| `<x-select-input>` | Liste déroulante de formulaire, au style de `<x-text-input>` |
| `<x-badge couleur>` | Pastille de statut (existait déjà) |

Partiels de formulaire partagés entre création et modification :
`livewire/gestion/adherents/formulaire/{photo,profil,adhesion}.blade.php`.

## Ce qui n'a pas changé

Toutes les actions Livewire de la fiche (`changerStatut`, `supprimer`, ayant droit,
personnes à charge, cotisations, droit d'adhésion) et les données passées à la vue
(`cotisations`, `solde`, `releves`…) sont identiques : seule la présentation change.
Le relevé mensuel (`partials/releve-cotisation`) est partagé avec l'espace adhérent.

## Tests

`tests/Feature/Gestion/AdherentFicheEtSectionsTest.php` : rendu de la fiche (dont
l'onglet Sécurité) et des sections de modification, redirection de l'ancien lien,
contrôle d'accès, zone sensible, indépendance des sections, audit, mot de passe
(minimum, génération, non-fuite), journal « Dernières opérations », adhérent sans compte,
création.
Les tests existants de la fiche (`DroitAdhesionTest`, `CotisationManagementTest`…) et
du formulaire (`AdherentManagementTest`…) passent sans modification.

La limite de mémoire des tests est portée à 512 Mo dans `phpunit.xml` : toute la
suite tourne dans un seul processus (~120 Mo de pic) et la limite CLI de 128 Mo était
trop juste, notamment pour l'export Excel.
