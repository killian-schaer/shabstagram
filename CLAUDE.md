# Consignes pour toute session Claude Code sur ce dépôt

## Contexte

Shabstagram est une application PHP/MariaDB de veille sur la Feuille
officielle suisse du commerce (FOSC), avec connexion Entra ID et
notifications par e-mail via Microsoft Graph. Voir `README.md` pour la vue
d'ensemble fonctionnelle et `CLAUDE-files.md` pour le rôle de chaque fichier.

## Règles impératives

- **Pas d'exploration des dossiers voisins.** Ce dépôt vit dans
  `/var/www/claude-apps/shabstagram`, à côté d'autres applications sans
  rapport (`bible`, `budin-qr`, `kocuron`, `kps`, `mailsorter`, `postman`).
  Ne jamais lire ni s'inspirer de leur contenu.
- **Tous les libellés visibles par l'utilisateur vivent dans
  `lang/fr.php`**, accessibles via `Shabstagram\Support\Lang::t()` (ou le
  raccourci global `t()`). Aucune chaîne destinée à l'écran ne doit être
  écrite en dur ailleurs — page PHP, JavaScript, ou gabarit Mustache.
- **Tous les réglages imaginables vivent dans `.env`** (voir
  `.env.example` et `config/config.php`, seul point de lecture de
  `$_ENV`). N'ajouter aucune valeur de configuration en dur dans le code.
- **`AUTH_KILLSWITCH` est dangereux.** Actif, il contourne entièrement la
  connexion Entra ID et ouvre une session d'administration factice
  (`Shabstagram\Auth\Killswitch`), sans jamais créer de ligne dans `users`.
  Ne jamais l'activer par défaut, ni le proposer comme solution à un
  problème de connexion en production.
- **Rédaction de l'interface en français** : vouvoiement systématique,
  orthographe et accents soignés, aucun terme technique visible
  (pas d'« API », de « tenant », de « cron », d'« Entra », de « Graph », de
  « XML », de « webservice »). Le mot « UID » est acceptable : c'est un
  terme métier suisse courant (numéro d'identification d'entreprise), pas
  un détail d'implémentation. Le mot interne « tenant » (table `tenants`,
  code) ne doit jamais fuiter côté utilisateur — on y parle de « feuille
  officielle ».
- **Mention légale obligatoire** : toute page ou tout e-mail qui affiche le
  contenu ou les résultats d'une publication doit rappeler que seule
  l'annonce individuelle au format PDF, munie d'une signature électronique
  qualifiée, fait foi juridiquement (`lang/fr.php` → `results.legal_notice`,
  déjà utilisé dans `templates/mail/new_hits_digest.mustache` et
  `public/searches/results.php` — ne pas dupliquer le texte ailleurs).

## Conventions d'architecture

- Pas de framework : pages PHP discrètes sous `public/`, autoload PSR-4
  `Shabstagram\` → `src/`. Chaque page protégée commence par
  `Session::requireAuth()`.
- Accès base de données exclusivement via les classes de
  `src/Db/Repositories/` (pas de SQL ailleurs, sauf dans les scripts de
  supervision qui doivent explicitement rester indépendants de la base).
- Les publications FOSC sont stockées **une seule fois globalement** par
  GUID (table `publications`), jamais dupliquées par recherche — voir
  `search_hits` (jointure) et `search_hit_notifications` (suivi d'envoi par
  destinataire, séparé de `search_hits` pour permettre une relance
  individuelle en cas d'échec partiel).
- Mustache est réservé au rendu des e-mails (`templates/mail/*.mustache`).
  Les pages web utilisent de simples inclusions PHP (`views/partials/`).
- Extensibilité prévue sans migration : `tenants` (plusieurs feuilles
  officielles futures), `tenants.auth_type` / `Shabstagram\Fosc\FoscAuthStrategy`
  (futures modalités d'authentification côté API), `searches.company_name`
  (future recherche du numéro d'entreprise par nom de société).
- Les deux tâches planifiées (`bin/cron_fosc_check.php` et
  `bin/cron_health_check.php`) doivent rester indépendantes : la seconde
  doit pouvoir alerter même si la base de données est en panne.

## Git / déploiement

- Dépôt distant : `git@github.com:killian-schaer/shabstagram.git`.
- `.env` ne doit jamais être commité (voir `.gitignore`) ; `.env.example`
  doit rester à jour à chaque nouveau réglage ajouté.
- `composer install` ne doit être exécuté que depuis le dossier du projet
  (jamais avec une portée plus large).
