-- Schema de base de donnees pour Shabstagram
-- Moteur : MariaDB (InnoDB, utf8mb4)
-- Ce fichier est fourni pour import manuel (ex: mysql shabstagram < schema.sql).

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- tenants : les differentes feuilles officielles interrogeables.
-- Aujourd'hui une seule (FOSC), mais l'application est concue pour en gerer
-- plusieurs a l'avenir. Cote interface, on ne parle jamais de "tenant" mais
-- de "feuille officielle" ; si une seule ligne est active, aucun selecteur
-- n'est propose a l'utilisateur.
-- ---------------------------------------------------------------------------
CREATE TABLE tenants (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code                VARCHAR(50)  NOT NULL,          -- slug interne, ex: 'fosc'
    label               VARCHAR(255) NOT NULL,          -- nom affichable, ex: 'Feuille officielle suisse du commerce'
    api_base_domain     VARCHAR(255) NOT NULL,          -- domaine de recherche (tenant domain), ex: 'www.fosc.ch'
    primary_tenant_code VARCHAR(50)  NULL,               -- code technique renvoye par l'API (ex: 'shab'), a titre informatif
    -- Prevu pour permettre a l'avenir des modalites d'authentification differentes
    -- si l'API change ou devient payante. Seule la valeur 'none' est utilisee
    -- aujourd'hui (l'API FOSC publique ne demande aucune authentification).
    auth_type           ENUM('none') NOT NULL DEFAULT 'none',
    auth_config_json    TEXT NULL,                        -- reserve : futurs identifiants d'authentification (chiffres/encodes)
    active              TINYINT(1)   NOT NULL DEFAULT 1,
    created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tenants_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- users : utilisateurs de l'application, lies a leur identite professionnelle.
