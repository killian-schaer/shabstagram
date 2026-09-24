# Shabstagram

Application de veille automatisée des publications de la **Feuille officielle
suisse du commerce (FOSC)**. Elle permet à chaque utilisateur de créer des
critères de recherche, d'y associer des collègues, et d'être notifié par
e-mail lorsqu'une nouvelle publication correspondante est publiée.

Le nom de l'application est entièrement piloté par le fichier `.env`
(`APP_NAME`) — le renommer ne nécessite de modifier aucun code.

## Pile technique

- PHP 8.1+ / MariaDB (PDO, sans ORM)
- Bootstrap 5
- Connexion via Microsoft Entra ID (compte professionnel)
- Notifications par e-mail via Microsoft Graph
- Gabarits d'e-mail rendus avec Mustache

## Installation

```bash
composer install
cp .env.example .env
# Compléter .env (voir la section « Configuration » ci-dessous)
mysql -h <hote> -u <utilisateur> -p <base> < schema.sql
```

## Configuration (`.env`)

Toutes les valeurs configurables se trouvent dans `.env.example`, avec un
commentaire pour chacune. Points d'attention :

- **`AUTH_KILLSWITCH`** : si `true`, contourne entièrement la connexion
  professionnelle et ouvre une session d'administration factice (traitée
  comme administratrice), sans jamais créer d'utilisateur réel en base.
  Réservé au développement local — ne **jamais** l'activer en production.
- **Inscription d'application Entra ID** : une seule inscription suffit. Elle
  sert à la fois à la connexion des utilisateurs (permissions déléguées
  `openid`, `profile`, `email`, `User.Read`) et, via un jeton applicatif, à
  l'envoi de courriels, la recherche d'annuaire et la vérification des
  groupes (permissions **applicatives** `Mail.Send`, `User.Read.All`,
  `Group.Read.All`, `GroupMember.Read.All`, à consentir par un administrateur
  du tenant). Renseigner `ENTRA_TENANT_ID`, `ENTRA_CLIENT_ID`,
  `ENTRA_CLIENT_SECRET`, `ENTRA_REDIRECT_URI`.
- **`ENTRA_ADMIN_GROUP_NAME` / `ENTRA_USER_GROUP_NAME`** : noms des deux
  groupes de l'annuaire professionnel qui contrôlent l'accès. Une personne
  doit appartenir à l'un des deux pour se connecter ; l'appartenance au
  groupe administrateur donne accès à l'ensemble des recherches de tout le
  monde (pas de gestion de droits plus fine que ces deux groupes). Si les
  deux valeurs sont laissées vides, aucun contrôle de groupe n'est appliqué.
- **`GRAPH_SENDER_UPN`** : la boîte au nom de laquelle les notifications sont
  envoyées (doit exister dans le tenant et être autorisée par la permission
  `Mail.Send`).
- **`ADMIN_ALERT_EMAIL`** : destinataire des alertes de supervision (voir
  ci-dessous).

## Recherches sans propriétaire

Une recherche peut exister sans utilisateur associé (notamment celles créées
en mode de secours). Elle n'est alors visible et gérable que par les
administrateurs, et **aucune notification n'est jamais envoyée** pour elle —
la préparation des notifications (`Shabstagram\Fosc\SearchSyncService`) est
volontairement conditionnée à la présence d'un propriétaire, l'envoi
(`Shabstagram\Notify\NotificationDispatcher`) n'a alors simplement rien à
traiter.

## Fil d'actualité

La page `/feed.php` liste, par ordre chronologique, les publications
trouvées pour les recherches visibles par l'utilisateur (les siennes, ou
l'ensemble pour un administrateur), avec un repère visuel sur celles
apparues depuis sa dernière visite (`users.last_feed_visit_at`).

## Synchronisation manuelle (bouton temporaire)

Un bouton réservé aux administrateurs, dans la barre de navigation, déclenche
une synchronisation immédiate (`public/admin/sync.php`) **sans jamais envoyer
de courriel** : il n'exécute que la phase de recherche
(`SearchSyncService`), jamais le dispatcher de notifications. Les résultats
trouvés restent en attente et seront notifiés normalement lors du prochain
passage du cron quotidien. Ce bouton est temporaire, prévu pour être retiré
plus tard.

## Les deux tâches planifiées (cron)

Deux scripts indépendants, à ajouter à la planification du serveur :

```cron
0 6 * * * php /var/www/claude-apps/shabstagram/bin/cron_fosc_check.php >> /var/www/claude-apps/shabstagram/var/logs/cron.log 2>&1
5 * * * * php /var/www/claude-apps/shabstagram/bin/cron_health_check.php >> /var/www/claude-apps/shabstagram/var/logs/health.log 2>&1
```

- **`cron_fosc_check.php`** : recherche quotidienne (fenêtre glissante d'au
  moins `CRON_LOOKBACK_DAYS` jours), stockage des preuves brutes (XML, PDF)
  sous `STORAGE_PATH`, puis envoi des notifications regroupées par
  destinataire.
- **`cron_health_check.php`** : totalement indépendant du premier. Vérifie
  que la recherche quotidienne s'est bien exécutée (fichier de battement de
  cœur sous `HEARTBEAT_PATH`) et que la base de données répond, puis alerte
  `ADMIN_ALERT_EMAIL` en cas de problème (avec anti-spam configurable via
  `ALERT_THROTTLE_HOURS`).

## Multi-feuilles officielles

L'application est conçue pour interroger plusieurs feuilles officielles à
l'avenir (table `tenants` en base). Aujourd'hui une seule est configurée
(FOSC, domaine `www.fosc.ch`) : tant qu'une seule ligne active existe dans
`tenants`, aucun sélecteur n'est proposé à l'utilisateur. Ajouter une
deuxième feuille officielle active dans cette table fera apparaître le
sélecteur automatiquement, sans changement de code.

## Hors périmètre de cette version

- Recherche automatique du numéro d'entreprise (UID) à partir du nom de la
  société via le webservice de la Chancellerie fédérale (point d'extension
  laissé dans `searches.company_name`).
- Vérification périodique du passage d'une publication de l'état
  « publiée » à « annulée » (le schéma le permet sans migration :
  `publications.publication_state` est déjà une colonne mutable).
- Modalités d'authentification autres que « aucune » pour interroger une
  feuille officielle (point d'extension dans `tenants.auth_type` /
  `tenants.auth_config_json` et `Shabstagram\Fosc\FoscAuthStrategy`).

## Documentation complémentaire

- [`CLAUDE.md`](CLAUDE.md) — consignes pour toute future session de travail
  assistée sur ce dépôt.
- [`CLAUDE-files.md`](CLAUDE-files.md) — rôle de chaque fichier du projet.
