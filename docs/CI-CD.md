# CI/CD de MIACI

MIACI se déploie sur l'hébergement mutualisé cPanel via le kit
**[xsel-deploy-mutualise](https://github.com/ouangni-wangny/xsel-deploy-mutualise)**,
commun à tous les projets XSEL. Ce projet ne porte que trois petits workflows
et ses secrets ; le build, le transfert SSH, `composer install`, les
migrations, la sauvegarde de la base et le healthcheck vivent dans le kit.

Pour le détail des décisions d'architecture (SSH/rsync, déploiement direct,
CI et CD séparés), voir les ADR du kit : `docs/adr/` dans son dépôt.

## Sommaire

- [Vue d'ensemble](#vue-densemble)
- [Fichiers du projet](#fichiers-du-projet)
- [Mise en route (une seule fois)](#mise-en-route-une-seule-fois)
- [Migrer un site déjà en ligne](#migrer-un-site-déjà-en-ligne)
- [Le `.env` de production](#le-env-de-production)
- [Au quotidien](#au-quotidien)
- [Retour arrière](#retour-arrière)
- [Rendre Pint bloquant](#rendre-pint-bloquant)
- [Dépannage](#dépannage)
- [Mettre à jour la version du kit](#mettre-à-jour-la-version-du-kit)
- [Limites](#limites)

## Vue d'ensemble

```
Pull request ──► CI (ci.yml) ──► tests + style + build Vite ──► revue / fusion

Push sur main ──► Deploy (deploy.yml)
                    ├─ job ci ........ ci.yml (mêmes contrôles)
                    └─ job deploy .... needs: ci  → workflow du kit :
                         1. build des assets (npm ci && npm run build)
                         2. préflight serveur (rsync, php, composer, disque)
                         3. rsync --delete du code vers deploy_path
                         4. sur le serveur : composer install --no-dev,
                            config/route/view:cache, sauvegarde DB,
                            migrate --force, storage:link
                         5. healthcheck https://…/up

Toutes les 15 min ──► Monitor (monitor.yml) ──► email GitHub si le site tombe
```

Le déploiement ne part **jamais** si la CI échoue (`needs: ci`), et jamais
depuis une PR ou une branche autre que `main`.

## Fichiers du projet

| Fichier | Rôle |
|---|---|
| [`.github/workflows/ci.yml`](../.github/workflows/ci.yml) | Tests (`php artisan test`, SQLite en mémoire), Pint, build Vite. Déclenché sur PR, appelé par `deploy.yml`, lançable à la main |
| [`.github/workflows/deploy.yml`](../.github/workflows/deploy.yml) | Sur push `main` : CI puis appel du workflow de déploiement du kit. **C'est la seule configuration de déploiement** |
| [`.github/workflows/monitor.yml`](../.github/workflows/monitor.yml) | Healthcheck périodique des URLs publiques |
| [`.htaccess`](../.htaccess) | Réécriture vers `public/` (voir [Configurer cPanel](#4-configurer-cpanel)) |

Côté serveur, le kit gère `<deploy_path>/.deploy-scripts/` (ses scripts,
resynchronisés à chaque déploiement) et `<deploy_path>/.backups/db/` (les
5 derniers dumps). Ne rien y ranger à la main.

## Mise en route (une seule fois)

Ordre conseillé : préparer le serveur et les secrets **avant** de pousser
`main`, pour que le premier déploiement soit déjà complet.

### 1. Clé SSH de déploiement

Sur cPanel → *SSH Access* → *Manage SSH Keys* : générer une clé
`github-actions-deploy`, l'**autoriser** (*Manage* → *Authorize*), télécharger
la clé privée, puis retirer sa passphrase (GitHub Actions ne peut pas la
saisir) :

```bash
ssh-keygen -p -f ~/Downloads/github-actions-deploy   # nouvelle passphrase : vide
```

Noter aussi hôte, port et utilisateur affichés sur l'écran principal *SSH
Access* (`ssh <user>@<host> -p <port>` ; le port n'est souvent pas 22).
Procédure détaillée : README du kit, étapes 1 à 3.

### 2. Créer le dépôt GitHub et les secrets

Le dépôt local est sur la branche `master` et n'a encore aucun commit. Les
workflows se déclenchent sur `main` :

```bash
cd /Users/xselservices/Herd/MIACI
git branch -m master main
gh repo create ouangni-wangny/miaci --private      # crée le dépôt vide, sans rien pousser
```

Puis les 4 secrets (la clé est lue depuis le fichier, jamais collée en
argument, pour ne pas finir dans l'historique du shell) :

```bash
REPO=ouangni-wangny/miaci
gh secret set DEPLOY_SSH_HOST --repo $REPO --body "monserveur.exemple.com"
gh secret set DEPLOY_SSH_PORT --repo $REPO --body "21098"
gh secret set DEPLOY_SSH_USER --repo $REPO --body "moncpaneluser"
gh secret set DEPLOY_SSH_PRIVATE_KEY --repo $REPO < ~/Downloads/github-actions-deploy
```

Un dépôt privé peut appeler le workflow du kit tant que **le kit reste
public** (contrainte GitHub, voir l'ADR-0004 du kit).

### 3. Préparer le dossier sur le serveur

Copier `scripts/bootstrap-app.sh` du kit sur le serveur, puis :

```bash
ssh <user>@<host> -p <port>
DEPLOY_PATH=/home/<user>/<dossier-miaci> STACK=laravel bash bootstrap-app.sh
```

Le script crée `storage/` et un `.env` **vide** (`chmod 600`) : le
déploiement refuse de continuer tant qu'il n'est pas rempli. Voir
[Le `.env` de production](#le-env-de-production).

### 4. Configurer cPanel

Deux options, au choix :

- **Sans manipulation** : laisser le document root cPanel à la racine de
  `deploy_path`. Le [`.htaccess`](../.htaccess) du dépôt réécrit tout vers
  `public/`. Il est versionné exprès : un `.htaccess` posé à la main serait
  supprimé par le `rsync --delete` du prochain déploiement.
- **Recommandé si possible** : cPanel → *Domains* → mettre le document root
  sur `<deploy_path>/public`. Le `.htaccess` racine devient alors inutile
  (inoffensif).

Avec le document root à la racine **sans** le `.htaccess`, le `.env`, le code
et `.backups/` seraient téléchargeables : ne jamais laisser cette combinaison.

### 5. Renseigner les valeurs `TODO` de `deploy.yml` et `monitor.yml`

| Valeur | Où la trouver |
|---|---|
| `deploy_path` | cPanel → *Domains* → *Document Root* (sans `/public`) |
| `health_check_url` et `urls` (monitor) | URL publique + `/up` (healthcheck natif Laravel) |
| `php_bin` / `composer_bin` | `ls -d /opt/alt/php*/usr/bin/php` sur le serveur (CloudLinux). Hébergeur sans CloudLinux : supprimer ces deux lignes |

`php_version: '8.3'` ne concerne que le **build CI** ; la version réellement
utilisée en production est celle de `php_bin`. Le code exige PHP ≥ 8.3
(`composer.json`).

### 6. Premier push

```bash
git status                # vérifier : ni .env, ni .sql, ni vendor/ node_modules/
git add -A
git commit -m "Initial commit"
git remote add origin git@github.com:ouangni-wangny/miaci.git   # si gh ne l'a pas fait
git push -u origin main
```

Suivre le run dans *Actions* (ou `gh run watch`) : `CI` puis `Deploy`, puis
ouvrir `https://…/up`. Le healthcheck du kit fait la même vérification.

## Migrer un site déjà en ligne

Le dépôt contient un dump de la base de production : le site tourne
probablement déjà, déployé à la main. Avant le **premier** déploiement
automatique :

1. **Ne pas committer le dump.** `miacicic_database.sql` contient les données
   personnelles des adhérents ; il est dans le `.gitignore`. Vérifier avec
   `git status` avant le premier commit.
2. **Sauvegarder** : export de la base (phpMyAdmin) et copie du dossier
   (`cp -a <deploy_path> <deploy_path>.avant-ci`, ou téléchargement du File
   Manager).
3. **Vérifier ce que contient `deploy_path`.** `rsync --delete` supprime tout
   ce qui n'est pas dans le dépôt, sauf `.env`, `storage/`, `.backups/` et
   `.deploy-scripts/`. Un fichier ou dossier posé à la main (autre app,
   sous-domaine imbriqué, `.htaccess` local, fichier de config…) disparaîtra.
   Le mettre dans `protect_paths` (un chemin par ligne, relatif à
   `deploy_path`), ou le committer :
   ```yaml
   with:
     protect_paths: |
       autre-dossier
   ```
   Un sous-domaine créé par cPanel vit souvent dans un sous-dossier du
   domaine principal : c'est le cas qui a effacé un dossier entier sur PECI
   (ADR-0002 du kit).
4. **Conserver le `.env` et surtout l'`APP_KEY` existants.** Les changer
   invaliderait les sessions et tout ce qui est chiffré avec la clé.
5. Le déploiement lance `migrate --force`, précédé d'un dump automatique dans
   `.backups/db/`. Les migrations déjà appliquées ne sont pas rejouées.

## Le `.env` de production

À remplir une fois dans `<deploy_path>/.env` (jamais dans le dépôt : le kit
l'exclut du transfert et du `--delete`). Base : `.env.example`, avec ces
valeurs à changer :

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://miaci.ci                # URL publique réelle, en https
APP_KEY=base64:...                      # php artisan key:generate --show (en local)

LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=<prefixe_miaci>
DB_USERNAME=<prefixe_user>
DB_PASSWORD=<mot de passe>

MAIL_MAILER=smtp                        # `log` en local n'envoie rien
MAIL_HOST=…
MAIL_PORT=…
MAIL_USERNAME=…
MAIL_PASSWORD=…
MAIL_FROM_ADDRESS="notifications@miaci.ci"

CINETPAY_API_KEY=…
CINETPAY_SITE_ID=…
CINETPAY_SECRET_KEY=…
# CINETPAY_NOTIFY_URL / CINETPAY_RETURN_URL se déduisent d'APP_URL
```

`APP_KEY` doit être présent **avant** le premier déploiement, car le script
lance `config:cache` avant tout le reste. Le `.env` est lu au moment du
`config:cache` : après une modification manuelle, relancer un déploiement ou
exécuter `php artisan config:cache` sur le serveur.

### Base neuve (pas de données en production)

Base créée dans cPanel → *MySQL Databases* (utilisateur associé avec tous les
privilèges), puis après le premier déploiement, sur le serveur, avec le PHP
de `php_bin` :

```bash
cd <deploy_path>
/opt/alt/php84/usr/bin/php artisan db:seed --class=RolePermissionSeeder --force
/opt/alt/php84/usr/bin/php artisan db:seed --class=TypeSinistreSeeder --force   # types de sinistre du règlement en vigueur
/opt/alt/php84/usr/bin/php artisan tinker
>>> $u = App\Models\User::create(['name' => 'Admin', 'email' => 'vous@exemple.ci', 'password' => 'mot-de-passe-solide']);
>>> $u->assignRole('ADMIN');
```

Le mot de passe est haché automatiquement (cast `hashed` du modèle `User`).
`User::factory()` est à éviter ici : Faker n'est pas installé en production
(`composer install --no-dev`).

**Ne jamais lancer `db:seed` sans `--class`** en production : `DatabaseSeeder`
crée `admin@miaci.ci` / `gestionnaire@miaci.ci` avec le mot de passe
`password`, plus des données de démonstration.

## Au quotidien

1. Créer une branche, pousser, ouvrir une PR : la CI tourne (tests, style,
   build).
2. Fusionner dans `main` : la CI tourne à nouveau puis le déploiement part
   automatiquement.
3. Suivre l'exécution : onglet *Actions*, ou `gh run watch`.

Redéployer sans nouveau commit (par exemple après avoir corrigé le `.env` du
serveur) : *Actions* → *Deploy* → *Run workflow*, ou
`gh workflow run deploy.yml`. Deux déploiements simultanés vers le même
dossier sont mis en file d'attente, jamais annulés en plein rsync.

Les fichiers du dépôt qui n'ont pas à être en production (`tests/`,
`vendor/`, `node_modules/`, `.env*`, `storage/`, `.git/`) sont exclus du
transfert par le kit ; `vendor/` est reconstruit sur le serveur.

## Retour arrière

Il n'y a **pas de rollback automatique** (ADR-0002 du kit : déploiement
direct, sans `releases/`). En cas de déploiement défectueux :

1. Code : `git revert <commit>` puis push sur `main` — redéploie la version
   précédente (le temps d'un cycle CI + déploiement).
2. Base : la sauvegarde d'avant migration est dans
   `<deploy_path>/.backups/db/<date>.sql.gz` (5 conservées). Un `revert` de
   code ne défait pas une migration déjà appliquée ; restaurer si besoin :
   ```bash
   gunzip < .backups/db/20260918103000.sql.gz | mysql -u <user> -p <base>
   ```
   La sauvegarde est « au mieux » : si `mysqldump` est absent ou échoue, le
   déploiement continue sans elle. Une sauvegarde régulière indépendante
   (cPanel → *Backup*) reste nécessaire.

## Rendre Pint bloquant

`vendor/bin/pint --test` échoue aujourd'hui sur une vingtaine de fichiers
(style uniquement : guillemets, ordre des imports…). L'étape est donc en
`continue-on-error: true` dans `ci.yml` : elle affiche l'avertissement sans
bloquer. Pour la rendre bloquante :

```bash
php vendor/bin/pint            # reformate le code
php artisan test               # doit rester vert
git commit -am "Formater le code avec Pint"
```

puis supprimer la ligne `continue-on-error: true` de l'étape *Pint (style)*.

## Dépannage

| Symptôme | Cause probable | Action |
|---|---|---|
| Run `Deploy` échoue à *Configurer la clé SSH* ou `Permission denied (publickey)` | Clé non autorisée dans cPanel, passphrase non retirée, secret mal collé | Refaire les étapes 1-2 ; recharger `DEPLOY_SSH_PRIVATE_KEY` depuis le fichier |
| `Host key verification failed` / timeout SSH | Mauvais `DEPLOY_SSH_PORT` ou hôte | Recopier depuis cPanel → *SSH Access* |
| `php_bin introuvable` / `composer_bin introuvable` | Chemin CloudLinux erroné | `ls -d /opt/alt/php*/usr/bin/php` sur le serveur, corriger `deploy.yml` |
| `.env manquant` | `.env` non créé | Étape 3 (`bootstrap-app.sh`) |
| `composer install` : version PHP non satisfaite | `php_bin` pointe une version < 8.3 | Choisir la version PHP dans cPanel (*MultiPHP Manager*) et pointer le bon binaire |
| `rsync ... exit code 12` | `rsync` serveur trop ancien pour une option | Le préflight affiche la version ; signaler au kit plutôt que contourner ici |
| Healthcheck KO mais le code est en place | Erreur 500 (`.env` incomplet, `APP_KEY` manquant, base injoignable) | Lire `<deploy_path>/storage/logs/laravel.log`, corriger, relancer *Deploy* |
| Site 403/404 après déploiement | Document root sans `.htaccess` ni pointage sur `public/` | Étape 4 |
| Images d'uploads cassées | Lien `public/storage` absent | `php artisan storage:link` (le déploiement le refait à chaque fois) |
| Un fichier du serveur a disparu | Absent du dépôt, supprimé par `rsync --delete` | Le restaurer depuis la sauvegarde, puis le versionner ou l'ajouter à `protect_paths` |
| CI rouge sur *Tests* seulement en CI | Dépendance à l'environnement local (`.env`, MySQL, fuseau…) | Les tests utilisent SQLite en mémoire (`phpunit.xml`) ; reproduire avec `php artisan test` après `cp .env.example .env` |

## Mettre à jour la version du kit

`deploy.yml` et `monitor.yml` référencent le kit épinglé sur `@v1.0.1`. Une
évolution du kit n'affecte MIACI qu'après changement explicite de ce tag :
lire les notes de version, modifier le tag dans les deux fichiers, pousser.
Exception connue : les **scripts serveur** sont toujours ceux de la branche
`main` du kit, même avec un tag épinglé (limite documentée, ADR-0004 du
kit).

## Limites

- **Pas de rollback automatique ni de zéro-downtime** : le code est écrasé en
  place ; une courte fenêtre de fichiers mélangés existe pendant le rsync.
- **Pas d'environnement de staging** : tout push sur `main` part en
  production, la CI est le seul filet.
- **Provisioning cPanel manuel** : base MySQL, document root, `.env`.
- **Sauvegarde DB MySQL/MariaDB uniquement**, non bloquante.
- **Pas de worker de file d'attente** : les emails partent en synchrone
  (voir le README du projet).
- **Pas de rotation de secrets automatisée** : en cas de changement d'équipe
  ou de fuite, régénérer la clé SSH (étapes 1-2) et le mot de passe MySQL
  (puis mettre à jour le `.env` du serveur).
