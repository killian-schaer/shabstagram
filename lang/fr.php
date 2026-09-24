<?php

declare(strict_types=1);

/**
 * Tous les libellés visibles par l'utilisateur vivent ici — aucune chaîne
 * destinée à l'écran ne doit être écrite en dur ailleurs dans le code.
 * Rédaction : vouvoiement systématique, pas de terme technique visible
 * (pas d'« API », de « tenant », de « cron », etc.).
 */
return [
    // --- Général ---
    'general.save' => 'Enregistrer',
    'general.cancel' => 'Annuler',
    'general.delete' => 'Supprimer',
    'general.edit' => 'Modifier',
    'general.add' => 'Ajouter',
    'general.close' => 'Fermer',
    'general.back' => 'Retour',
    'general.confirm' => 'Confirmer',
    'general.yes' => 'Oui',
    'general.no' => 'Non',
    'general.loading' => 'Chargement…',
    'general.actions' => 'Actions',

    // --- Navigation ---
    'nav.dashboard' => 'Mes recherches',
    'nav.feed' => 'Fil d\'actualité',
    'nav.logout' => 'Se déconnecter',
    'nav.new_search' => 'Nouvelle recherche',

    // --- Authentification ---
    'auth.login_title' => 'Connexion',
    'auth.login_intro' => 'Connectez-vous avec votre compte professionnel pour accéder à vos recherches.',
    'auth.login_button' => 'Se connecter avec mon compte professionnel',
    'auth.login_error' => 'La connexion a échoué. Merci de réessayer, ou de contacter un administrateur si le problème persiste.',
    'auth.not_authorized' => 'Ce compte n\'est pas autorisé à accéder à cette application. Merci de contacter un administrateur.',
    'auth.killswitch_banner' => 'Mode de secours actif : la connexion professionnelle est désactivée et une session d\'administration temporaire a été ouverte automatiquement. Ce mode ne doit jamais rester actif en production.',
    'auth.killswitch_display_name' => 'Administration (mode de secours)',
    'auth.logged_in_as' => 'Connecté en tant que {name}',

    // --- Actions d'administration ---
    'admin.sync_button' => 'Lancer une synchronisation (sans envoi de mail)',
    'admin.sync_success' => 'La synchronisation a été effectuée avec succès. Aucun e-mail n\'a été envoyé.',
    'admin.sync_errors' => 'La synchronisation s\'est terminée avec {count} erreur(s). Aucun e-mail n\'a été envoyé. Voir l\'historique de chaque recherche pour le détail.',
    'admin.sync_failed' => 'La synchronisation n\'a pas pu être effectuée.',

    // --- Tableau de bord / liste des recherches ---
    'dashboard.title' => 'Mes recherches',
    'dashboard.empty' => 'Vous n\'avez encore créé aucune recherche.',
    'dashboard.column_label' => 'Nom de la recherche',
    'dashboard.column_mode' => 'Type de recherche',
    'dashboard.column_status' => 'État',
    'dashboard.column_watchers' => 'Personnes suivies',
    'dashboard.status_active' => 'Active',
    'dashboard.status_inactive' => 'Suspendue',
    'dashboard.view_results' => 'Voir les résultats',
    'dashboard.view_events' => 'Voir l\'historique',
    'dashboard.manage_watchers' => 'Gérer les destinataires',
    'dashboard.confirm_delete_title' => 'Supprimer la recherche',
    'dashboard.confirm_delete' => 'Voulez-vous vraiment supprimer la recherche « {label} » ? Cette action est irréversible.',
    'dashboard.paired_with' => 'Associée à la recherche complémentaire « {label} »',
    'dashboard.column_owner' => 'Propriétaire',
    'dashboard.no_owner' => 'Sans propriétaire',

    // --- Création / modification d'une recherche ---
    'search_form.create_title' => 'Créer une recherche',
    'search_form.edit_title' => 'Modifier la recherche',
    'search_form.label' => 'Nom de la recherche (facultatif)',
    'search_form.label_help' => 'Si ce champ est laissé vide, le terme de recherche saisi ci-dessus sera repris comme nom.',
    'search_form.mode' => 'Type de recherche',
    'search_form.mode_plaintext' => 'Recherche par mot-clé',
    'search_form.mode_uid' => 'Recherche par numéro d\'entreprise (UID)',
    'search_form.keyword' => 'Terme à rechercher',
    'search_form.keyword_help_button' => 'Comment fonctionne la recherche ?',
    'search_form.keyword_help_content' => '<p class="mb-2"><b>Opérateurs de recherche disponibles :</b></p><ul class="mb-0 ps-3"><li><i>parfait</i> trouve <i>Parfait SA</i> et <i>Le Contrat est Parfait</i>, mais pas <i>parfaitement</i>.</li><li><i>"Parfait et Cenovis"</i> trouve <i>Parfait et Cenovis SA</i>, mais pas <i>Manger du Cenovis</i>.</li><li><i>Parfait|Cenovis</i> trouve <i>Parfait SA</i> et <i>Cenovis SA</i>.</li></ul>',
    'search_form.uid' => 'Numéro d\'entreprise (UID)',
    'search_form.uid_placeholder' => 'CHE-123.456.789',
    'search_form.uid_invalid' => 'Ce numéro n\'est pas valide (format attendu : CHE-123.456.789, avec un chiffre de contrôle correct).',
    'search_form.uid_warning' => 'Attention : une recherche par numéro d\'entreprise ne permet pas toujours de retrouver toutes les publications pertinentes. Certaines annonces regroupent plusieurs sociétés sans mentionner le numéro de chacune d\'entre elles. Il est recommandé d\'ajouter également une recherche par mot-clé complémentaire.',
    'search_form.add_companion_search' => 'Ajouter également une recherche par mot-clé complémentaire',
    'search_form.companion_keyword' => 'Mot-clé de la recherche complémentaire',
    'search_form.gazette_picker_label' => 'Feuilles officielles à surveiller',
    'search_form.validation_error' => 'Merci de corriger les champs signalés ci-dessous.',
    'search_form.created_flash' => 'La recherche a bien été créée.',
    'search_form.updated_flash' => 'La recherche a bien été mise à jour.',

    // --- Activation / suppression ---
    'search_actions.activated_flash' => 'La recherche a été activée.',
    'search_actions.deactivated_flash' => 'La recherche a été suspendue.',
    'search_actions.deleted_flash' => 'La recherche a été supprimée.',

    // --- Destinataires (watchers) ---
    'watchers.title' => 'Destinataires des notifications pour « {label} »',
    'watchers.intro' => 'En plus de vous-même, vous pouvez ajouter d\'autres collègues qui recevront un e-mail lorsque cette recherche trouve de nouveaux résultats.',
    'watchers.search_placeholder' => 'Rechercher une personne par son nom…',
    'watchers.add_button' => 'Ajouter',
    'watchers.remove_button' => 'Retirer',
    'watchers.empty' => 'Aucun destinataire supplémentaire pour le moment.',
    'watchers.owner_badge' => 'Propriétaire',
    'watchers.added_flash' => 'La personne a été ajoutée aux destinataires.',
    'watchers.removed_flash' => 'La personne a été retirée des destinataires.',

    // --- Résultats / historique ---
    'results.title' => 'Résultats trouvés pour « {label} »',
    'results.empty' => 'Aucun résultat n\'a encore été trouvé pour cette recherche.',
    'results.column_title' => 'Publication',
    'results.column_rubric' => 'Rubrique',
    'results.column_date' => 'Date de publication',
    'results.column_gazette' => 'Feuille officielle',
    'results.legal_notice' => 'Seule l\'annonce individuelle publiée au format PDF et munie d\'une signature électronique qualifiée fait foi juridiquement. Les informations affichées ici sont fournies à titre indicatif et ne remplacent pas la consultation de ce document officiel.',

    'events.title' => 'Historique de « {label} »',
    'events.empty' => 'Aucun événement n\'a encore été enregistré pour cette recherche.',
    'events.type.hit_found' => 'Nouveau résultat trouvé',
    'events.type.notification_sent' => 'Notification envoyée',
    'events.type.notification_failed' => 'Échec de l\'envoi d\'une notification',
    'events.type.cron_error' => 'Erreur lors de la recherche automatique quotidienne',
    'events.type.search_created' => 'Recherche créée',
    'events.type.search_updated' => 'Recherche modifiée',
    'events.type.search_toggled' => 'État de la recherche modifié',
    'events.type.search_deleted' => 'Recherche supprimée',

    // --- Fil d'actualité ---
    'feed.title' => 'Fil d\'actualité',
    'feed.empty' => 'Aucune publication trouvée pour le moment.',
    'feed.new_badge' => 'Nouveau',
    'feed.column_search' => 'Recherche « {label} »',

    // --- E-mail de notification (rendu via Mustache, voir templates/mail) ---
    'mail.digest_greeting' => 'Bonjour {name},',
    'mail.digest_intro' => 'Voici les nouvelles publications trouvées pour vos recherches suivies :',
    'mail.digest_footer' => 'Vous pouvez consulter et gérer vos recherches depuis {app_name}.',
];
