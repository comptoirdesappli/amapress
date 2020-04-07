# 0.93.75 (2020-04-05)
* **Contrats:** amélioration affichage colonne Résumé (contrat pas encore ouvert à inscription en ligne et contrat récurrents)
* **Imports CSV:**
    - affichage des erreurs en multilignes (pre-wrap)
    - ne pas autoriser l'import CSV de contrats ou configurations de paniers sur des contrats ayant des inscriptions + option pour Autoriser la modification dans ce cas + option pour réimporter toutes les configurations de paniers si aucune inscription en cours (pour respecter l'ordre d'import) ;
* **Shortcodes:** 
    - refactoring et amélioration de l'affichage du shortcode users_near
    - variantes [display-if-xxx] où xxx peut être logged, not-logged, intermittent, no-contrat, responsable-distrib (est responsable de distribution cette semaine), responsable-amap (peut accéder au Tableau de Bord)

# 0.93.65 (2020-04-05)
* **Gardiens de paniers:** inscription gardiens de paniers (et mode admin) + option général d'activation + enregistrement/désaffection des gardes avec mails de confirmation + affichage distance des gardiens par rapport à l'amapien connecté + affichage des gardes de paniers dans la liste d'émargement + rappels aux gardiens et aux amapiens avec paniers gardés

