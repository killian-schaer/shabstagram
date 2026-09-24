# Rôle de chaque fichier

Ce fichier récapitule ce que fait chaque fichier du projet. À tenir à jour à
chaque fichier ajouté, renommé ou supprimé.

## Racine

| Fichier | Rôle |
|---|---|
| `bootstrap.php` | Point d'entrée commun à toutes les pages et scripts : autoload Composer, chargement du `.env`, chargement de la configuration, fuseau horaire, démarrage de session. |
| `helpers.php` | Raccourcis globaux `t()` (traduction) et `e()` (échappement HTML) utilisés dans les vues. |
| `composer.json` | Dépendances PHP (dotenv, client OAuth2 Azure, Guzzle, Mustache). |
| `schema.sql` | Schéma complet de la base MariaDB, à importer manuellement. |
| `.env.example` | Modèle de tous les réglages configurables ; à copier en `.env` (jamais commité). |
| `.gitignore` | Exclut `.env`, `vendor/`, et le contenu généré (`storage/`, `var/logs/`, `var/heartbeat/`). |
| `README.md` | Vue d'ensemble fonctionnelle, installation, configuration, cron. |
| `CLAUDE.md` | Consignes pour toute future session de travail assistée sur ce dépôt. |
| `CLAUDE-files.md` | Ce fichier. |

## `config/`

| Fichier | Rôle |
|---|---|
| `config/config.php` | Unique point de lecture du `.env` ; transforme `$_ENV` en tableau de configuration structuré. |

## `lang/`

| Fichier | Rôle |
|---|---|
| `lang/fr.php` | Tous les libellés visibles par l'utilisateur (français). |

## `src/Support/` — utilitaires transverses

| Fichier | Rôle |
|---|---|
| `Env.php` | Accesseurs typés sur `$_ENV` (string/bool/int/csv), utilisés uniquement par `config/config.php`. |
| `Config.php` | Accès en lecture à la configuration chargée par `bootstrap.php`. |
| `Lang.php` | Charge `lang/{locale}.php` et fournit `t($clé, $variables)`. |
| `Uid.php` | Validation et normalisation d'un numéro d'entreprise (format `CHE-XXX.XXX.XXX`). |
| `Flash.php` | Message ponctuel affiché après une action (succès/erreur), stocké en session. |
| `Heartbeat.php` | Lecture/écriture des fichiers de battement de cœur (JSON sur disque) utilisés par les deux crons. |

## `src/Auth/`

| Fichier | Rôle |
|---|---|
| `Killswitch.php` | Détecte le mode de secours (`AUTH_KILLSWITCH`) et fournit l'identité factice associée. |
| `Session.php` | Point d'entrée unique pour connaître l'utilisateur courant et protéger une page (`requireAuth()`), login/logout de session. |
| `EntraAuth.php` | Connexion déléguée des utilisateurs via Entra ID (flux « authorization code »). |

## `src/Fosc/` — API des feuilles officielles

| Fichier | Rôle |
|---|---|
| `FoscAuthStrategy.php` | Interface d'extension pour de futures modalités d'authentification côté API. |
| `NoneAuthStrategy.php` | Implémentation actuelle (aucune authentification requise). |
| `FoscClient.php` | Appels HTTP : recherche, récupération du détail d'une publication, récupération du PDF. |
| `FoscXmlParser.php` | Transforme les réponses XML en tableaux PHP. |

## `src/Graph/` — Microsoft Graph

| Fichier | Rôle |
|---|---|
| `GraphClient.php` | Jeton applicatif partagé (client credentials) et appels HTTP génériques vers Microsoft Graph. |
| `DirectorySearch.php` | Recherche de collègues dans l'annuaire, pour l'ajout de destinataires à une recherche. |
| `MailSender.php` | Envoi d'un e-mail via Microsoft Graph, au nom de la boîte configurée. |

## `src/Notify/`

| Fichier | Rôle |
|---|---|
| `DigestBuilder.php` | Regroupe les notifications en attente par destinataire (un seul e-mail par personne et par exécution). |
| `TemplateRenderer.php` | Rendu des gabarits Mustache (e-mails). |

## `src/Db/`