-- Une ligne est creee au premier vrai login ; jamais sous killswitch.
-- ---------------------------------------------------------------------------
CREATE TABLE users (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entra_object_id     CHAR(36)     NOT NULL,           -- identifiant unique de l'annuaire professionnel (claim "oid")
    upn                 VARCHAR(255) NOT NULL,           -- identifiant de connexion / adresse professionnelle
    display_name        VARCHAR(255) NOT NULL,
    -- Recalcule a chaque connexion a partir de l'appartenance au groupe
    -- Entra administrateur (voir ENTRA_ADMIN_GROUP_NAME dans .env).
    is_admin            TINYINT(1)   NOT NULL DEFAULT 0,
    -- Derniere visite du fil d'actualite (vue "feed"), pour marquer les
    -- publications apparues depuis la derniere visite.
    last_feed_visit_at  DATETIME     NULL,
    created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_entra_object_id (entra_object_id),
    UNIQUE KEY uq_users_upn (upn)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- searches : criteres de recherche enregistres par un utilisateur.
-- owner_user_id peut etre NULL (recherche creee sans utilisateur associe,
-- notamment via le mode de secours, ou devenue orpheline apres suppression
-- de son proprietaire) : elle reste alors visible uniquement du groupe des
-- administrateurs, et aucune notification n'est jamais envoyee pour elle.
-- ---------------------------------------------------------------------------
CREATE TABLE searches (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_user_id       INT UNSIGNED NULL,
    label               VARCHAR(255) NOT NULL,
    mode                ENUM('plaintext','uid') NOT NULL,
    keyword             VARCHAR(500) NULL,               -- utilise si mode = plaintext
    uid                 VARCHAR(20)  NULL,                -- utilise si mode = uid, format CHE-XXX.XXX.XXX
    company_name        VARCHAR(255) NULL,                -- reserve : future recherche du numero par nom d'entreprise (non utilise)
    -- Lien symetrique optionnel vers une recherche complementaire (ex: recherche
    -- UID accompagnee d'une recherche textuelle). Sert uniquement a un regroupement
    -- visuel dans les listes ; aucune action en cascade n'est appliquee entre les deux.
    paired_search_id    INT UNSIGNED NULL,
    active              TINYINT(1)   NOT NULL DEFAULT 1,
    created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_searches_owner (owner_user_id),
    KEY idx_searches_active (active),
    CONSTRAINT fk_searches_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_searches_paired FOREIGN KEY (paired_search_id) REFERENCES searches(id) ON DELETE SET NULL,
    CONSTRAINT chk_searches_mode_fields CHECK (
        (mode = 'plaintext' AND keyword IS NOT NULL) OR
        (mode = 'uid' AND uid IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- search_tenants : jointure recherche <-> feuille(s) officielle(s) ciblee(s).
-- ---------------------------------------------------------------------------
CREATE TABLE search_tenants (
    search_id           INT UNSIGNED NOT NULL,
    tenant_id           INT UNSIGNED NOT NULL,
    created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (search_id, tenant_id),
    CONSTRAINT fk_search_tenants_search FOREIGN KEY (search_id) REFERENCES searches(id) ON DELETE CASCADE,
    CONSTRAINT fk_search_tenants_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- search_watchers : destinataires supplementaires des notifications pour une
-- recherche. Ne necessitent pas de s'etre deja connectes a l'application.
-- ---------------------------------------------------------------------------
CREATE TABLE search_watchers (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    search_id           INT UNSIGNED NOT NULL,
    entra_object_id     CHAR(36)     NOT NULL,
    display_name        VARCHAR(255) NOT NULL,
    email               VARCHAR(255) NOT NULL,
    created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_watcher_per_search (search_id, entra_object_id),
    CONSTRAINT fk_watchers_search FOREIGN KEY (search_id) REFERENCES searches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- publications : une ligne par publication FOSC, stockee une seule fois
-- globalement (independamment du nombre de recherches qui la trouvent).
-- ---------------------------------------------------------------------------
CREATE TABLE publications (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id           INT UNSIGNED NOT NULL,
    fosc_guid           CHAR(36)     NOT NULL,           -- identifiant (meta/id) renvoye par l'API
    ref_url             VARCHAR(500) NOT NULL,           -- lien absolu utilise pour recuperer le detail complet
    rubric              VARCHAR(100) NULL,
    sub_rubric          VARCHAR(100) NULL,
    publication_date    DATE         NULL,
    publication_number  VARCHAR(50)  NULL,
    -- Mutable : une publication PUBLISHED peut plus tard passer a CANCELLED.
    -- Un futur cron distinct pourra mettre a jour cette colonne sans migration.
    publication_state   ENUM('PUBLISHED','CANCELLED') NOT NULL DEFAULT 'PUBLISHED',
    registration_office VARCHAR(255) NULL,
    title_fr            VARCHAR(1000) NULL,
    title_de            VARCHAR(1000) NULL,
    title_it            VARCHAR(1000) NULL,
    title_en            VARCHAR(1000) NULL,
    publication_text    MEDIUMTEXT NULL,                 -- texte integral de l'annonce (pour l'affichage en plein texte)
    xml_path            VARCHAR(500) NULL,               -- chemin disque du XML detaille stocke
    pdf_path             VARCHAR(500) NULL,               -- chemin disque du PDF stocke
    fetched_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_publications_tenant_guid (tenant_id, fosc_guid),
    CONSTRAINT fk_publications_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- search_hits : jointure recherche <-> publication trouvee. La cle unique
-- sert a la fois de deduplication de correspondance et d'idempotence lors
-- des relances du cron le meme jour.
-- ---------------------------------------------------------------------------
CREATE TABLE search_hits (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    search_id           INT UNSIGNED NOT NULL,
    publication_id      INT UNSIGNED NOT NULL,
    matched_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_hit_search_publication (search_id, publication_id),
    CONSTRAINT fk_hits_search FOREIGN KEY (search_id) REFERENCES searches(id) ON DELETE CASCADE,
    CONSTRAINT fk_hits_publication FOREIGN KEY (publication_id) REFERENCES publications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- search_hit_notifications : suivi d'envoi PAR DESTINATAIRE. Volontairement
-- separe de search_hits : si un destinataire echoue alors qu'un autre a bien
-- recu sa notification pour le meme resultat, seul l'envoi en echec doit
-- pouvoir etre retente au prochain passage du cron.
-- ---------------------------------------------------------------------------
CREATE TABLE search_hit_notifications (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    search_hit_id       BIGINT UNSIGNED NOT NULL,
    recipient_email     VARCHAR(255) NOT NULL,
    recipient_name      VARCHAR(255) NOT NULL,
    recipient_role      ENUM('owner','watcher') NOT NULL,
    sent_at             DATETIME NULL,                    -- NULL = pas encore notifie avec succes
    attempted_at        DATETIME NULL,
    error_message       TEXT NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_notification_recipient (search_hit_id, recipient_email),
    KEY idx_notifications_pending (sent_at),
    CONSTRAINT fk_notifications_hit FOREIGN KEY (search_hit_id) REFERENCES search_hits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- cron_runs : trace de chaque execution du cron principal, par couple
-- (recherche, feuille officielle interrogee), avec le XML brut recu.
-- ---------------------------------------------------------------------------
CREATE TABLE cron_runs (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    search_id           INT UNSIGNED NOT NULL,
    tenant_id           INT UNSIGNED NOT NULL,
    run_at              DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    params_json         TEXT         NOT NULL,           -- parametres exacts envoyes lors de l'appel
    response_xml_path   VARCHAR(500) NOT NULL,           -- chemin disque du XML brut de la reponse
    total_results       INT UNSIGNED NULL,
    status              ENUM('success','error') NOT NULL DEFAULT 'success',
    error_message       TEXT NULL,
    KEY idx_cron_runs_search (search_id),
    KEY idx_cron_runs_run_at (run_at),
    CONSTRAINT fk_cron_runs_search FOREIGN KEY (search_id) REFERENCES searches(id) ON DELETE CASCADE,
    CONSTRAINT fk_cron_runs_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- events : journal d'audit propre a l'application pour chaque recherche
-- (l'API des feuilles officielles ne propose aucun historique/evenement :
-- ce journal est entierement alimente par le cron et par les actions de
-- l'utilisateur sur ses recherches).
-- ---------------------------------------------------------------------------
CREATE TABLE events (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    search_id           INT UNSIGNED NOT NULL,
    event_type          VARCHAR(50)  NOT NULL,           -- hit_found, notification_sent, notification_failed,
                                                          -- cron_error, search_created, search_updated,
                                                          -- search_toggled, search_deleted
    description         VARCHAR(1000) NOT NULL,
    payload_json        TEXT NULL,
    created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_events_search (search_id, created_at),
    CONSTRAINT fk_events_search FOREIGN KEY (search_id) REFERENCES searches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- Donnee initiale : la seule feuille officielle disponible aujourd'hui.
-- Tant qu'une seule ligne "active" existe ici, l'application ne propose
-- aucun selecteur de feuille officielle a l'utilisateur.
-- ---------------------------------------------------------------------------
INSERT INTO tenants (code, label, api_base_domain, primary_tenant_code, auth_type, active)
VALUES ('fosc', 'Feuille officielle suisse du commerce', 'www.fosc.ch', 'shab', 'none', 1);