# 0.93.45 (2020-04-04)
* **Rôles:** Producteurs, pas de publications d'articles
* compatibilité avec Autoptimize (chargement des cartes Leaflet après le chargement du document)
* **Contrats:** check des placeholders et de la validité du modèle DOCX (contrat personnaisé, contrat vierge et bulletin d'adhésion) en notice erreur/warning dans Edition Contrat
* **Emails groupés:** 
    - alertes de configuration du SMTP du compte si le nombre de membres est supérieur à 25 et dans Etat Amapress
    - membres et sans modérateurs (Trésoriers, Responsables, Rédacteurs...)
* **Etat Amapress:** 
    - check des placeholders et de la validité du modèle DOCX (contrat personnaisé, contrat vierge et bulletin d'adhésion) dans la section Inscription en ligne
    - sauvegarde, affichage de la configuration DB/Fichiers d'UpdraftPlus

# 0.93.30 (2020-04-03)
* **Etat Amapress:** 
    - check de l'état de sauvegarde d'UpdraftPlus
    - Stats DB, affichage de toutes les options avec leur dump
* paramètre d'expiration de sessions (courte et longue - Se souvenir de moi coché)
* paramètres d'envoi des notifications de création de comptes et de changement de mot de passe (séparé Responsables/Amapiens)

# 0.93.25 (2020-04-02)
* **Etat Amapress:** configuration préinscription, shortcode mes-contrats, suggestion de l'argument email vers adresse boite contact
* **Gestion Contrats:** Quantités à la prochaine distribution, tri des contrats par date de début puis nom
* **Rôles:** 
    - autoriser responsables, coordinateurs, référent à gérer les demandes d'adhésions
    - autoriser tous les rôles avec accès au Tableau de bord à publier des articles
    - ne pas autoriser les producteurs, rédacteurs et coordinateurs à supprimer contenu des autres
* **Shortcodes:** mes-contrats, gestion de l'argument "email"
* **Contrats:** colonne Résumé avec le type, les prinpales options de paiement et l'état d'ouverture des inscriptions en ligne

# 0.93.15 (2020-04-02)
* **Messagerie:** suppression contenu SMS (obsolète)
* ajout filtrage des utilisateurs par "Nom complémentaire" de contrats
* **Demandes d'adhésions:** ajout email et contrats + refactoring, class et clean
* **Emails groupés:** 
    - ajout "Inclure les demandes d'adhésion"
    - Modérateurs, ajout des rôles descriptif individuellement ; Membres : Tous les utilisateurs enregistrés, Contrats par nom complémentaires, Référents Producteurs, Amapiens jamais connectés, Amapiens avec adhésion, Amapiens sans adhésion, Amapiens avec adhésion sans contrat, Amapiens sans contrat, Amapiens avec contrat, Co-adhérents, Amapiens avec co-adhérents + ajout filtrage des utilisateurs avec adhésion en cours
* **Espace Intermittents:** mode admin pour céder les paniers à la place des amapiens et affecter directement le repreneur + notification par mail
* **Messagerie:** 
    - affichage des listes de diffusions et Emails groupés configurés
    - Emails aux amapiens, lien vers Messagerie et suppression SMS aux amapiens

# 0.93.0 (2020-03-28)
* **Distributions:** 
    - Report vers distribution non existante, labels 'distribution exceptionnel' et interface de création (choix date et lieux) ; le choix des contrats se fait en déplaçant des paniers vers cette nouvelle distribution.
    - suppression Emails/SMS aux amapiens (car limite technique de sms: et mailto: )
* **Pages de listes** (contrat, inscriptions...), possibilité de choisir les colonnes visibles par défaut
* **Email groupés:** 
    - envoi sur smtp externe dans des mail queues séparées et envoi individuel (et non avec tous les destinataires en Bcc) + limitation mail par heure séparée
    - amélioration saisie ports IMAP/SMTP + check SMTP
    - envoi des bounces/mailer-daemon à l'admin
    - meilleure gestion des retours mailer-daemon
* **PWA:** Android/iOS meilleur prompt
* **Système intermittents:** option Autoriser la cession partielle de paniers
* **Contrats:** filtrage colonnes visibles par défaut
* **Création Utilisateur:** email, nom et prénom requis
* **Frontend:** 
    - correction liens précédent/suivant dans la détails d'un article (distribution, evenement, producteur...)
    - correction tri par défaut des pages catégories et archives
* **Mail Queue:** amélioration saisie port SMTP
* **pwa:** prompt installation PWA uniquement sur la home
* **Rappels:** rappel individuel des commandes à la prochaine distribution, cas "pas de produit"
* filtrage colonnes visibles par défaut pour toutes les vues

# 0.92.120 (2020-03-22)
* **Génération contrats:** placeholder 'option_paiements' (ne pas utiliser 'mention pour les paiements') + placeholder 'paiements_mention', correction bug decode html entities (pour DOCX)

# 0.92.115 (2020-03-22)
* **Rappels:** 
    - option pour envoyer un rappel individuel à chaque amapien avec le contenu de son panier à la prochaine distribution
    - refactoring UI, rappel à tous les amapiens à la prochaine distribution

# 0.92.105 (2020-03-09)
* Paiement en monnaie locale

# 0.92.100 (2020-03-05)
* activation du bouton Editer (admin bar) pour les types publiques (producteur, production, distrib...)
* support du filtrage des commentaires et du post de commentaire seulement loggué pour les types d'évènements privés (distrib, visites, ag..)

# 0.92.95 (2020-03-04)
* Log des erreurs JS (si log actif)

# 0.92.85 (2020-02-28)
* aide à la recherche d'adresse non résolvable par le service de géolocalisation
* **Etat Amapress:** recommandation plugin Email Subscribers & Newsletters et SecuPress
* support des commentaires (visites, distrib, recettes, produits, ag, évènements...)

# 0.92.75 (2020-02-28)
* **Inscription Partielles:** gestion des inscriptions partielles suivant option 'Autoriser la co-adhésion partielle sur seulement certains contrats'
* **pwa:** 
    - options du manifest (short_name, theme_color, display) et plugin Autoptimize
    - prise en charge Progressive Web App
    - renommages et contrainte à 25 caractères
* ajouter Rafraichir cache Github Updater dans update-core.php

# 0.92.55 (2020-02-21)
* **Etat Amapress :** refactoring plugins (Recommandés, Non recommandés, Fonctionnalités supplémentaires, Utilitaires/Maintenance)

# 0.92.50 (2020-02-13)
* Option "Autoriser la co-adhésion partielle sur seulement certains contrats"

# 0.92.45 (2020-02-11)
* Option "ne pas apparaître sur le trombinoscope" (assistant inscription, mes infos, profil)
* Limitation distance carte des amapiens/autres carts à AMAPRESS_MAX_MAP_DISTANCE (km, par défaut, 300)

# 0.92.35 (2020-02-10)
* **Rappels :** 
    - liste émargement/responsable distribution, option To/Bcc
    - afficher de la date d'envoi sur les liens de tests/renvois
* **Assistant inscription/Mes contrats :** option ignore_renouv_delta (true par défaut) pour masquer immédiatement les contrats terminés

# 0.92.25 (2020-02-10)
* Augmentation report panier/distrib à 4 mois

# 0.92.15 (2020-02-05)
* Agrandissement zone heures recall editor
* Refactor readme + WP minimum 4.6

# 0.92.5 (2020-01-29)
* Changement icônes lieu de distribution et produit par défaut
* Mail mise à jour: suppression lien changelog

# 0.92.0 (2020-01-29)
* Mise à jour des infos Amapress pour GitHub Updater + readme + icônes

# 0.91.135 (2020-01-24)
* Profil utilisateur: fonction actuelle, liens pour éditer les rôles
* Row action supprimer du collectif, suppression affectation référent

# 0.91.115 (2020-01-22)
* **Utilisateur :** row action (dans profil), Supprimer du collectif (role=amapien et plus d'étiquettes)

# 0.91.110 (2020-01-21)
* **Mailing groups :** Archives, correction tri par date
* **Inscription distribution :** correction CSS pour column_date_width

# 0.91.105 (2020-01-16)
* **Emails groupés/Mailing lists :** lien pour voir les membres des différentes catégories configurables Messagerie : affichage des destinataires
* **Recall Quantités à livrer :** mail paramétrable séparemment pour les paniers modulables.

# 0.91.100 (2020-01-15)
* **Présentation producteur et contrat :** lien d'inscription (vers inscription ou mes contrats)
* Placeholders %%lien_mes_contrats%% et %%lien_inscription_contrats%%
* **Edition contrat :** agrandissement de la colonne Fact. Quant.
* **Inscription distribution :** column_date_width pour changer la largeur de la colonne Dates/Produits
* Picto contrat non affiché si size = medium_large, large ou full
* **Editer le collectif :** lien vers les sections référents
* **Paniers intermittents :** picto et suppression texte + clean + picto panier défaut
* Description Amap Rédacteur
* Ne pas afficher les infos du producteur (email, tel...) si non connecté
* Amélioration interface édition inscription panier modulable, back office

# 0.91.95 (2020-01-13)
* **Placeholder lien_inscription_distrib (générique) :** lien vers la page d'inscription aux distributions
* **Assistant d'inscription :** lien retour vers les contrats si amapien a déjà une inscription à tous
* **Mail queue :** clean errored mail like logged mails
* **Recall Responsable manquant :** placeholder %%lien_inscription%%, lien vers la page d'inscription (mode connecté)
* **Emails groupés et mail queue :** doc sur imap/pop/smtp ovh et ports
* **Emails groupés :** affichages des modérateurs dans la liste
* **Profil Utilisateur :** indication membres des mailing-listes et mailing groupes avec lien vers les membres + affichage 'Membres de ' à la place de 'Utilisateurs'
* **Etat Amapress :** vue stats WP DB
* **Editeur de page :** zone Amapress Aide avec lien vers aide des shortcodes
* **Import Utilisateurs :** lien d'édition et recherche d'amapien existant vers nouvel onglet
* Plus d'explications dans les onglet Imports CSV

# 0.91.85 (2020-01-10)
* **Liste émargement :** 
    - option ne pas afficher la liste d'émargement si uniquement paniers modulables + indication distribution sur titre Détails panier modulable
    - détails paniers modulables affichage "en tout"
* **Menu Amapress :** lien vers page inscription distribution
* **Assistant inscription :** allow_inscription_all_dates pour autoriser l'inscription à partir de toutes les dates, y compris celles après la date de clôture du contrat
* **Contrat Word :** explication sur l'utilisation du contrat global
* **Quantités à livrer :** 
    - option "masquer les lignes vides" (si pas avec adhérents et pas avec dates)
    - correction du comptage avec adhérent si une seule inscription

# 0.91.75 (2020-01-09)
* **Emails groupés :** option Renvoi mail de demande de modération + mail de rappel (deux fois par jour) si mails en attente de modération
* **SMTP Mail Queue :** ignore invalid utf 8 while json encode to ensure proper saving of mail in queue
* **Quantités à livrer :** 
    - résumé "en tout", afficher le nombre d'adhérents
    - highlight des liens de la vue active (recap, prochaine distrib, groupage) + afficher/masquer montants et amapiens

# 0.91.70 (2020-01-08)
* **Shortcode doc :** adhesion-request-count
* **Inscription distribution anonyme :** si connecté fallback vers le mode connecté
* **Emails groupés :** affichage du nombre de mails en attente dans le menu
* Substituer les shortcodes dans le titre du back office
* **Assistant inscription :** si connecté et pas de clé, alors proposer les liens vers l'assistant avec clé et "mes contrats"
* **Customizer, agenda :** couleurs pour "inscrit" + couleurs pour évènements intermittents + couleurs pour encaissements
* **Recall Quantités à livrer :** placeholders producteur_paniers_quantites_amapiens et producteur_paniers_quantites_amapiens_prix
* **Quantités à livrer :** 
    - affichage adhérent par défaut uniquement pour paniers modulables
    - affichage "Pas de livraison" si aucune quantité à livrer
    - amélioration affichage prochaine distribution
    - correction compte ¤Toutes¤ (paniers modulables) si pas de commande pour une date + correction affichage "En tout" si affichage amapiens + correction prochaine distribution le jour même

# 0.91.40 (2020-01-07)
* **Recall Quantité à livrer :** 
    - cacher les filtres et liens si placeholder
    - option ne pas envoyer aux référents
    - option filtrage prod_id pour test envoi
    - correction envoi au producteur
* **Recall Responsable distribution :** option pour envoyer les listes d'émargement complète et/ou avec les contrats distribués
* **Customizer :** gestion couleurs pour évènements, visites, lieu des visites par producteur, par contrat, type d'évènement avec icone + refactoring classement
* **ICAL :** X-AMPS-ICON pour passer une image/icone et X-AMPS-CSS (correction) pour passer des classes CSS
* **Full calendar :** affichage icone passée par x-amps-icon + gestion className + option icon_size

# 0.91.25 (2020-01-03)
* **Datatable (front) :** row print button js overidde
* **Assistant inscription :** récap sommes dues affichage date début contrat
* **Finances :** correction affichage adhérents (max)
* **Quantité à livrer :** 
    - correction affichage "panier déplacé" quand show_all_dates
    - lien afficher par date + afficher amapiens
    - affichage par mois/trimestre uniquement pour affichage par date
* **Assistant inscription :** annulation, suppression totale de l'inscription (au lieu de trash)
* Ne pas comptabiliser les adhésions dans la corbeille
* **Mes contrats :** affichage des contrats en cours même si hors période d'inscription
* Amélioration breadcrumb footer

# 0.91.15 (2019-12-29)
* **Contrat :** diminution/report d'un jour de la date de clôture (utile pour les contrats commandes)
* **Mail send :** throw empty mail
* **Emails groupés :** ensure mail placeholder not empty

# 0.91.0 (2019-12-20)
* Ajout état de financement des producteurs

# 0.90.105 (2019-12-19)
* **Assistant inscription :** page Calendrier des livraisons (show_calendar_delivs)

# 0.90.100 (2019-12-19)
* **Calendrier/Ical :** prise en compte des catégories amap_event (nom et classes) et visite producteurs
* **Menu Amapress :** lien vers inscription intermittent
* **Paniers modulables :** afficher/renvoyer uniquement les dates restantes avec "commande"
* **Edition inscription :** prendre en compte la date de cloture contrat
* Filtrage producteur (et accès quantités à livrer)
* Stats paniers modulables
* **Quantités à livrer :** groupage par date, mois, trimestre

# 0.90.95 (2019-12-17)
* **Shortcode listes-diffusions :** accès listes de membres et configuration
* **Shortcode bouton webcal :** agenda-url-buttont
* **Rappels de livraison :** 
    - placeholders avec montants et option montants pour les paniers modulables
    - option envoyer directement aux producteurs (en plus des référents)
* **Assistant inscription :** affichage récap livraisons par date ou producteur / show_delivery_details

# 0.90.85 (2019-12-16)
* **Paniers modulables :** défaut sélecteur quantité 0>25:1
* **Inscription/Règlements et Adhésions/Règlements :** 
    - row action/action group "Marquer reçu" / "Marquer Non reçu"
    -filtrage type de paiement et état reçu/non reçu
* **Inscription contrat éditeur paiement :** gestion autres types de paiement au prépare chèques
* **Assistant inscription :** 
    - autres types de paiements = 1 réglement
    - option show_due_amounts / Récapitulatif des sommes dues
    - ne pas envoyer le mail réponses aux questions si l'amapien n'a pas répondu du tout
* **Quantités à livrer :** 
    - pas d'affichage des lignes vides pour les paniers modulables
    - affichage du montant total si affichage des montants
    - refactoring colonnes si panier modulable et refactoring un seul lieu

# 0.90.75 (2019-12-13)
* **Quantités à livrer :** 
    - gestion rowGroup + export titre + export Print groupé par date
    - option colonne Montant
* **Assistant inscription :** ne pas lister les contrats clôturé (case à cocher Fermer le contrat)

# 0.90.55 (2019-12-12)
* **Shortcode mes-contrats :** option allow_inscriptions et allow_adhesion (pour ne fournir qu'une liste readonly)
* Refactoring shortcodes inscription-en-ligne, mes-contrats, inscription-en-ligne-connecte
* **Inscription distrib :** option show_contrats_desc et show_contrats_count
* **Inscription distrib anonyme :** correction inscr_all_distrib
* **Inscription distrib anonyme :** lien direct
* Assistant d'inscription conf key exemple

# 0.90.45 (2019-12-10)
* **Assistant d'inscription (mes-contrats) :** 
    - doc des arguments utilisables
    - prise en compte agreement avant adhésion
* **Quantités à livrer :** 
    - paniers modulables (pas de ligne 'toutes') + ne pas afficher les lignes vides si toutes les dates
    - paniers modulable, pas de tri des produits
* **Assistant inscription :** 
    - bouton annuler dans liste des contrats
    - afficher toutes les inscriptions en cours (même si pas subscribe)
* **Editeur paniers/quantités :** unit par défaut
* Export CSV Productions

# 0.90.35 (2019-12-09)
* **Menu :** filtrage des liens personnalisés pointant vers des pages protégées
* **Assistant inscription :** 
    - refactoring modification de contrat (personnalisation du message) et possibilité d'annulation
    - argument contrat_print_button_text/adhesion_print_button_text pour personalisé le texte du bouton Imprimer

# 0.90.20 (2019-12-09)
* **Assistant inscription :** message si pas de clé sur le shortcode * être utilisé qu'en mode anonyme avec clé

# 0.90.15 (2019-12-06)
* **Assistant inscription:** 
    - adhésion, ne pas afficher Adhésion AMAP ou Adhésion Réseau si montant 0
    - numéroation des étapes différente si [mes-contrats] ou [inscription-en-ligne]
    - gestion paiement à la livraison (sans gestion de saisie de ces paiements et ajustement)
    - affichage lien et adresse lieu (même si un seul lieu)

# 0.90.0 (2019-12-03)
* **Assistant préinscription :** 
    - édition des inscriptions
    - option autoriser édition des inscriptions jusqu'à leur début et si pas validée
* Interdiction mise à la corbeille depuis Gutenberg (si pas droit de suppression)

# 0.89.135 (2019-11-29)
* **Quantités producteur :** 
    - liens pour accéder aux différents formats (récap global, récap pour les dates suivantes, récap par date/prochaine distribution)
    - option show_all_dates avec groupage par date + affiche first date contrat
    - clarification vue 'à partir de tel date' et lien retour 'date now'
    - listes des prochaines dates

# 0.89.125 (2019-11-27)
* **Trombinoscope :** Rôles dans l'AMAP
* **Rappels contrats à renouveler :** exclusion producteur
* Aide colonne Fact. quant.
* **Utilisateurs :** colonne type d'adhérent
* **Etat :** 
    - nouveaux shortcodes à configurer
    - lister les sous dossiers de docspace
* Ensure message queue delete

# 0.89.105 (2019-11-26)
* **Assistant inscription :** 
    - panier modulable, recopieur de quantité pour les dates suivantes
    - max_produit_label_width
    - page détails de contrat
* Remplacement référencement vers unpkg par des copies locales (isotope et leaflet)
* Bulletin placeholder paiement_date
* Aide/placeholders recherchables

# 0.89.95 (2019-11-25)
* **Emails groupés :** 
    - check config envoi par SMTP
    - option envoi par SMTP
* Filtrage backoffice Producteur
* **Imports CSV :** 
    - contrats (lieux requis, calendrier initial requis, import type de contrat, nb paiements requis,
    - gestion du contrôle des multicheck (simples, posts, users) et des multidates

# 0.89.70 (2019-11-22)
* **Genération DOCX papier :** 
    - gestion newlines
    - gestion placeholders contrats génériques
* **Import CSV :** option Ignorer les colonnes inconnues
* **Etat Amapress :** lien En savoir plus sur les plugins suggérés vers la page du plugin sur wordpress.org

# 0.89.60 (2019-11-21)
* Version Amapress dans Footer Tableau de bord
* Shortcode responsable-distrib-info pour afficher un message aux responsables de la semaine ou de la semaine suivante.
* Lien mailto en bcc pour page distribution (envoi mail à tous les amapiens)
* **Liste émargement :** correction double prise en compte saut de page (paniers modulables)
* Harmonisation et accès à jQuery par $ du document ready (compatibilité WP5.3)

# 0.89.50 (2019-11-20)
* **Inscription distribution :** allow_resp_dist_manage pour autoriser les responsables de distributions à gérer les inscriptions le temps de la semaine où ils sont inscrits
* **Liste émargement :** saut de page entre les détails des paniers modulables
* **Shortcode mes-contrats :** ne pas proposer l'inscription contrats si l'adhésion AMAP est requise

# 0.89.35 (2019-11-19)
* **Assistant inscription :** réglage hauteur de l'éditeur de paniers modulables

# 0.89.30 (2019-11-18)
* Amélioration interface assistant panier modulable
* Amélioration interface saisie quantités

# 0.89.20 (2019-11-15)
* **Messagerie :** Reply-To user si restriction DMARC

# 0.89.15 (2019-11-15)
* **Etat :** check SPF record
* Placeholders adherent.code_postal/ville/rue
* Constante AMAPRESS_NOTIFY_UPDATES pour désactiver la notification des mises à jour
* **Messagerie :** gestion DMARC

# 0.89.0 (2019-11-14)
* Gestion paiement par virement (contrat)
* Cacher Contact Forms si pas droits manage_options
* **Assistant inscription :** 
    - option send_adhesion/contrat_confirm (email à l'amapien) + personnalisation des messages 8/8 et final
    - message adhésion personnalisable et placeholder %%print_button%%
    - pré répartition des dates des chèques
    - paiement_info_required pour rendre obligatoire la saisie des chèques

# 0.88.100 (2019-11-13)
* Colonne dernière connexion
* **Admin Utilisateur :** affichage trié par last_name par défaut
* **Paniers modulables et liste émargement :** tri par nom
* **Paniers modulables :** suppression icône panier
* **Rôle :** Amap Rédacteur pour autoriser des amapiens à publier des articles/recettes

# 0.88.90 (2019-11-08)
* **Assistant inscription :** saisie numéro/banque chèque adhésion
* Séparation modèles de contrats générique et pour paniers modulables + modèle simple texte + bulletin avec paiement_numero/banque

# 0.88.75 (2019-11-06)
* Amélioration affichage archives évènements
* **Panier modulables :** remplacement AvailFrom/To par listes de dates spécifiques
* Prise en compte default_orderby front non connecté

# 0.88.65 (2019-11-05)
* **Menu générateur des latest posts :** 10 par défaut et order by comme définis dans la déclaration du type
* liste-inscription-distrib: doc + max_dates
* Documentation shortcodes
* Exception si envoi mail sans $to ou $message

# 0.88.55 (2019-11-02)
* Description des sections de tableau de bord

# 0.88.50 (2019-10-31)
* **Etat :** 
    - check et affichage des type de cotisation
    - suggestion création Producteur/production... envoyer vers liste plutôt que post-new
* **Référencement :** ajout aide et suppression Yahoo (qui utilise Bing maintenant)
* **Modèles excel :** description en commentaires

# 0.88.40 (2019-10-30)
* Mail subject html entities decode + send to admin if $to is empty

# 0.88.35 (2019-10-29)
* Contrat placeholders tous_referents_contacts et referents_contacts
* **Contrat Ordre :** nom du producteur par défaut

# 0.88.30 (2019-10-28)
* Lien aide assistant pré inscription
* **Etat :** affichage domaine dans suggestion shortcode pré inscription
* **Contrat vierge/psersonnalisé :** placeholders paiements_ordre et paiements_mention
* **Contrat vierge :** ajout placeholder référents
* **Agenda ICAL :** ajout since_days

# 0.88.25 (2019-10-25)
* **Assistant pré inscription :** questions aux nouveaux

# 0.88.20 (2019-10-23)
* **Intermittents :** réservation paniers, option allow_amapiens pour ne pas ouvrir aux amapiens non intermittents
* Php warning fix + UTF8 sans bom

# 0.88.15 (2019-10-22)
* Saisie chèques non readonly + report livraison paginé
* **Emails groupés :** stocke from réel pour affichage et envoi dmarc conforme + archive mail non modéré

# 0.88.0 (2019-10-21)
* **Assistant préinscription en ligne :** intégration saisie des chèques + option pour autoriser dans contrat
* Intégration tableau des paiements et placeholders paiements dans génération contrat/inscription
* **Responsable/Coordinateur/Référent :** droit d'éditer/publier des pages
* **Quantités prochaine distrib :** 
    - correction comptage si panier déplacé
    - lien distrib suivante + autres distrb du même contrat

# 0.87.115 (2019-10-18)
* Responsable rôles par lieu
* **Etat Amapress :** check méthode màj
* **Liste émargement :** affichage commentaire dans export PDF
* **Génération contrat/bulletin :** correction prise en compte placeholder générique (nom_site...)

# 0.87.100 (2019-10-17)
* Gestion espaces dans codes postaux

# 0.87.95 (2019-10-16)
* Responsable de distribution supplémentaires au niveau contrat
* Explication référents producteurs/production
* Explication Rediriger non connectés vers

# 0.87.80 (2019-10-14)
* **Menu :** possibilité d'ajouter les derniers posts dans un sous menu (Articles/Evenemenents...)
* **Produits :** autoriser association multiples producteurs
* **Emails groupés :** 
    - correction affichage emails from
    - mail de modération, affichage inline du contenu du mail à modérer

# 0.87.70 (2019-10-11)
* autocomplete=off sur les champs de login/mot de passes (SMTP, mailing..)
* **Etat Amapress :** mailing links target blank
* **Emails groupés :** gestion des domaines avec DMARC restrictifs (remplacement du from par l'email de la liste, et ajout de X-Original-From), par ex, pour Yahoo pour éviter les bounces

# 0.87.60 (2019-10-10)
* Implémentation liens tests et renvois des rappels (echo d'information et titres)
* **Rappels :** liens tests et renvoi

# 0.87.55 (2019-10-09)
* **Row actions :** géolocaliser Producteur et Lieu de distribution
* Amélioration mail notification mises à jour
* **Etat Amapress :** ajout section mailing

# 0.87.45 (2019-10-08)
* Notification de l'admin des mises à jours
* Shortcode amapress-panel
* **Shortcode trombinoscope :** show_principal_only
* Option Lieu principal ; n'afficher sur la carte que les lieux principaux

# 0.87.20 (2019-10-07)
* **Email groupés :** 
    - modérateurs et free, membres des destinataires
    - correction deleteMessage
    - check connexion (message raccourci) + IMAP SSL par défaut

# 0.87.10 (2019-10-03)
* Intégration Here Maps pour la géolocalisation

# 0.87.0 (2019-10-02)
* Intégration code de vérification de sites (Google/Bing/Yahoo) et autres entêtes custom
* **Etat Amapress :** pas de contrat actif (ouverture/cloture) warning (au lieu de error)
* **ICAL :** correction bug calcul daylight saving/changement heure
* **ICS Viewer :** affichage date/time en timezone 'local'

# 0.86.110 (2019-10-01)
* **Etat Amapress :** correction affichage rappels
* **Emails groupés :** option raw emails
* **Rappel :** ne pas proposer placeholder me/dest

# 0.86.100 (2019-09-30)
* Affichage mémoire utilisée dans footer admin
* Pas d'erreur si UpDraftPLus n'est pas actif si FREE_PAGES_PERSO
* Pas de suggestion GitHub Updater si FREE_PAGES_PERSO
* **Etat Amapress :** 
    - import CSV inscriptions, check sur active contrats + typo
    - correction nommage fonction info système
* **Assistant inscription admin :** messages pour contrat et paniers complets

# 0.86.90 (2019-09-27)
* **Espaces documents :** attribut title et title_tag
* Admin Breadcrumb footer text
* Ajout lien section référents producteurs

# 0.86.80 (2019-09-26)
* **Recall :** responsable(s) de distribution manquant(s)

# 0.86.65 (2019-09-23)
* **Etat Amapress :** 
    - ajout plugin Non recommandé (Activity Log par ex, ralentissement du site)
    - affichage informations système (DB, limites...)
* **Mailing list/group :** ajout des rôles dans l'AMAP individuellement
* Modèle Bulletin Adhésion DOCX générique (dans Période Adhésion)
* Breadcrumb backoffice with tab name

# 0.86.45 (2019-09-20)
* Interdiction visibilité privée pour Producteur/Production/Contrat/Inscriptions
* **Distributions :** filtre "Année précédente"
* **Etat Amapress :** ne plus suggérer Activity Log (ralentit WordPress)

# 0.86.40 (2019-09-19)
* Admin Breadcrumb in footer
* Better test FREE_PAGES_PERSO

# 0.86.30 (2019-09-18)
* Shortcode amapress-latest-posts si latest-post-shortcode est actif
* **Row action :** génération Bulletin Adhésion DOCX/PDF
* Lien vers wiki dans Aide et menu Amapress
* Utilisateur - Non renouvellement pas dans CSV Import

# 0.85.65 (2019-09-18)
* **Etat Amapress :** ne plus suggérer Re-welcome
* **Users :** Typo et row action Renvoyer mail bienvenue
* Notice mail en erreur d'envoi

# 0.85.45 (2019-09-17)
* Etat Amapress: affichage mode Free Pages Perso et mail brutes
* **Free Pages Perso :** permaliens /index.php/ et check structure dans Etat

# 0.86.0 (2019-09-16)
* **Assistant inscriptions :** autoriser les autres contrats si un amapien a déjà un contrat principal (et que celui-ci est complet depuis)

# 0.85.110 (2019-09-12)
* Espaces documents sous dossiers (dans options)
* Lien vers config test mail mode si actif
* **Profil Utilisateur :** non renouvellement, edit only
* Affichage date information quantité à livrer

# 0.85.95 (2019-09-12)
* Import CSV: Contrats
* Assistant Inscription: nouvelle inscription, reply to amapien
* Inscription aux distributions sans login avec une clé (comme assistant inscription en ligne)

# 0.85.85 (2019-09-06)
* **Etat Amapress :** ajout de lien pour checker la redirection et la prise en compte du htaccess
* **Assistant inscription :** check admin_mode uniquement dans backoffice
* Amélioration génération démo (référents lieux, et suppression message)
* Champ et placeholder %%mention_speciale%%

# 0.85.25 (2019-09-05)
* **Refactor geoloc :** log http errors et affichage

# 0.85.70 (2019-09-05)
* Refactoring option assistant inscription en ligne
* **Intermittence :** ajout info contenu paniers dans les mails
* **Assistant Inscription :** * sur champ Téléphone si mob_phone_required=true
* **Related Users :** lien vers Editer l'utilisateur sur son nom/prénom et avatar

# 0.85.60 (2019-09-04)
* Remplacement BackupWordpress par UpdraftPlus (sauvegardes externes)
* Optimisation
* **Assistant inscription :** param allow_coadherents_access pour interdire l'accès aux co-adhérents
* **Refactor geoloc :** log http errors et affichage

# 0.85.45 (2019-09-03)
* **Assistant inscription :** 
    - paramètre show_coadherents_address (false par déf) pour afficher saisie adresse pour co-adhérents
    - paramètre notify_email pour mettre en copie une ou plusieurs adresses des notifications (Changement co-adhérents, Non renouvellement, Adhésion, Inscription)
    - paramètre allow_coadherents_inscription (true par déf) pour autoriser les co-adhérents à adhérer à l'AMAP

# 0.85.35 (2019-09-02)
* **Assistant inscription :** 
    - notification assoc/déassoc des co-adhérents
    - paramètre track_no_renews pour proposer une zone "Je ne souhaite pas renouveler" et un motif et notifier par mail (paramètre track_no_renews_email)

# 0.85.25 (2019-08-31)
* **Assistant inscription :** ajout d'info sur les co-adhérents, si la personne est adhérent principale ou co-adhérent (param show_adherents_infos) et possibilité bloquage de l'inscription aux contrats pour les co-adhérents (param allow_coadherents_inscription)

# 0.85.20 (2019-08-29)
* **Free Pages Perso :** 
    - envoi en plain/text par défaut sauf si SEND_EMAILS_AS_PLAIN_TEXT est définie à false
    - pas de mail queue et pas de ré-essai d'envoi de mail en erreur

# 0.85.15 (2019-08-29)
* **Emails groupés :** check activation extension IMAP
* Address lookup, better check curl and empty $address

# 0.85.10 (2019-08-28)
* **Generate démo :** suppression des message inscription et paniers intermittents

# 0.85.5 (2019-08-28)
* **Mail queue :** mail en erreur, lien pour déclencher un renvoi
* **Suppression mail :** confirmation

# 0.85.0 (2019-08-27)
* **Assistant Inscription :** 
    - option contact_referents pour afficher lien de contact sur étape 4/8
    - css additionel pour les cas "email non autorisé", "inscription déjà existante"
* Amélioration correction automatique adresse pour résolution

# 0.84.135 (2019-08-27)
* **Assistant Inscription :** correction tel co adhérents requis si email non vide

# 0.84.130 (2019-08-26)
* **Assistant Inscription :** mail aux référents en options
* **Inscriptions :** 
    - co adhérent placeholder de inscription d'abord puis amapien
    - colonne Message
    - cacher la dropdown quantité quand case panier pas cochée

# 0.84.125 (2019-08-24)
* **Assistant Inscription :** affichage message de l'amapien

# 0.84.120 (2019-08-24)
* **Assistant Inscription :** mail au référents plus complet + message de l'amapien

# 0.84.115 (2019-08-22)
* **Mail Queue :** 
    - Send mail without 'to' to admin with subject indicating error
    - Skip Invalid CC
* **Assistant inscription :** cacher la sélection de quantités quand un panier n'est pas coché

# 0.84.110 (2019-08-21)
* **Assistant inscription :** 
    - possibilité de supprimer les coadhérents et paramètre allow_remove_coadhs
    - paramètre max_coadherents + doc shortcode
    - co adhérents téléphones
* Bulletin adhésion en PDF (ou DOCX dans row action)

# 0.84.95 (2019-08-20)
* **Assistant inscription :** implémentation pas de gestion des réglements

# 0.84.90 (2019-08-20)
* **Limite des scripts Free à 30s :** wp-admin bloqué Revert "Free Page Perso : process les mails en erreurs quand un utilisateur accède au tableau de bord". This reverts commit 30c5c4b0
* **Assistant inscription :** implémentation max adhérents

# 0.84.85 (2019-08-13)
* **Assistant inscription :** implémentation max adhérents

# 0.84.80 (2019-08-09)
* **Assistant inscription :** message 4/8 replace placeholders

# 0.84.75 (2019-08-08)
* **Assistant inscription :** 
    - mob_phone_required
    - message supplémentaire étape 4/8 contrats
    - css additionnel pour par ex masquer les entêtes
    - affichage contrat aux étapes 5 et 6
    - correction lien si pas de contrat principal
    - option allow_new_mail
* Refactoring menu/onglets mails/config
* Placeholder coadherent inscription/adhésion
* **Test Mail Mode :** afficher notice et info dans les mails envoyés
* **Free Page Perso :** process les mails en erreurs quand un utilisateur accède au tableau de bord
* Optimisation check dossier et màj Amapress

# 0.84.55 (2019-08-06)
* **Intermittents :** 
    - affichage panier dispo depuis
    - mail de confirmation de demande, ajout des coordonnées de l'amapien
* Option suppression de mail de la file d'attente

# 0.84.45 (2019-08-02)
* Refactoring et déplacement des rappels et mails dans des sections à part du Tableau de bord

# 0.84.40 (2019-08-02)
* Ne pas archiver les contrats avant 3 mois après leur fin
* Ne pas cloner les infos d'archivage
* **Archivage :** 
    - correction canBeArchived si isArchived
    - correction lien archivage et lien vers contrat
    - lien inscription readonly

# 0.84.25 (2019-08-01)
* Shortcode resp-distrib-contacts pour afficher les coordonnées des responsables de distribution de la semaine et prochaine

# 0.84.20 (2019-07-31)
* Ne pas utiliser la mailqueue si FREE_PAGES_PERSO

# 0.84.0 (2019-07-30)
* Intégration aide Import CSV vers wiki
* Documentation arguments shortcode inscription-en-ligne
* Check Amapress Updates

# 0.83.40 (2019-07-24)
* **Mailinggroup :** clean archive 90j option

# 0.83.35 (2019-07-24)
* Intégration lien vers cron-job.org
* Option SEND_EMAILS_AS_PLAIN_TEXT

# 0.82.95 (2019-07-23)
* Gestion des contrats dont la date de début/fin donne une durée d'environ un an (<=53 semaines)

# 0.82.90 (2019-07-22)
* Better handle Free Page Perso mail send
* Better handle Reply-To header
* Better filter duplicate headers (amapress_wp_mail) to enforce text/html utf-8

# 0.83.30 (2019-07-22)
* **Shortcode listes-diffusions :** lister les emails groupés

# 0.82.85 (2019-07-19)
* **Assistant préinscription :** fix/mobile séparés
* Filtre membres mailinglist et affichage nb amapiens au lieu de nb mails
* **Etat Amapress :** correction affichage icone catégories

# 0.82.65 (2019-07-18)
* Affichage des productions des producteurs dans la liste
* Check wp_mail already hooked
* **Etat Amapress :** affichage uniquement du premier recall si désactivé
* Infos sur modèle de contrat générique dans contrat
* Requires WP: 4.4

# 0.82.45 (2019-07-16)
* Set AltBody if text/html

# 0.84.0 (2019-07-16)
* Vue archivage des contrats avec confirmation
* Archivage d'un contrat avec export des inscriptions/chèques et effacements

# 0.83.5 (2019-07-14)
* **Emails groupés :** description de la section
* Handle shortcode in admin page title

# 0.83.0 (2019-07-14)
* Email groupés affichage nb mails en attente
* Reject mailer daemon return mail to avoid loop
* Ordre menu Emails groupés/Listes de diffusions

# 0.82.35 (2019-07-12)
* **Etat AMapress :** 
    - collapsible heightStyle content
    - collapsed by default
    - plugin block bad queries
    - collapsible + icone état par section
* Intégration modèle générique de contrat DOCX (papier et personnalisé)
* **Mail queue :** dont save for invalid emails
* Option AMAPRESS_LIMITED_HTACCESS pour les sites contenant index.php dans l'url

# 0.82.10 (2019-07-11)
* **Mailgroup :** 
    - reject quiet first
    - option confirmation aux inconnus + delete message func
    - blacklist regex
    - option reject/moderate pour les expéditeurs inconnus
    - cache queries + configuration section
    - show member and query filter
    - eml attaché au mail aux modérateurs
    - page admin Archives
* Icone Configuration
* Vue groupée Messages en attente de modération
* Préfixe Sujet par défaut
* Ne pas conserver Message-ID
* Intégration système Emails groupés avec modération
* Allow Send multipart mail from wp_mail Allow Send plain/html with wp_mail (message as array containing text/html keys) Keep Content-Type and From if set in headers Allow Add Embedded Attachement (passing $attachments as array in wp_mail) and attachment names
* **php-imap :** bug fix for gmail sent emails with attachment (flatten parts before parsing)
* Intégration php-imap

# 0.82.0 (2019-07-01)
* Intégration champs/méthodes accès Contrat Referents

# 0.81.85 (2019-06-30)
* **Liste émargement :** option afficher colonne Commentaire

# 0.81.75 (2019-06-30)
* **Widget next-events :** option ne pas afficher pour les utilisateurs non connectés
* Affichage coordonnées responsable de distribution de la semaine passée et en cours
* **Distribution :** affichage bouton Liste émargement pour le collectif ou les responsables de la semaine
* **Liste émargement :** filtrage téléphones vides
* Log du détails des échecs de géolocalisation

# 0.81.65 (2019-06-26)
* **Liste émargement :** amélioration checkbox
* Autoriser l'inscription/désinscription de responsable de distrib pour le collectif

# 0.81.20 (2019-06-25)
* Testeur d'accès à ConvertWS

# 0.81.5 (2019-06-21)
* **Assistant Inscription Admin :** correction url de départ avec conservation de l'user choisi
* **Import CSV :** modèles par contrat avec config panier en colonnes

# 0.80.45 (2019-06-13)
* Admin notice contrat à mettre à jour (panier/distrib)
* Inscription classique message un seul contrat à la fois
* Option Le contrat a des paniers à renseigner

# 0.80.10 (2019-06-12)
* Check Amapress folder name for GitHub Updater

# 0.80.5 (2019-06-12)
* Affichage des co adhérents liés aux utilisateurs dans la vue Inscriptions
* **Etat Amapress :** check période d'adhésion en cours et au début des contrats en ligne

# 0.79.110 (2019-06-07)
* **Export CSV Utilisateur :** correction export de rôle sur le site
* **Import CSV Utilisateur :** "rôle sur le site" dans modèle + resolve role + intermittent samples
* Setting GitHub Plugin URI to repo full url
* **Mailinglist :** par défaut, ne pas fetcher les mails en attente de modération
* Indication WS autres services
* **Ajout inscription (BO) :** correction start_url assistant
* **Préinscription en ligne :** coadhérent 3

# 0.79.100 (2019-06-03)
* **Préinscription en ligne :** check que le nom/prénom ne contient pas de /;,\ (indiquant une tentative de saisir tous les coadhérents en un seul utilisateur)

# 0.79.95 (2019-06-03)
* Refactoring url de base du webservice de conversion (PDF et autres)
* Custom post types Amapress non exportables
* Constante AMAPRESS_TEST_MAIL_MODE pour les sites de démo

# 0.79.85 (2019-05-22)
* **Mail panier dispo :** contact admin_email
* Placeholder admin_email/admin_email_link
* Lien page Céder son panier vers Mes paniers échangés
* Check HTTPS activé
* Confirmation sur les boutons "Inscrire/Désinscrire"

# 0.79.55 (2019-05-21)
* Nonce de désinscription liste des intermittents géré par transient de 5j
* Générer un login sans la partie domaine de l'adresse mail

# 0.79.20 (2019-05-20)
* **Generate unique username :** sans espace et minuscules
* Préparation générateur de contenu de site AMAP démo

# 0.79.15 (2019-05-19)
* Interface gestion des inscriptions dans back office Editer Distribution

# 0.78.95 (2019-05-16)
* Sanitize_user à la génération d'un nom unique à partir de prénom.nom
* Afficher les row actions en lignes
* User row action Géolocaliser
* Gestion adresse avec double CP et Ville si champs séparés
* Désactivation du xmlrpc.php par défaut
* **Inscription liée :** option Aucune
* Panier modulables, pas de rattrapage
* OpenStreetMap et Nominatim par défaut
* Amélioration affichage quantités producteurs

# 0.78.75 (2019-05-15)
* **Inscription liée :** ne pas lister l'inscription en cours

# 0.78.50 (2019-05-13)
* Export collectif séparé

# 0.78.15 (2019-05-13)
* **Quantités à la prochaine distribution :** affichage Autres dates et lien depuis Distributions
* Affichage Quantités à la prochaine distribution suivant configuration contrat (variable, fact quants)
* Edition Modèles contrats, précisions sur Dates spec.
* **Etat Amapress :** 
    - check contrat instance dates ouverture/cloture vs dates début/fin
    -  affichage email du site
    - phpinfo sans effet de bord sur l'affichage du backoffice
    - affichage version PHP et WordPress
    - check extensions ZIP et cURL
* Enregistre toujours les contrats quantites en publish
* Lieu distribution contact externe facultatif et adresse obligatoire

# 0.77.0 (2019-05-11)
* Refactoring menu Amapress
* **Quantités à la prochaine distribution :** affichage détails par option de quantités multiples

# 0.76.25 (2019-05-09)
* **Etat Amapress :** 
    - check ws conversion DOCX/PDF
    - autorise les admin à ne pas avoir de rôle descriptif
    - autorise la page de blog à être vide
    - affichage version plugin
    - check permalien toujours visible
* Filtre mois précédent (distrib, panier...)

# 0.75.75 (2019-05-07)
* **Etat Amapress :** 
    - check permalien toujours visibleaffichage admin email dans  2/ Configuration
    - check permalien toujours visiblecheck permalink structure

# 0.75.50 (2019-05-06)
* Affichage PHP Info depuis Etat Amapress
* Check min php version 5.6
* Check page de blog configurée et dans le menu
* Check Github Updater Multisite

# 0.75.0 (2019-05-02)
* Option Utiliser la première image de chaque article comme image à la Une

# 0.74.95 (2019-05-02)
* Lien vers page Inscription aux distribution (si existante) depuis une distribution
* **Amap Event, AG et Visite :** lien vers affichage single sur front pour inscription/désinscription
* Amélioration interface inscription AG
* Suppression "time" du champs date de AG
* Suppression "time" du champs date de Amap Event
* Amélioration interface front Amap Event pour inscription/désinscription + inscription par responsables
* Amélioration interface front Visite pour inscription/désinscription + inscription par responsables
* Update README.md

# 0.74.50 (2019-04-30)
* Intégration validateur de datepicker (min, max...)
* Visite/AG/Amap Event, participants readonly et lien Full Edit
* Harmonisation icones des catégories (produits, recettes...)
* Amélioration affichage reports montant entre deux inscriptions
* Refactoring et correction bug affichage icones dans calendrier
* Explications en tooltip dans l'éditeur de quantités
* Intégration plugin icalendrier

# 0.74.5 (2019-04-05)
* Inscriptions complémentaires pour gérer les chèques entre deux inscriptions quand l'amapien change de quantité en cours d'année
* Ajout téléphone et adresse optionels dans inscription intermittent
* Alerte producteur sans référent
* Lien distributions toutes et à venir sur row action contrat
* Amélioration affichage trombi des responsables distributions

# 0.73.0 (2019-03-29)
* Changement icônes pour calendrier
* Imports CSV Producteur, Productions, Produit + gestion contrat modèle par défaut pour import Quantités
* Inscription readonly sur les champs hors chèque et cloture

# 0.72.65 (2019-03-28)
* Ajout icones stats, quantités, calendrier
* Filtre contrats dans vue distribution

# 0.72.30 (2019-03-21)
* **Etat Amapress :** ajout d'une section shortcodes configurés

# 0.72.25 (2019-03-21)
* **Paniers modulables :** affichage du tableau de quantités à la prochaine distribution si pas de date spécifiée (rapporté par LoicC04)
* Page d'aide sur les shortcodes
* Documentation des shortcodes (sans les paramètres)
* Shortcode years-since
* **Contrat placeholder :** table des quantités avec dates de distributions
* Recalls Amap events
* Shortcode inscription Amap Events
* **Ical :** ajout de X-WR-TIMEZONE
* **Inscriptions :** placeholders dates_rattrapages_list et dates_distribution_par_mois_list
* **COntrat class :** getFormattedDatesDistribMois quantite_id
* **Contrat et inscription :** ajout de placeholder pour les dates par quantités avec/sans rattrapages

# 0.70.25 (2019-03-05)
* Déplacement check des membres du collectif
* Ajout accept aux inputs file

# 0.70.10 (2019-02-26)
* Intégration web service convertws DOCX > PDF pour imprimer les contrats en PDF
* **Etat Amapress :** -> renommage Cookie Consent -> ajout Feed Them Social et Count Per Day
* Option pour modifier le label des row actions dans éditeur de post

# 0.69.50 (2019-02-21)
* Placeholder dans les termes du contrats
* Ajout lien d'édition dans la présentation producteur

# 0.69.10 (2019-02-18)
* Ne plus autoriser les templates ODT
* Gestion template word pour paniers modulables
* Formattage du prix
* Ajout d'une colonne Prix dans le cas d'un panier modulaire
* **Etat Amapress :** check localisation lieux, producteur et amapiens

# 0.68.15 (2019-02-07)
* Préinscription adhesion_shift_weeks à 0 (même période adhésion que inscriptions)
* **Préinscriptions :** 
    - option only_contrats pour autoriser des inscriptions à un ou plusieurs contrats précis
    - option check_principal pour autoriser des inscriptions sans contrat principal
* Implémentation calendrier fullcalendar
* Mails intermittents, lien desinscription
* Affichage notice si accès ou adresses non localisées
* Contenu des pages par défaut
* More check date début/fin/ouverture/cloture
* Première gestion DONOTCACHE
* Log geocode failed
* **Etat Amapress :** suggestion plugin Really Simple SSL
* Filtrage utilisateurs sans téléphone, sans adresse mais avec contrat

# 0.67.50 (2019-01-23)
* Ajout shortcode liste-inscription-distrib, liste des inscrits aux distribs en readonly
* Option WP_CORRECT_OB_END_FLUSH_ALL pour corriger le bug Wordpress Notice: ob_end_flush(): failed to send buffer of zlib output compression (1)
* Amélioration normalisation des numéros de téléphone
* **Liste émargement :** éditer amapien target blank
* Gestion contrat synthèse ajout filtre "avec contrat"
* **Reférent :** Ajouter une inscription, afficher le nombre de contrats total
* **Etat Amapress :** 
    - ajout shortcode inscription-visite
    - ajout shortcode docspace
* **Panier intermittent :** adherent/repreneur, ne pas afficher role
* Copyleft Widget
* Mise à jour bouton génération clé google
* Amélioration afficher contacts amapiens
* Https ready
* Espace documents (public, amapiens, admin)

# 0.65.50 (2019-01-11)
* **Etat Amapress :** 
    - check installation GitHub Updater et Personnal Access Token
    - affichage de l'état de la création de compte sur le site
* Caché le bouton Enregistrer si Gutenberg
* Essayer de chercher un uilisateur par son nom de famille
* **Import CSV :** si login exite déjà alors afficher un lien vers l'utilisateur et un lien de recherche

# 0.65.15 (2019-01-08)
* Shortcode echo bug fix pour gutenberg
* Rappel "une visite a lieu prochainement"

# 0.65.0 (2019-01-04)
* **Etat Amapress :** check des utilisateurs membre du collectif sans contrat Mailing Listes : nettoyage des possibilités de synchro et ajout de filtres "uniquement avec contrat" + utilisateurs ajoutés à toutes les listes

# 0.64.85 (2018-12-21)
* Ajout lien vers configuration du renouvelement + roles spécifiques de l'amap (distributions, visites...) déplacés dans la section collectif + affichage systématique dans Etat amapress
* Format CSV plus supporté (avec message d'erreur demandant la conversion)
* **Gestion des contrats ponctuels :** une date de livraison (ou plusieurs) après la date de cloture
* Recall distribution horaire modifié + affichage horaires spécifiques
* Amélioration log des rôles (categories et filtres)
* Log des modifications du collectif
* Ajout index.php vide dans les dossier de stockage attachment, logs etc (en plus de htaccess deny from all)
* **Shortcode collectif :** option show_prod pour afficher les producteurs

# 0.64.50 (2018-12-17)
* Warning si moins de dates de paiements que le max de chèques
* Historique inscription distributions
* Optimisation
* Lien désincription intermittent
* Warning Etat amapress menu
* Optimisation
* Amélioration reporting backtrace pour notice, warnings, etc avec url et current user id
* **Menu Amapress :** lien vers Pages et lien vers Page préinscriptions en ligne

# 0.64.0 (2018-12-14)
* Intégration OpenStreetMap vs Google Maps avec choix pour la géolocalisation et pour l'affichage des cartes

# 0.63.55 (2018-12-12)
* Check PHP7 + correction Google Sitemap Generator + refactoring

# 0.63.50 (2018-12-10)
* Line endings \n

# 0.63.45 (2018-12-07)
* Optimisations

# 0.63.37 (2018-12-07)
* **Word templates :** définir un dossier temp dans les upload pour éviter les erreurs en PHP safe mode

# 0.63.35 (2018-11-28)
* **Etat amapress :** 
    - check des rôles spécifiques distrib, intermittents, visites, event
    - ajout shortcode [listes-diffusions]

# 0.63.10 (2018-11-23)
* **Etat Amapress :** warning si membre collectif sans role descriptif Editer collectif : afficher la liste des membres du collectif sans role descriptif
* Recall inscriptions à valider
* **Assistant inscription :** amélioration interface agreement
* Jquery Validate en fr

# 0.62.50 (2018-11-21)
* Ne pas proposer de générer les distrib/paniers pour les contrats dans la corbeille
* **Etat Amapress :** 
    - correction lien Google XML Sitemap
    - ajout d'info sur la conf assistant inscription en ligne (reglement et autres réglages)
    - check periode adhésion pour inscription en ligne sur la première date de début de contrat
    - indication Mention Amapress
* Ajout plugin Google XML Sitemap (pour le référencement simple)
* Shortcode liste des listes de diffusion
* **back link vers la liste des "post" / referrer :** ne pas faire de back link vers 'post.php' lui même
* Mode Edition complète (des champs readonly pour correction par ex)
* Afficher une lien vers le Refresh Cache de Github Updater pour faciliter la mise à jour d'Amapress

# 0.62.25 (2018-11-19)
* Ajout colonne Nom de famille dans vue utilisateur
* Reply To all co adhérents intermittents
* Reply To tresorier assistant inscription adhésion
* Reply To mails système intermittents
* Ajout info de contact dans les mails échangent paniers intermittents
* Affichage co adhérent dans tableaux des paniers intermittents
* Affichage des producteur/présentation web/modèle contrat invalides
* Affichage des producteur/présentation web/modèle contrat invalides
* Plugins GPRD (complet) et Cookie Consent (light)
* Lien pour actualiser le cache Github Updater et voir les extensions installées
* Option de personnalisation de la couleurs des légendes (Produits/recettes)
* Plus de bordures rondes pour les logos des contrats
* Setter d'horaires de distributions alternés

# 0.61.45 (2018-11-15)
* **Page distribution :** filtrage des quantités avec dates spécifiques et affichage des rattrapages
* **Mail queue :** log send mail errors + amapress_mail_queue_retries for setting number of send retries + set retries to 3 for password lost mail

# 0.61.5 (2018-11-05)
* **Test config mail :** ajout option envoyer à une autre adresse

# 0.61.0 (2018-10-29)
* Set Sender to From de PHPMailer pour définir le Return Path/FROM SMTP et éviter le rejet + Lien pour tester la configuration mail
* Ajout warning contrat invalid
* **Distributions :** filtre changement de lieu, horaires et livraisons

# 0.60.5 (2018-10-24)
* **Assistant inscription :** 
    - personnalisation message remerciement adhésion
    - ajout d'une étape réglement
* **Etat Amapress :** ajout plugin Re-Welcome
* Préparation espace document

# 0.59.30 (2018-10-15)
* Recall renouvèlement des contrats

# 0.59.15 (2018-10-11)
* Plugins Error Log et Latest Post
* Stats échanges paniers
* Stats responsables de distrib
* **Panier intermittents :** correction droit delete + filtres + changer pour adhérent + valider échange + affichage des repreneurs en attente

# 0.58.55 (2018-10-10)
* Refactoring produits/recettes avec affichage isotope js
* Refactoring options envoi liste émargement vers groupes
* Inclusion librairie Isotope.js pour amélioration du trombinoscope (et autres galleries)
* Warning lieu non localisé + warning lieux non localisés dans Etat d'Amapress
* Prise en compte Icone contrat instance dans distrib

# 0.58.35 (2018-10-05)
* **Etat Amapress :** ajout d'une section Inscriptions en ligne
* **Modèle de contrat :** possibilité de mettre une icône, ie pour Semaine A/B
* Affichage pas de clé google configurée
* Affichage 'Aucune localisation disponible' si une demande de générer une carte sans marker

# 0.58.10 (2018-10-03)
* **Modèle contrat :** export inscriptions et chèques + voir stats
* Assistant inscription et Editer mes infos, params edit_names dans les shortcodes pour désactiver la modification des noms/prénoms
* **Inscription distributions :** args inscr_all_distrib du shortcode pour autoriser les amapiens à s'inscrire à n'importe quelle distribution (ie, Semaine A/B)
* Lien retour depuis l'éditeur de post
* Affichage prix unitaire et distributions/dates dans Editer inscriptions
* Réglage confirmation des row actions
* Assistant inscription shortcode arg pour sauvegarder le lien court
* Bulk action renvoie mail confirmation inscription
* Message par défaut sur la page de login (avec indication délai expiration lien de changement de mot de passe et procédure)
* Ajout de la possibilité de choisir des groupes (ie Membres Collectif, amap roles...) en copie des mails de rappels + id pour tab des mails de rappels
* **Mail confirmation inscription :** Reply-To aux référents
* Amélioration interface ajouter une inscription (back office)
* Affichage dernière modif custom post
* **Customizer :** options par défaut
* Optimisation save title formatter seulement si changement

# 0.57.50 (2018-10-01)
* Cascade renommage lieu et correction cascade présentation web, modèle contrat, inscription
* Message confirmation pour les row actions
* Amélioration/Personnalisation du message de récupération de mot de passe + envoi direct sans passer par la mail queue.
* Gestion User Switching pour rester sur la même page (si possible)
* Ajout de capability "delete_posts" pour activer la bulk action trash
* Amélioration interface saisie des chèques
* Gestion des reports de livraison dans les quantités avec listes de dates + dans l'assistant d'inscription pour avoir la réelle prochaine date de livraison

# 0.57.25 (2018-09-29)
* Cascade delete Contrat_quantite quand delete contrat instance + clean
* Cascade de nommage des paniers, inscriptions, modèle contrat et contrat
* Amélioration filtrage Paniers intermittents
* Amélioration filtrage Adhésions
* Gestion getArchived dans select-posts (comme dans select-users)

# 0.57.15 (2018-09-26)
* Ajout horaires de substitution dans distribution
* Déplacement affichage des fonctions actuelles
* Affichage "Créé par xxx à ddd" sur tous les types de post dans l'éditeur

# 0.57.5 (2018-09-24)
* Statistiques des inscriptions aux contrats (passés et présents)
* **Aide :** afficher directement les tableaux de placeholders sans toggler
* **Etat amapress :** plugin Activity Log + info pour plugin optionnels + config liste émargement / mail site / page connexion / mail bienvenue / mailing lists / mail queue + amélioration check producteurs/référents/prés web/modèles contrats + période adhésion et étiquettes paiements + Pages particulières
* Workaround stdClass::$delete_posts (suppression cap delete_xxx singulier)
* Message au sujet des coadhérents
* **Check du changement de rôle des référents :** interdire de modifier son rôle si encore associé au producteur
* Amélioration interface profil utilisateur pour rôles et rôles dans le collectif + renommage rôles "Amap"
* **Contrat :** affichage référents + producteur
* **Distrib + Contrat :** mail/sms aux amapiens
* Interdire la suppression des word de contrat et bulletins si associés
* Suppression des paiements à la suppression des inscriptions
* Envoi rappel liste émargement à vérifier aux référents

# 0.56.10 (2018-09-20)
* **Etat amapress :** correction des warnings pour les plugins recommandés + yoast + check shortcode "mes-contrats"
* Quantités rattrapage adaptative (toujours 5 de plus)
* Trésorier et coordinateurs amap ne peuvent pas être référents
* Option contrat 'gérer les chèques sur le site'
* Affichage info inscriptions dans Etape 4/8 assistant en ligne
* Formattage des montants dans la génération de contrat word
* Intégration co adhérents à assistant inscription en ligne
* Ajout de " x " dans le tableau des quantitiés (12 x 6 Oeufs, par ex)

# 0.55.22 (2018-09-17)
* Row action resend liste émargement à vérifier et aux responsables
* **Calendrier chèques :** inclure les contrats actifs 3 mois avant (pour gérer les renouvèlements)
* **Assistant inscription :** autoriser inscription à la date de clotûre

# 0.55.20 (2018-09-17)
* Gestion Nom complémentaire dans génération de panier et placeholder contrats
* **Ajouter une inscription :** proposer date de début jusqu'à fin de semaine
* **Assistant inscription en ligne :** adhesion + send to referents + send to tresoriers actifs par défaut

# 0.55.5 (2018-09-14)
* Shortcode mes-contrats
* Filtrage calendrier pour distrib sans paniers (si un panier a des dates de distrib particulières)
* Calendrier adaptiveHeight
* Menu amapress admin lien vers sauvegarde
* Affichage standardisé en tableau dépliant des placeholders pour les mails
* **Mail log :** correction ordre par défaut + formattage html des texte brut
* Afficher * si date fin influe sur le montant
* **Assistant inscription :** ne pas afficher quantité sans date + indication spam
* **Inscription :** gestion de date de fin qui recalcule/stop le montant
* Gestion de dates spécifiques par quantités + generate contrat (modèle) si now < date fin

# 0.53.1 (2018-09-12)
* **Inscriptions :** row action (re)send confirmation mail
* Affichage des boutons téléchargement et titre des contrats/bulletin Word attachés
* Gestion shortcode panier intermittent plusieurs fois sur une page (conflit @id)
* **Contrat/inscriptions placeholders :** description rattrapages + nom lieux courts
* **Assistant inscription :** affichage des distrib de rattrapage et distinction dates/distrib

# 0.52.25 (2018-09-10)
* **Assistant inscription :** réglage before_close_hours sur shortcode pour autoriser les inscriptions jusqu'à X heures avant minuit du jour de distrib (ie, 12 autorise les inscriptions jusqu'à la veille midi)

# 0.52.20 (2018-09-10)
* **Assistant inscription :** inscription jusqu'à deux jours avant la prochaine distribution + mode vos contrats
* **Menu amapress :** ajout de lien vers logs mails, contact public, welcome mail
* **Assistant inscription :** 
    - send_tresoriers, send_referents
    - utilisation du content du shortcode pour afficher info de contact pour visiteur anonymes + warning sécurité
* Intégration adhésion à Vos contrats de l'assistant inscription en ligne
* Listes paniers intermittents adherent/repreneur avec historique
* Clean transients sur delete_post
* Expiration count inscriptions à confirmer toutes les heures
* Unslash mail subject
* Distinction inscription arrêtée et clotûrée

# 0.52.2 (2018-09-07)
* Séparation nb dates et nb distrib avec facteurs

# 0.52.0 (2018-09-07)
* Intégration bulletin d'adhésion

# 0.51.60 (2018-09-03)
* Filter active pour adhesion paiement et period
* Marquage champs obligatoires éditeur quantités
* **SMTP Queue :** si erreur alors retenter avec un délai croisant
* **Filtres :** ajout de inscription arrêtée + affichage des contrat actifs uniquement
* **Génération word papier :** retour à la ligne dans option chèques
* **Génération word inscription :** ajout placeholders pour producteur
* **Assistant inscription en ligne :** 
    - gestion filtrage multi contrat par présentation web (semaine A et/ou B par ex)
    - gestion multi contrat principal (semaine A et/ou B par ex)
* Ajout filtre inscription ended
* Correcton filtrage user par lieu et contrat (id, actif...) pour exclure les inscriptions arrêtées

# 0.51.35 (2018-08-29)
* **Inscription :** ajout placeholder heure début/fin de distribution
* Post Status Archivé (producteur, produit, présentation, recette, lieu)
* Ajout instructions et QRCode assistant inscription en ligne

# 0.51.25 (2018-08-27)
* **Inscriptions en ligne :** 
    - admin, information avec lien vers config mail et bit.ly
    - ajout du nom des référents, d'une étape "j'ai terminé" + améliorations textuels

# 0.51.15 (2018-08-22)
* Protection generate panier et distrib avec contrat en brouillon
* Génération contrat personnalisé à la fin de l'assistant d'inscription en ligne
* Génération contrat vierge à une date donnée
* Filtrage et correction des infos des placeholders Contrat personnalisé et Contrat vierge
* **Menu Amapress :** -> ajout nombre d'inscriptions en attente de validation + lien vers inscription en attente -> row action : confirmer inscription 
* **Liste émargement :** affichage en italique des inscriptions 'en attente de confirmation'
* **Menu Amapress :** ajout lien vers Contrats/Edition
* **Etat amapress :** suggestion WP Maintenance

# 0.51.0 (2018-08-09)
* Refactoring interface saisie contrat V4
* Ajouter dans la query ?placeholders pour afficher la liste en dessous des champs recalls et contrat word
* **Etat amapress :** liste des recalls
* Intégration textes Types de contrat
* Intégration impression contrat à assistant inscription
* Génération contrat papier
* Doc placeholders et recalls

# 0.49.1 (2018-07-31)
* Doc placeholders contrat en word

# 0.49.0 (2018-07-31)
* Génération de contrat word
* Refactoring getProperty
* Log mail + clean log

# 0.48.30 (2018-07-27)
* **Edition contrat modèle :** amélioration interface
* **Edition Distribution :** amélioration liste paniers

# 0.48.20 (2018-07-26)
* **Edit inscription :** affichage des coadhérents
* Dans edit panier afficher distributions
* Refactoring page profil utilisateur
* Recall à tous les amapiens ajout horaires
* **Recall de distribution :** "infos à vérfifier" + "à tous les amapiens"

# 0.48.0 (2018-07-25)
* Refactoring case à cocher "Intermittent" en button inscr/desinscr
* **Distributions :** mailto smsto responsble row action
* **Recall Liste des chèques :** export des inscriptions
* **Edition contrat :** tableaux des modalités de paiement
* **Editeur Contrat quantités :** si code vide alors code = title
* **Menu Amapress :** ajout section Admin/Etat amapress + accès Inscriptions + suppression inscription du menu Créer Etat Amapress : ajout info sur comment configurer la sauvegarde
* **Interface paiement :** texte indiquant les clics droit de recopie des numéro/banque/adhérent
* Amélioration Editeur de collectif/référents

# 0.47.30 (2018-07-24)
* Optimisation queries

# 0.47.18 (2018-07-24)
* **Assistant inscription :** correction bug email existant + calcul chèque

# 0.47.15 (2018-07-23)
* Refactoring type de quantités en select
* Refactoring quantité editor en tableau

# 0.47.5 (2018-07-22)
* **Flage de prise en compte des contrats futurs (dans les active contrat instance et active adhésion) Liste émargement, affichage des inscriptions en cours uniquement Assistant inscription en ligne :** finalisation
* Réduction liste émargement

# 0.46.8 (2018-07-22)
* **Assistant Inscription :** contrôle des entrées des étapes + amélioration étape "date et lieux" + correction bug envoi d'un mail dans "init"

# 0.46.3 (2018-07-19)
* **Assistant inscriptions :** prise en charge des contrats paniers variables

# 0.46.0 (2018-07-12)
* **Back office ajouter une inscription :** intégration de l'assistant inscription en ligne (avec tous les contrats possibles)
* Réduction taille liste émargement
* Assistant inscription contrat en ligne V1.2 (gestion for_logged et check pas plus d'un contrat principal)
* Assistant inscription contrat en ligne V1.1 (gestion des contrats multiples et variables)

# 0.45.18 (2018-07-08)
* **Inscriptions en ligne :** v0.1

# 0.45.15 (2018-07-05)
* **Inscriptions :** séparation des colonnes nom, nom de famille, mail et adresse pour faciliter réimport au renouvellement et export CSV de sauvegarde
* **Contrat :** nom complémentaire readonly si déjà inscriptions
* Séparation renouvellement contrat en prolongation (nouvelle date de début = semaine après la fin du précédent) et à la même période (renouvelle l'année d'après)
* **Amélioration inscription distribution :** lister les utilisateurs sans contrats et possibilité simplifiée d'ajouter une personne hors amap
* Amélioration liste émargement et envoi contrats de la semaine/tous contrats en rappel
* Menu Amapress, liste émargement en target blank

# 0.45.7 (2018-06-29)
* Amélioration interface intermittents

# 0.45.6 (2018-06-29)
* Mise à jour format des titres Inscription et paiement inscription pour être triable par nom de famille

# 0.45.5 (2018-06-27)
* **Etat amapress :** page à compléter : intégration des producteurs, lieux, contrat pour la recherche de [[à compléter]]
* Séparation style adminbar de bo/fo css

# 0.45.1 (2018-06-25)
* Gestion archivage/suppression des utilisateurs sans contrat, non producteur et non référent

# 0.44.38 (2018-06-20)
* Prise en charge des webmail qui incluent le > du mail de password lost dans le lien...

# 0.44.36 (2018-06-15)
* Unslash et texturize à l'envoi de mail

# 0.44.35 (2018-06-14)
* Amélioration interface liste des chèques + export

# 0.44.25 (2018-06-12)
* Amélioration interface Calendrier des chèques + liste des chèques
* Amélioration affichage new user
* Menu Amapress, ajout de Inscription distributions et icone + vue mobile

# 0.44.15 (2018-06-06)
* Déplacement liste coadhérent
* Affichage email dans drop down select users

# 0.44.5 (2018-06-05)
* Interface saisie coadhérent
* Pas d'avatar dans getProperty/getDisplay d'Amapien (utilisé dans les mails)
* Gestion des distributions sans panier et rappels de "pas de distribution" et distribution un autre jour que l'habituel
* **Etat amapress :** split entre contenu à compléter et shortcodes + ajout check Rôle du collectif + check COntrats à renouveller
* Panier Date subst filtrage des distribution 2 mois autours de la date du panier
* Amélioration interface Producteur et Contrat (lien vers les contrats modèles et les inscriptions)

# 0.43.15 (2018-05-31)
* Amélioration intégration contact form et infos de contact
* **Etat amapress :** amélioration message check [[à compléter]] et shortcodes
* **Contrat :** amélioration du message d'édition "après saisie des inscriptions"

# 0.43.10 (2018-05-30)
* **Etat amapress :** check shortcodes présents, check page vide, check options et pages à compléter suivant syntaxe * en multisite
* Filtrage amapress_date=past et correction amapress_date pour inscription
* **Distribution :** lien pour mailto et smsto responsables
* **Contrat :** lien Editer tout (quand déjà des inscription) + message si pas de quantités ajoutées
* **Distribution :** lien vers liste émargement en premier + row action
* Prise en compte du sous nom dans le renew contrat/adhésion
* Implémentation rattrapage quantités

# 0.41.20 (2018-05-17)
* Implémentation Contrat Nom complémentaire dupliquer contrat correction bug cochage liste dates Liste paniers dans contrat correction ordre de tri des paniers dans les related posts

# 0.41.15 (2018-05-17)
* **User :** affichage contrats et coadhérents dans page modification + query filter amapress_coadherents=user_id

# 0.41.11 (2018-05-13)
* Roles dans l'AMAP plus importable

# 0.41.8 (2018-05-13)
* Workaround pour problème Could not close zip file php://output suite

# 0.41.7 (2018-05-13)
* Workaround pour problème Could not close zip file php://output

# 0.41.6 (2018-05-12)
* Ne pas filter les contrat/producteur/... pour les admins et responsables amap

# 0.41.0 (2018-04-27)
* Gestion coadhérent 4

# 0.40.30 (2018-04-24)
* **Affichage de Coadherent sans mail dans liste émargement + inscription distrib 
* Amélioration interface éditer producteur 

# 0.40.6 (2018-04-15)
* Mode de test qui envoie tous les mails à une/plusieurs cibles prédéfinies.

# 0.40.5 (2018-04-12)
* Gestion de la transition Inscription distribution avec/sans rôles (amapiens en "surplu" dans la dernière case)

# 0.40.1 (2018-04-06)
* Séparation collectif et collectif sans producteurs

# 0.40.0 (2018-04-04)
* Option pour désactiver l'inscription d'intermittents par les amapiens

# 0.39.5 (2018-03-30)
* Panier filtre nextweek
* Implémentation Role de distribution
* Harmonisation date limite cession panier
* **Import CSV :** check et erreur si date début inscription en dehors de la plage de date du contrat

# 0.38.50 (2018-03-24)
* Checks échange paniers (échange > now + pas double échange)

# 0.38.45 (2018-03-24)
* Ajout du role producteur dans les role avec accès au backoffice
* **Mailing list :** ajouter de Producteurs dans la liste des membres possibles

# 0.38.40 (2018-03-24)
* **Import CSV :** gestion import d'un contrat avec quantités en colonnes
* Datatable init_as_html, gestion de responsivePriority

# 0.38.35 (2018-03-12)
* Ajout d'une bulk action Confirmer inscription
* Amélioration menu Amapress (Semaine dernière, cette semaine en italique, accès aux autres paniers/distrib)

# 0.38.20 (2018-03-06)
* Rappels quantités, chèques, responsable, liste émargement, inscription, panier intermittents + fonction test mail + simplifcation syntaxe %%post:...%%

# 0.38.15 (2018-03-05)
* Rappels quantités, chèques, responsable, liste émargement, inscription, panier intermittents + fonction test mail + simplifcation syntaxe %%post:...%%

# 0.38.0 (2018-03-02)
* Rappels quantités, chèques, responsable, liste émargement, inscription, panier intermittents + fonction test mail
* Liste émargement en XLSX
* Créér scheduler uniquement si > now
* Générique amapress export Excel from post query
* **Table des chèques/paiements contrats :** export XLSX
* Html to XLSX générique
* **Table des chèques/paiements contrats :** export PDF
* **Quantité de la semaine :** affichage texte et/ou tableau
* **Panier :** indiquer modifications dans titre (annulation, report)

# 0.36.10 (2018-02-27)
* Amélioration affichage de liste émargement et en PDF
* Clean Html2Pdf et CreatePDF as attachment

# 0.36.0 (2018-02-25)
* Refactoring et export PDF de la liste d'émargement + màj dépendences Composer
* **Map :** ne pas afficher vélo et traffic
* **Fonctions d'envoi de mails :** gestion attachments, cc, bcc
* **Classe Panier intermittent :** ajout description des quantités/contrats
* **Classe Distribution :** ajout des paniers intermittents et de leur description

# 0.35.5 (2018-02-14)
* Amélioration interface contrats modèles + generate distributions et pas si brouillon
* Amélioration liste des contrats modèles

# 0.35.0 (2018-02-13)
* **Mailing list :** ajout de Membres du collectif
* Refacoting amapress_prepare_in_sql (filtrage empty et unique) pour un IN sql
* **Editer collectif :** ajouter un rôle en target _blank
* **Contrat :** affichage du nombre de dates de distrib + weeks
* Amélioration génération des modèles d'import Excel
* Menu Editer le collectif
* Gestion coadhérents dans queries user et adhesion

# 0.31.0 (2018-02-08)
* Datatable builder, init_as_html (true/false) pour initialiser la table en html ou en JS
* **Shortcode "collectif" :** ne pas afficher les rôles "Wordpress/Amapress"
* Page de gestion BO du collectif dans Paramétrage
* Amélioration interface inscription distrib (éditer nb resp, largeur colonnes, inscr multiple par resp) 
* **Proposer son panier :** affichage des paniers de l'utilisateur seulement

# 0.30.0 (2018-02-01)
* **Post-its :** liste émargement, lien vers distrib Ajout menu Amapress, liste émargement, paniers, distrib, ajouter inscription
* Amélioration filtrage date
* **Système Intermittent :** groupage des paniers par distribution, amélioration interface, filtrage
* **Mailing list configuration :** ajout de Amapiens avec contrat
* Row actions renew check si possible
* Vue A renouveller et correction bug nommage A confirmer
* Vue simplifiée pour ajouter un utilisateur
* **Profile utilisateur :** tf-form-table à table layout auto sinon colonne entête trop large
* **Ajout inscription :** lieu par défaut si amapress_adhesion_adherent est passé + now pour date début
* User, row action, Ajouter une inscription
* Gestion row action dans user-edit.php, users.php, profile.php et option target

# 0.25.0 (2018-01-23)
* Slugs configurables
* Row actions vers liens simples
* **Distribution :** boutons mailto responsables, quantité à livrer, éditer + panier reporter/éditer contenu Panier : amélioration interface Query filter : amapress_date=lastweek
* Cacher les group de metabox vides
* Amélioration interface présentation web
* Simplification présentation web/producteur
* Suppression des champs personnalisés pour recettes et produits. Uniquement éditeur principal.
* Suppression de l'inclusion de bootstrap
* Suppression news (utilisation d'article plus tard)
* Panier intermittent readonly dans back office
* Ajout email comme identifiant dans mail welcome par défaut
* Ajout filtre "Membres du collectif" en remplacement de Avec rôle
* Ajout des entités dans le menu +Créer
* Amélioration interface
* Gestion des row actions après le titre de l'éditeur de post
* Tri des contrats
* Génération automatique de l'identifiant utilisateur à la création (prénom.nom) + masquage champs inutiles
* Administrator n'a pas le droit de delete/publish sur distribution et panier
* **Adhésion contrat :** ordre par défaut et ne pas proposer les contrats auquel un amapien est déjà abonné
* Ajout de custom post, prise en compte de valeurs par défaut passées en $_GET
* Amélioration affichage information Amapress dans vue Utilisateur
* Ordre de tri par défaut des custom post type et des options select
* Option dans metabox pour affiche la colonne Date dans la vue liste de posts Amélioration de la vue BO Demande d'adhésions
* Filtrage par amapress_status
* Panier date de substitution uniquement pour Status Reporté
* Amélioration affichage adminbar
* Bug recherche utilisateur depuis adminbar depuis front end
* Bug enregistrement et envoi mail pour annulation d'une demande de reprise panier en attente de validation
* Bug DISTINCT manquant quand WP_User_Query avec meta_query
* Ajout d'une zone de recherche dans l'admin bar
* **Intermittents :** possibilité d'annuler une demande de reprise de panier pas encore validée
* **Sync Sud Ouest :** fetching par remote url forcée en http
* Ne pas rediriger vers home page si la requête est vers /wp-admin/admin-post.php
* **Etat Amapress :** bug check présentation web
* **Mailinglist :** sync_all, force la synchro de tous les paramètres
* Ajout des filtres de vue Evènements

# 0.19.0 (2017-12-30)
* Optimisations

# 0.18.0 (2017-12-22)
* Optimisations

# 0.17.2 (2017-12-08)
* Suppression du SQL_CALC_FOUND_ROWS
* Optimisation de requêtes SQL
* Liste émargement - bouton pour afficher/cacher tous les contrats
* Liste émargement - amélioration affichage pleine largeur
* Liste émargement - options show_phone, show_email, show_address et all (tous les contrats même si pas à cette distrib)
* Amélioration affichage liste émargement

# 0.17.0 (2017-11-24)
* Affichage quantité multiple (ie 1L, 2L...) dans page distribution
* Implémentation synchro Sud-Ouest.org
* Autoriser un amapien responsable cette semaine ou la semaine prochaine à inscrire les responsables de distribution

# 0.16.0 (2017-11-23)
* Contenu de panier en texte + produits associés
* Validation du formulaire utilisateur Bouton Enregister dans l'admin bar pour Editer utilisateur et Votre profile
* **Synchro Listes Ouvaton :** suppression de {xxxx} ajouté (par Wordpress ???) dans les requêtes SQL de recherche de rôle (like dans wp_capabilities)
* **Page collectif :** afficher email et numéro de mobile
* **Sample CSV :** quantité, inclure code et quantité (si <> 0) dans les valeurs d'exemple
* Implémentation déplacement de distribution pour un contrat (panier) donné
* Page collectif, ne pas afficher lieu si un seul + autorise wrap des données
* Proposer son panier - Information border box sizing
* Proposer son panier - Colonne date 30%
* **Amélioration interface nouvelle inscription/edition inscription :** contrôle date début (et fin)
* **Amélioration interface nouveau contrat/edition contrat :** contrôle date début/fin et ouverture/fermeture

# 0.15.3 (2017-11-16)
* Amélioration interface nouveau contrat/edition contrat

# 0.15.2 (2017-11-16)
* Option pour le password_reset_expiration
* Affichage des paniers dans distributions
* Affichage contrat en une seule colonne
* **Import CSV :** si une seule quantité pour un contrat, autoriser "x", 'oui', 'yes'...pour importer une inscription avec cette quantité
* Ajout intermittent à demande d'adhésion

# 0.15.1 (2017-11-13)
* **CSV Sample :** gestion des contrats en mode multi et quantités filtrées
* Inscription unique par contrat/amapien (validation formulaire ajout inscription)
* Sélection de quantité uniquement si quantité cochée
* Motif de fin de contrat uniquement après création de l'inscription

# 0.15.0 (2017-11-10)
* **Quantité et calendrier paiement :** options affichage prochaine distrib et contact producteur
* Lien instructions lieu vers page distribution
* Amélioration liste quantités
* Amélioration calendrier paiements (option Imprimer) + répartition par lieu
* Amélioration présentation liste émargement (largeur colonnes)
* getPropertiesDescriptions
* **getProperty :** Séparation info adherent et repreneur
* Ajout propriétés pour distribution (lien, ical, infos)
* Ajout propriété générique pour Events (liens, ical, info lieu)
* Ajout filtrage ical (par events_id et event_types)
* Refactoring inscription/desinscription
* Référents 2 et 3
* Implémentation quantité variables (par ex, 1L jus, 3L jus...)

# 0.13.0 (2017-10-21)
* Shortcode inscription-visite
* **Inscriptions :** table de paiement, max width 100%
* **Présentation Web :** n'afficher le thumbnail (supposé un logo) que si la taille du thumb demandé est carré
* Ne pas afficher les entrée de menu Archive de posts logged_or_public
* **Bulk action :** localiser utilisateurs
* **Intermittents :** lien de désinscription avec saisie email (et protection par nonce) + shortcode intermittent-desinscription-href (logged only)
* **Etat amapress :** check clé google
* **Paramétrage :** Google Key dans un onglet à part et ajout de bouton pour Générer une clé
* **Import Excel :** prise en compte du role uniquement si ajout utilisateur
* **Shortcodes :** prochaine distrib et prochaine liste émargement (link, href, redirect, date)
* Liste émargement: paramétrage du nombre de distribution affichées pour l'inscription des prochains responsables
* **Visite :** bouton Inscription en haut
* **Inscription Contrat :** message validation sélection un seul contrat
* **Liste émargement :** Option taille de police à l'impression Ajout bouton Editer infos du lieu Ajout bouton Editer paramètres de la liste Check droit Editer la distribution Ajout contact externe
* **Ajout inscription :** check de sélection des quantités d'un seul contrat à la fois
* **Post it :** afficher liste émargement jusqu'à la fin de la semaine

# 0.12.20 (2017-10-16)
* **Liste émargement :** info responsable distrib, cacher adresse, roles
* **Liste émargement :** édition commentaire
* Ajout capacité edit_users sur Coordinateur Amap
* Protection "is_protected_meta" des métadonnées amapress
* **Calendrier :** forcage float left des events + break word all pour éviter les débordements

# 0.12.15 (2017-10-14)
* Possibilité suppression lieu de distribution sans contrat associés
* Calendrier des chèques, ajout banque, lieu, nom, quantité Tri par nom
* **Liste émargement :** bug tri suite ajout lien édition utilisateur sur last_name
* **Tests :** mise en place des données de test de l'amap
* En mode Test, générer les distrib et paniers en add only
* **Liste émargement :** renommage bouton Editer
* Suppression Edit Post sur liste émargement
* **Calendrier :** possibilité d'ajouter une bordure autours des évènements des dates
* **Liste émargement :** lien éditer utilisateur si droit
* **Liste émargement :** possibilité d'afficher/masquer téléphone, adresse, emails
* **Liste émargement :** affichage des responsables des autres lieux

# 0.12.10 (2017-10-13)
* **Liste émargement :** correction bug liste responsable distribution
* **Liste émargement :** tri alphabétique

# 0.12.5 (2017-10-12)
* Protection amps_lo pour the_content, the_title et the_excerpt
* Affichage du code quantité dans la liste émargement
* Tests filtrage WP_Query et WP_User_Query (failing pour l'instant)
* Tests filtrage WP_Query et WP_User_Query
* **Tests unitaires des pages, posts et custom posts :** the_content, the_title...
* &generate_test dans url pour générer du code de test (dans une métabox) sur une installation Amapress existante Classe de base pour test unitaire Amapress
* phpunit, constante AMAPRESS_TEST_MODE
* Mise en place phpunit, bootstrap
* Variable globale contenant la liste des shortcodes (pour les tests)

# 0.11.20 (2017-09-25)
* Contrat modèle, readonly si inscriptions déjà faites
* Bouton Editer dans distribution
* Liste émargement, colonne C et Commentaire
* Ajout d'un filtre tf_is_option_readonly
* Amélioration des messages de synchronisation des mailinglists et ajout d'un lien Sync All
* Bouton Enregister dans l'admin bar pour Ajouter (post-new.php)
* Installation PHPUnit
* Mise à jour Composer, nettoyage composer.json, ajout YaLinqo et passage du projet en PHP 5.5
* Amélioration des filtres Présentation web (contrat) et modèle contrat (contrat instance) Vues par Présentation web dans modèle contrat
* amapress_prepare_in pour ne pas laisser une meta_query en IN avec un tableau de recherche vide
* Mailinglist: option "Tout synchroniser"
* **Filtre utilisateur :** lieu et contrat, ne prendre en compte que les post "publish"
* **Mailinglist :** lister les Présentation web actives au lieu des Modèles de contrat
* Filtre amapress_contrat peut prendre l'id d'un contrat modèle ou d'une présentation contrat
* Export Excel, unescape html et html entities
* Checkbox "Panier personnalisé" sur la page "Ajout inscription" pour les contrat panier variable
* Ajout d'un helper générique d'ajout de contenu après le titre de l'éditeur de post Affichage d'un warning si un utilisateur n'a pas de contrat principal ou que le contrat principal n'existe pas
* Row action "Ne pas renouveller" et filtrage "CanRenew" si date de fin et raison pas définies
* Transaction autour de la création des distributions et paniers
* Ajout des coadhérents dans le récap des chèques, même s'il n'est pas émetteur d'un chèque
* Lien vers l'inscription et affichage de tous les émetteurs dans le Calendrier Encaissements

# 0.10.4 (2017-09-14)
* Vues Calendrier Encaissements et Quantités à la prochaine distribution
* Possibilité de créer des tabs dynamiquement dans une admin page (call_user_function) tf-form-table pour table-layout: fixed

# 0.9.4 (2017-09-11)
* **Inscription aux distributions :** -> correction du check d'autoristion inscription autre amapien -> autoriser à s'inscrire sur son lieu de distribution précédent pendant la période de renouvelèment
* Les paiements dans les inscriptions ne peuvent être renseignés que lorsque l'inscription est publiée. Sinon afficher un message