| Fichier | Rôle |
|---|---|
| `Database.php` | Connexion PDO partagée (`connection()`) et connexion indépendante à délai court (`quickProbe()`, utilisée par la supervision). |
| `Repositories/UserRepository.php` | Utilisateurs (création/mise à jour au login, recherche par identifiant). |
| `Repositories/TenantRepository.php` | Feuilles officielles configurées, et détection d'un besoin d'afficher le sélecteur. |
| `Repositories/SearchRepository.php` | Recherches enregistrées : CRUD, activation/désactivation, rattachement aux feuilles officielles, appariement UID/mot-clé. |
| `Repositories/WatcherRepository.php` | Destinataires supplémentaires (« watchers ») d'une recherche. |
| `Repositories/PublicationRepository.php` | Publications FOSC stockées globalement par GUID. |
| `Repositories/SearchHitRepository.php` | Correspondances (recherche, publication) trouvées, avec déduplication. |
| `Repositories/SearchHitNotificationRepository.php` | Suivi d'envoi par destinataire (regroupement, marquage envoyé/échoué). |
| `Repositories/CronRunRepository.php` | Journal de chaque exécution de recherche (succès/erreur, XML brut). |
| `Repositories/EventRepository.php` | Journal d'audit par recherche (affiché sur la page « historique »). |

## `bin/` — tâches planifiées

| Fichier | Rôle |
|---|---|
| `cron_fosc_check.php` | Cron quotidien : recherche, stockage des preuves, notifications groupées. Écrit le battement de cœur `fosc_search`. |
| `cron_health_check.php` | Cron de supervision indépendant : vérifie le battement de cœur et l'accès à la base, alerte `ADMIN_ALERT_EMAIL` si besoin. |

## `templates/mail/` — gabarits Mustache

| Fichier | Rôle |
|---|---|
| `new_hits_digest.mustache` | Corps HTML de l'e-mail de notification (résultats groupés par recherche, mention légale). |
| `new_hits_digest_subject.mustache` | Sujet de cet e-mail. |
| `health_alert.mustache` | Corps HTML de l'e-mail d'alerte de supervision. |

## `views/` — pages web (hors dossier public, inclus par les pages)

| Fichier | Rôle |
|---|---|
| `partials/header.php` | En-tête commun (navigation, bandeau killswitch, message ponctuel). |
| `partials/footer.php` | Pied commun (scripts Bootstrap). |
| `searches/form.php` | Formulaire partagé de création/modification d'une recherche (mode, avertissement UID, aide sur la recherche par mot-clé). |

## `public/` — pages accessibles directement

| Fichier | Rôle |
|---|---|
| `index.php` | Redirige vers le tableau de bord (après vérification de connexion). |
| `login.php` | Page de connexion (bouton vers Entra ID). |
| `callback.php` | Traitement du retour de connexion Entra ID, création/mise à jour de l'utilisateur, ouverture de session. |
| `logout.php` | Déconnexion. |
| `searches/index.php` | Tableau de bord : liste des recherches, activation/suspension, accès aux actions. |
| `searches/create.php` | Création d'une recherche (avec option de recherche complémentaire en mode UID). |
| `searches/edit.php` | Modification d'une recherche existante. |
| `searches/toggle.php` | Bascule active/inactive (action POST). |
| `searches/delete.php` | Suppression d'une recherche (action POST). |
| `searches/watchers.php` | Gestion des destinataires supplémentaires (recherche d'annuaire en arrière-plan, ajout, retrait). |
| `searches/results.php` | Résultats trouvés pour une recherche, avec la mention légale sur la valeur du PDF signé. |
| `searches/pdf.php` | Fournit le PDF stocké d'une publication trouvée (contrôle d'accès par propriétaire). |
| `searches/events.php` | Historique des événements enregistrés pour une recherche. |
| `assets/css/theme.css` | Surcharges Bootstrap (couleur d'accent). |
| `assets/js/watcher-search.js` | Recherche de personnes en arrière-plan sur la page de gestion des destinataires. |
| `assets/img/logo.svg` | Logo carré (clin d'œil à un appareil photo, signe juridique §). |
| `assets/img/favicon.svg` | Version simplifiée du logo pour l'onglet du navigateur. |
