# 0.99.216 (2023-06-11)

* notification pour indiquer qu'il existe la **Recherche dans le Tableau de bord** (pour trouver un paramètre, une
  fonctionnalité ou un menu)

# 0.99.215 (2023-06-11)

* **Espace intermittents:** ajout d'un paramètres `show_waiting` sur le shortcode `[les-paniers-intermittents]` (paniers
  disponibles) pour afficher les paniers en attente de validation de l'échange.
* plus de compatibilité PHP 8.1 et 8.2 (warnings)

# 0.99.210 (2023-06-04)

* compatibilité PHP 8.1 et 8.2 (error handler et autres deprecations)

# 0.99.203 (2023-05-27)

* **Système des intermittents**: envoi de mail de refus aux demandeurs/repreneurs si l'amapien annule avant validation
  des demandes
* mails de contrats/périodes d'adhésions/utilisateurs archivables uniquement si au moins un est archivable

# 0.99.201 (2023-05-12)

* **Espace intermittents:** option pour envoyer le mail de 'Paniers disponibles' à tous les amapiens
* filtres contrats et périodes d'adhésion archivées

# 0.99.197 (2023-04-16)

* fix for some WordPress 6.2 deprecations

# 0.99.196 (2023-04-02)

* fix error handler

# 0.99.195 (2023-04-01)

* **Espace intermittents:**
  - ne pas envoyer le message Panier à réserver à l'amapien qui le propose (s'il n'est pas intermittent)
  - depuis un mail de Panier disponible, scroller vers le panier en question (ou proche)
  - option 'Afficher les co-adhérents des amapiens proposant leurs paniers' (si décochée seul l'amapien ayant proposé
    son panier est affiché sans les informations sur ces co-adhérents)
* option 'Ignorer les erreurs' pour pouvoir supprimer des logs les erreurs des autres plugins/thèmes (et alléger le log) (Tableau de bord>Réglages>Amapress, onglet Tests)

# 0.99.186 (2023-02-20)

* suppression des références au plugin Command Palette, suite à l'intégration de la recherche dans le tableau de bord "
  nativement" dans Amapress

# 0.99.185 (2023-02-07)

* Quantité proposée, tooltip "ajouter ! en début pour forcer à prendre au moins 1"
* ajout Messageries instantanées dans les moyens de contact d'un amapien
* menu Aide > Recherche Tableau de bord pour rechercher dans le menu, options et paramètres du Tableau de bord

# 0.99.175 (2023-01-05)

* **Quantités contrats:** ajout d'une option !1>25 pour forcer la sélection d'au moins 1 pour un produit
* affichage des coadhérents suivant le calendrier de partage dans les quantités aux distributions, les finances et la
  liste d'émargement

# 0.99.171 (2023-01-03)

* autoriser les co-adhérents coché par défaut dans les contrats

# 0.99.170 (2023-01-02)

* message aux co-adhérents facultatif dans les contrats
* **Assistant d'inscription:** correction affichage du bouton d'édition du calendrier de partage pour les modes de
  co-adhésion totale ou partiel
* **Export CSV:** correction export multidate (par ex, dans les dates spécifiques dans contrat quantités)

# 0.99.165 (2022-12-18)

* correction bugs calendrier de partage

# 0.99.160 (2022-12-17)

* **Gestion d'un calendrier de partage entre co-adhérents**
  - édition du calendrier,
  - rappels,
  - calendrier,
  - affichage dans les distributions

# 0.99.155 (2022-09-10)

* **Assistant d'inscription:**
  - bug affichage tableau de chèques pour paiement au mois
  - envoi mail à l'amapien en cas d'annulation de contrat
  - afficahge description des quantités pour les contrats simples
* **Contrats:** gestion des paiements par mois si plus de 11 mois + amélioration affichage
* **Règlements Adhésion:** action et action groupée pour Marquer remis
* **Mailing List:** champs liste d'email brute (un par ligne)
* **Remainder:** champs liste d'email brute (un par ligne)
* **Mailing Groups:** imap checking
* constante AMAPRESS_LOG_HELLOASSO_CALLBACK pour log des callback HelloAsso (inactif par défaut)

# 0.99.149 (2022-07-01)

* constante AMAPRESS_VALIDATE_SSL pour permettre de désactiver le check SSL si nécessaire
* dont log send mail quota exceed

# 0.99.148 (2022-05-31)

* PHP8 compatibility

# 0.99.146 (2022-04-02)

* numéro adhérent dans la liste des adhésions

# 0.99.145 (2022-03-25)

* **Etat Amapress:** mise à jour lien pour création de token GitHub
* **Contrats:** bug check status sur contrat papier si pas de contrat docx pour le contrat
* **Mail Queue:** commencer par les mails en attente avant les mails en erreur
* **Demandes d'adhésion:** action de création de l'amapien : si admin ou responsable alors approuver le compte + si
  demande d'être intermittent alors inscrire
* **Emails groupés:** envoi mail à l'admin si mails en erreur d'envoi
* ne pas logger certaines erreurs de plugin externes

# 0.99.141 (2022-02-21)

* **Contrats:** autoriser les contrats papiers, valeur par défaut

# 0.99.140 (2022-02-18)

* **Shortcodes:** générer le shortcode [amapress-backoffice-view] de la vue des comptes avec users="true"
* **Règlements adhésions:** affichage colonne Numéro d'adhérent

# 0.99.135 (2022-02-14)

* **Shortcodes:** inscription responsables de distribution, bouton S'inscrire gardien sur toutes les prochaines
  distributions uniquement si le système de gardien de paniers est actif

# 0.99.131 (2022-02-02)

* **Contrat** vérification de la présence des contrats DOCX

# 0.99.130 (2022-01-26)

* **Inscription Intermittents:** ajout des responsables des intermittents en destinataire des mails d'approbation (New
  User Approve)

# 0.99.125 (2022-01-24)

* **Inscriptions distributions:** ajout d'un bouton 'S'inscrire gardien de paniers à toutes les distributions'
* formulaires d'inscriptions (distribution, amapiens externes), honeypots + check key avec comparaison
  amapress_sha_secret

# 0.99.120 (2022-01-15)

* **Import/Export CSV:**
  - correction de la gestion de la colonne 'Rôle sur le site' (et Nom)
  - correction bug export des valeurs des colonnes des users (par ex dans le cas de colonnes ajoutées par d'autres
    plugins)
* **Mailinglist:**
  - lien vers configuration dans un nouvel onglet
  - OVH : synchronisation des modérateurs
* **Comptes:**
  - affichage colonne du lieu choisi dans l'adhésion en cours
  - lien envoyer un mail à l'amapien et lien envoyer un mail à l'amapien et tous ses coadhérents
* **Contrat:**
  - affichage d'un warning indiquant que les rappels d'ouverture de contrats ne seront pas envoyé si la date d'ouverture
    des inscriptions est inférieur à la date de création du contrat
  - lien pour envoyer le mail de rappel d'ouverture des inscriptions
  - option pour ne pas inclure/générer de contrat PDF
* **Dashboard:** amélioration widget d'information Amapress
* **Messagerie:** affichage des contrats en cours, pour inclusion par copier coller dans le message si besoin
* **Shortcodes:** ajout d'un shortcode [inscription-distrib-info] d'informations sur le nombre d\'inscriptions requises
  comme responsable de distribution
* bug pre_get_posts appelé via get_posts par certains plugin avant le chargement complet de Wordpress

# 0.99.100 (2021-12-31)

* **Assistant inscription:**
  - scroll tableau des paniers modulables
  - indication du scroll
  - paniers_modulables_editor_height en px par défaut si juste un chiffre

# 0.99.95 (2021-12-15)

* correction bug génération de titres d'adhésion + optimization
* ajout meilleur gestionnaire d'exception

# 0.99.90 (2021-12-05)

* **Mailinglist:**
  - correction affichage nombre de membres si 0
  - correction de la génération de la requête SQL avec exclusion de membres
  - gestion des exceptions et des tokens expirés/erreurs de configuration dans la synchronisation auto des mailing
    listes
  - normalisation en minuscule des adresses emails pour éviter les problèmes de synchro case sensitive
* **Mailinglist OVH:**
  - correction des liens de configuration vers le manager OVH
  - lien target blank pour générer les tokens
* **Email groupés:** mail de bounce, recherche de l'utilisateur concerné pour fournir un lien direct d'édition du compte

# 0.99.80 (2021-12-01)

* **Contrats:** clone de la description des configurations de panier
* **Mailinglist:**
  - affichage Mailing liste introuvable si liste non trouvée par ex par erreur de configuration/clés périmées...
  - correction des liens de configuration manuelle OVH + affichage correcte de la nécessité de configuration manuelle de
    la modération et du reply-to
  - OVH, correction bug si les clés ne sont plus/pas valides

# 0.99.70 (2021-11-13)

* autocorrect deprecation of git updater override dot org

# 0.99.65 (2021-11-13)

* **Rappels:** rappel contrats avec inscriptions ouvertes/commandes ouvertes avant chaque distribution

# 0.99.60 (2021-10-30)

* **Emails groupés:** correction bug suppression message après modération

# 0.99.55 (2021-10-26)

* **Rappels:** rappels des livraisons aux amapiens, prise en compte des paniers déplacés

# 0.99.50 (2021-10-22)

* **Emails groupés:** correction d'un bug de suppression des pièces jointes des mails modérés
* **Contrats:** affichage "inscriptions closes X jours avant la dernière distribution"

# 0.99.45 (2021-10-19)

* **Formulaire d'adhésion:** affichage contrat actifs et futur, non filtré (option filterhome pour filter et onlycurrent
  pour ne pas afficher les contrats futurs)
* **Quantité à livrer:** afficher uniquement le message au producteur
* **Formulaire d'adhésion:** option pour répondre automatiquement aux demandes d'adhésion et settage du Reply-To

# 0.99.35 (2021-10-11)

* ajout champ Numéro d'adhérent dans les comptes
* approbation du compte (New User Approve) en même temps que le marquage reçu de l'adhésion

# 0.99.25 (2021-09-30)

* **Assistant d'inscription:** gestion max adhérent/part à la fois au niveau contrat et au niveau produits
* **Archivage:** possibilité d'archiver tous les contrats archivables d'un coup

# 0.99.15 (2021-09-22)

* **Rappels:** livraison à la prochaine distribution aux amapiens, correction bug sujet si pas de livraison

# 0.99.10 (2021-09-17)

* **Etat Amapress:** recommandation WP Mail SMTP pour configuration avancée
* filtrage amapien principaux par période d'adhésion
* **Messagerie:** ajout "Les amapiens principaux avec adhésion AMAP (réglée ou non réglée)", "Les amapiens et
  coadhérents avec adhésion AMAP (réglée ou non réglée)" et "Les amapiens principaux par période d'adhésion"

# 0.99.0 (2021-09-17)

* **Assistant d'inscription:**
  - correction bug sélection de la quantité pour les paniers modulables (par défaut, à l'affichage, première valeur > 0)
  - paramètre sort_contrats pour trier l'affichage des contrats ouverts aux inscriptions par title, inscr_start,
    inscr_end ou contrat_start
* **Mail Queue:** ignorer les adresses emails invalides à l'envoi
* **PWA:** correction enregistrement des stratégies de cache
* **Quantité producteur:** amélioration affichage message au producteur/référent
* **Shortcodes:** amapress-backoffice-view, correction bug parsing de la query
* par défaut ne pas logguer les erreurs JS (remplacement de la constante AMAPRESS_DISABLE_JS_ERROR_LOG par
  AMAPRESS_ENABLE_JS_ERROR_LOG)
* amélioration affichage quantité "1 x" (par défaut pour les paniers modulables)
* gestion callback HelloAsso sans address/zipcode/city

# 0.98.145 (2021-08-31)

* **Assistant d'inscription:** choix de quantité prendre la première valeur après 0 par défaut
* **Filtre adhérents principaux:** correction bug si à la fois principal et lié en coadhérent dans le coadhérent

# 0.98.140 (2021-08-29)

* **Assistant d'inscription:**
  - renommage Poursuivre en Ajouter de nouveaux contrats
  - affichage d'un lien vers la page de présentation du contrat dans l'étape Panier
  - option agreement_new_only pour n'afficher le règlement intérieur qu'aux nouveaux amapiens
  - possibilité d'affichage des placeholders contrats dans les messages des étapes d'inscription
* **Présentation contrats:** ajout d'une classe amap-contrat-subscribable ou amap-contrat-not-subscribable sur la panel
  des contrats actifs affichés dans la page front d'affichage des contrats

# 0.98.130 (2021-08-19)

* **Assistant d'adhésion:** bug envoi mail non renouvellement à l'amapien + other fix
* **Mes paniers échangés:** groupage par status de validation de l'échange de panier (si mode non partiel)

# 0.98.120 (2021-08-18)

* **Assistant d'adhésion/d'inscription:**
  - clarification emplacement bouton Modifier les coordonnées (paramètre show_modify_coords) dans la doc Shortcodes
  - correction bug affichage des champs coadhérents/cofoyer pour un adhérent principal

# 0.98.115 (2021-08-12)

* **Assistant d'inscription:** affichage des adhésions en cours et futures
* **Assistant d'adhésion:** paramètre send_no_renews_message pour envoyer un message de non renouvellement à l'amapien
* **Rappels livraisons:**
  - option pour ne pas envoyer de mail si pas de livraison
  - possibilité de sujet différent si si pas de livraison
* affichage des boutons de récupération de mot de passe
* affichage "1 x produit" suivant la constante AMAPRESS_SHOW_ONE_UNITS

# 0.98.100 (2021-07-23)

* **Assistant d'inscription:** correction bug inscription à un panier modulable après la première date de livraison
* **Quantités à livrer:** correction affichage du message au producteur/référents dans ¤Toutes¤ (quand affichage des
  amapiens)
* affichage d'un colonne et filtre avec le nom du producteur pour les contrats et les inscriptions
* **Import CSV:** date avec année sur deux chiffres

# 0.98.95 (2021-07-03)

* **Assemblée:** correction bug titre/slug si passage de lieu de distribution à lieu externe
* **Evènement:** correction bug titre/slug si passage de lieu de distribution à lieu externe

# 0.98.90 (2021-06-13)

* **Période d'adhésion:** correction du filtrage des périodes d'adhésion par catégorie

# 0.98.85 (2021-06-07)

* correction filtrage ok/nok/all pour gestion multiple période d'adhésion avec chevauchement et catégorie d'adhésion

# 0.98.80 (2021-06-04)

* **GitHub Updater:** intégration du renommage du plugin GitHub Updater en Git Updater (https://git-updater.com/knowledge-base/updating-from-github-updater-to-git-updater/)
* **PWA:** PWA cache plugin 0.6
* **Adhésion:** gestion émetteur
* **Adhésion en ligne:** paramètre _send_adhesion_bulletin_ pour permettre de désactiver l'envoi du bulletin d'adhésion
  avec le mail de confirmation
* **Asistant d'inscription:**
  - gestion filtrage période d'adhésion avec paramètre _adhesion_category_
  - gestion multiple période d'adhésion avec chevauchement et catégorie d'adhésion
  - séparation des paramètres paiements_info_required (banque et émetteur) et paiements_numero_required
* **Parsing adresse:** gestion de la forme d'adresse en une seule ligne avec espace (sans virgule ni retour à la ligne)
  entre la rue et le code postal
* **Shortcodes:** affichage HelloAsso, filtrage par catégorie

# 0.98.60 (2021-05-28)

* **HelloAsso:**
  - correction de l'association de la bonne période d'adhésion si plusieurs se chevauchent
  - ne pas afficher de lien vers le backoffice HA si le lien vers l'adhésion est vide
* **Custom Check:** assistant d'adhésion, label avant les custom check configurées (par défaut "Options : " et
  personnalisable par argument custom_checks_label)

# 0.98.50 (2021-05-11)

* **Assistant inscription:** autoriser l'inscription à toutes les dates pour les contrats paniers modulables
* **Liste émargement:**
  - résumé quantités, correction calcul quantités
  - code pour les colonnes de contrats
  - option pour afficher une liste de livraison de commandes au lieu d'un tableau avec Var.
* **Rappels:** placeholder quantités à livrer, ne pas afficher les adhérents pour les paniers modulables
* affichage d'un lien "voir contrat" dans l'éditeur d'inscription en dessous du tableau quantités
* amélioration affichage des options de chèques dans l'éditeur d'inscription
* initialisation une seule fois des types de cotisations
* pour le paiement au mois, ne pas tenir compte des options de nombre de chèques cochées
* **Archivage:** possibilité de suppression des excels archivés
* **Producteurs:** accès aux inscriptions et aux règlements

# 0.98.25 (2021-04-04)

* **Contrats DOCX:** bug génération si les placeholders contiennent des caractères à escaper
* **Exports CSV:** entêtes manquantes
* **Finances:**
  - affichage trimestre avec année
  - possibilité de choisir une année de départ des stats
* **Quantités à livrer:** affichage trimestre avec année
* **Règlements inscriptions:** affichage du nom de la ferme du producteur ou du nom du producteur

# 0.98.15 (2021-03-31)

* **Assistant inscription:** gestion des inscriptions avec quota (inscriptions, parts ou par produit) avec granularité
  permettant de compléter au maximum le quota (en particulier paniers modulables, en ne proposant que les dates non
  complètes)

# 0.98.5 (2021-03-27)

* **Espaces documents:** erreur JS si utilisation de sous-dossiers

# 0.98.0 (2021-03-26)

* **Contrats:** options de quantité, toujours autoriser 0
* **Exports CSV:** possibilité d'exporter toutes les colonnes, les colonnes visibles ou une sélection de colonnes +
  ajout d'un filtre Exporter (colonnes) pour n'exporter que les colonnes visibles
* **Rappels:** ouverture/clôture, ne pas lister les contrats TEST
* **Shortcodes:**
  - amapress-backoffice-view, possibilité d'afficher et d'exporter des données des vues backoffice
  - display-if, display-if-logged, display-if-not-logged, paramètre key pour conditionner l'affichage également par une
    clé secrète
* **Vue backoffice:** lien de génération du shortcode [amapress-backoffice-view] à partir de toutes les vues liste du
  backoffice
* PHP8, method_exists throw if first argument is null (extractText of PHPWord)

# 0.97.115 (2021-03-18)

* **Finances:** option pour afficher la répartition par type de règlement
* amapress_contrat_instance_paiements check/uncheck
* Command Palette, check capability
* dont log error containing autoptimize_imgopt_number_field_7
* exclure le contrat lui-même de Autre contrat (car l'inscription à un contrat ne peut pas être conditionné à lui-même)
* import csv, ignorer les variantes de quotes

# 0.97.110 (2021-03-11)

* enforce SMTPAuth if SmtpUserName or UserName is not empty
* enforce ssl for port 465 and tls for 587 to avoid Smtp connection hanging

# 0.97.100 (2021-03-08)

* **Rappels:** ouverture/clôture de contrats, exclure les contrats dont le nom complémentaire est "test"
* display-if inner shortcodes
* display-if-xxx content

# 0.97.95 (2021-03-06)

* **Rappels:** ouverture/clôture de contrats, exclure les contrats dont le nom complémentaire est "test"

# 0.97.90 (2021-03-05)

* **Rappels:** distribution, à tous les amapiens, section envoi individuel et section envoi collectif
* check double reply-to

# 0.97.85 (2021-03-02)

* **Archivage:** correction lien et nombre d'utilisateurs archivables
* **Rappels:** rappels distributions, exclusion des inscriptions arrêtées en cours de route

# 0.97.80 (2021-02-25)

* **Réglements Adhésions:** ajout de colonne Nom, Prénom, Adresse, Téléphone

# 0.97.75 (2021-02-24)

* affichage des paniers reportés sur calendrier Amapress, FullCalendar et ICAL
* calendrier FullCalendar, option hidden_days
* Paniers, affichage de la distribution si report
* menu Aide, wiki et shortcode
* Rappels, aux amapiens avant chaque distribution, rappels des contenus des paniers de la semaine

# 0.97.70 (2021-02-19)

* affichage de l'aide Shortcodes, afficher le shortcode entre []
* bug affichage/prise encompte des paniers reportés dans la distribution d'origine (bug du commit [#36](https://github.com/comptoirdesappli/amapress/issues/36)cd00af4f57d50c4b11e080b04dfc57dafcf992),
  closes [#36cd00af4f57d50c4b11e080b04dfc57dafcf992](https://github.com/comptoirdesappli/amapress/issues/36cd00af4f57d50c4b11e080b04dfc57dafcf992)
* prise en compte du lieu choisi dans l'adhésion si aucune inscription (donc aucun lieu associé à un amapien)
* **Calendrier:**
  - rond de date clicable
* **Shortcodes:**
  - documentation searchbox [amapiens-role-list]
  - Le Collectif, option searchbox
  - Le Collectif, prise en comte des show_tel, show_tel_mobile, show_tel_fixe, show_adresse, show_email
  - trombinoscope, searchbox sur toutes les versions

# 0.97.60 (2021-02-14)

* Role Setter, désactivation du filtrage producteur et référent producteur
* sécurisation cookie Role Setter

# 0.97.55 (2021-02-11)

* **Messagerie:** amélioration de l'interface
* intégration aide sur le formulaire de demande d'adhésion + typo + Etat Amapress
* wp_die, access denied, 403
* **Mailing lists:**
  - option pour désactiver la synchro Framaliste
  - Framaliste, securisation possible par constantes AMAPRESS_FRAMALISTE_ADMIN_PASSWORD
  - Ouvaton, securisation possible par constantes AMAPRESS_OUVATON_SYMPA_ADMIN_PASSWORD
  - OVH, securisation possible par constantes AMAPRESS_MAILING_OVH_APPLICATION_KEY,
    AMAPRESS_MAILING_OVH_APPLICATION_SECRET, AMAPRESS_MAILING_OVH_CONSUMER_KEY
  - Sud-Ouest, securisation possible par constantes AMAPRESS_SUDOUEST_SYMPA_ADMIN_PASSWORD
* **Mes infos:** gestion téléphone fixe et deux téléphones mobiles
* **Quantités à livrer:** intégration Message au producteur
* **Règlements inscriptions:** ajout colonne Nom/Prénom (cachées par défaut)
* ajout d'un bouton pour retourner au Tableau de bord si l'accès est interdit
* check des numéro de téléphones fixes et mobiles + formattage avec espace et à 10 chiffres par défaut
* possibilité de choisir la vue d'un rôle pour l'admin ou le responsable AMAP
* **Shortcodes:** paramètres force_upper, pour forcer les informations utilisateurs en majuscules (Inscription en ligne,
  Mes infos, inscription intermittent....)

# 0.97.45 (2021-01-28)

* Téléphone 2 dans Règlement Adhésion et Inscriptions
* amélioration affichage cartes, paramètre max_zoom et padding + Leaflet.Sleep pour le scroll zoom parallèlement au
  scroll de la page
* **Menu Amapress** :
  - affichage adhésion non reçues
  - Ajouter une adhésion
* notification de publication d'articles, de visite, d'évènements, d'AG
* sécurisation stockage secret HelloAsso, dans constante AMAPRESS_HELLOASSO_API_CLIENT_SECRET de wp-config.php
* sécurisation stockage secret Stripe, dans constante AMAPRESS_PRODUCTEUR_xxx_STRIPE_SECRET_KEY de wp-config.php
* Trésorier, ajout d'une adhésion (et co-adhérent et personne hors AMAP)

# 0.97.35 (2021-01-23)

* import inscriptions, si contrat référencé par ID, check existance du contrat
* paramètres de shortcode "lieu" pointant des lieux de distribution non existants

# 0.97.30 (2021-01-22)

* autoriser la suppression d'une distribution pour l'admin si aucun panier n'y est livré (pour pouvoir supprimer une
  distribution exceptionnelle par ex)
* correction gestion déplacement de livraison de paniers multilieux (et sur la même date), affectation du panier à la
  bonne distribution + affichage des paniers dans l'éditeur de distribution

# 0.97.25 (2021-01-16)

* **Calendrier des chèques:** gestion dates de paiement avant et après le début/fin de l'inscription
* **Inscriptions en ligne:** affectation date de paiement pour éviter d'avoir 0 (01/01/1970) si pas assez de dates de
  paiement disponibles
* **Rappels:** responsable de distribution manquant, gestion de variante de mail par lieu

# 0.97.20 (2021-01-14)

* **Assistant d'inscription:** gestion du cas des inscriptions en cours d'année avec un contrat principal clos

# 0.97.15 (2021-01-12)

* **Quantités à livrer:** gestion de déplacement de livraisons sur une livraison déjà existant (merge)

# 0.97.10 (2021-01-10)

* amélioration et nettoyage interface de sélection d'heures
* amélioration interface saisie des produits/quantités des contrats
* **Calendrier des livraisons:** colonne des contrats fixe et centrage des croix
* nom de lieu complet pour AG et évènements
* nombre de chèques, ne pas afficher répartitions/dates sur l'option de x chèques n'est pas cochée
* affichage message d'avertissement de mise à jour (extension, thème) pour indiquer d'attendre la réactivation avant de
  fermer la fenêtre
* **Aide shortcodes:** filtres lieu, affichage des valeurs possibles

# 0.97.0 (2020-12-20)

* **Assistant inscription:** mention au sujet des règlements dupliquée
* **Commandes variables:** gestion de l'annulation (des dates à venir)
* **Contrats:**
  - affichage contrat complet
  - affichage max parts/adhérents
* **Paniers modulables:** correction de la suppression d'une commande pour une date (bug conservation des commandes
  antérieures des Commandes variables)
* confirmation suppression définitive depuis la corbeille
* **Adhésions:** actions groupées Marquer reçu et envoyer adhésion confirmée et envoi adhésion confirmée 
* log suppression inscriptions

# 0.96.200 (2020-12-19)

* **HelloAsso:**
  - rejet propre des formulaires non membership
  - import des adhésion par l'API HelloAsso (pour palier aux appels de callback bloqués par un parefeu par ex)
  - notification des adhésions HelloAsso non importables (plusieurs fois le même mail, adhésion avec un même mail si une
    adhésion HelloAsso est déjà enregistrée)
* **Import CSV:**
  - contrats, correction import nombre de chèques
  - import complet des adhésions (répartitions, lieu, date)
* **Etat Amapress:** lien de configuration Autoptimize

# 0.96.195 (2020-12-13)

* amélioration sélection du mode de validation des adhésions avant ouverture de l'accès aux inscriptions aux contrats
* lien vers la configuration des quantités sur le notice Vous devez configurer les quantités et tarifs des paniers
* **Assistant d'inscription en ligne:**
  - bug calcul recopieur de quantités
  - gestion du max adhérent par produit dans les paniers modulables
  - ne pas checker les quantités complètes sur la ré-édition d'une inscription
* **Etat Amapress:**
  - affichage du délai de suppression automatique des éléments en corbeille
  - affichage max coadhérents/membres du foyer et téléphone mobile obligatoire
  - suggestion amapress-amapien-agenda-viewer et amapien-details-paiements dans les shortcodes à configurer
* **Mail queue:** bouton Supprimer tous les emails en erreur
* confirmation avant mise à la corbeille

# 0.96.192 (2020-12-07)

* **HelloAsso:**
  - prendre le montant de l'adhésion et pas le total (multi adhésion)
  - option Mettre à jour les informations des comptes utilisateurs existants (désactivé par défaut)

# 0.96.190 (2020-12-06)

* cron (distrib, event, ag, visites...) planifier sur 6 semaines (pour gérer des rappels plus longtemps avant) 
* logs des mails, amélioration affichage (remplacement "," par ", " pour autoriser le break)
* log de la date de mise dans la file d'attente et affichage
* **Adhésion Custom Checkbox:**
  - bulk action pour cocher/décocher les custom check box
  - instruction de personnalisation du label des checkbox
  - placeholders custom_check1/2/3
* **Liste émargement:** option affichage du total des quantités livrées

# 0.96.185 (2020-12-01)

* filtre Adhésion future (inclure les adhésions des périodes futures) 
* **Liste émargement multi-date:** ne pas inclure les inscriptions futures et filtrage des distributions 

# 0.96.180 (2020-12-01)

* **Archivage:**
  - affichage seuil de passage en archivable en mois (contrats)
  - affichage seuil de passage en archivable en mois (période d'adhésion)
  - rappel à l'admin que des contrats/période d'adhésion/comptes utilisateurs sont archivables (weekly)
* **Emails groupés:**
  - gestion mail totalement vides
  - log fetch exceptions
  - séparation du mail de distribution automatique (sans modération) et du mail de distribution après modération
* amélioration affichage Archives Emails groupés
* amélioration affichage Logs/Error/Waiting de la file d'attente et des Emails groupés + affichage Cc/Bcc 
* gestion +33 (0)X 
* **Inscriptions:** placeholders %%coadherent.xxx%% 
* **Liste émargement:** PDF et Excel, uniquement pour responsables ou responsables de distribution de la semaine 
* **Liste multidates:** téléphone
* **Emails groupés:** indication de la relation de l'intervalle d'envoi pour les Emails groupés et file d'attente globale 
* **Liste émargement:** possibilité de génération d'une liste multidate simplifiée pour une durée déterminée (en mois) à partir d'un distribution donnée 
* ajout des destinataires : "Amapiens sans adhésion", "Amapiens avec adhésion non réglée", "Amapiens avec adhésion (et co-adhérents/membres du foyer)" 
* **Mailing list:** gestion d'exclusion d'utilisateurs et de groupes d'utilisateurs 
* filtrage adhésion ok avec coadhérents (ok_co), all avec coadhérents (all_co) et sans adhésion (none) 
* Possibilité d'éditer les mails en attente et en erreur dans la file d'attente global et celles des Emails groupés 
* shortcode responsable-distrib-info, amélioration affichage (date) + paramètre emargement_pdf_link 
* warning si fichier de log > 10MB (constante AMAPRESS_MAX_LOG_FILESIZE) 

# 0.96.168 (2020-11-26)
* placeholder %%coadherent.xxx%% si co-adhéséion partielle 

# 0.96.167 (2020-11-26)

* %%dest%% placeholder 

# 0.96.165 (2020-11-24)
* **Utilisateurs:** colonnes Adhésions, compter les adhésions en cours et futures 
* **Assistant d'inscription:** si co-adhésion partielles activées, message aux co-adhérents au sujet du fait que l'adhérent principal doit effectuer les inscriptions communes 
* **Co-adhérent:** option générale pour obliger les co-adhérents à prendre une adhésion séparée de l'adhérent principal 
* **Rappels:** ajout de 2 rappels d'envoi des quantités à livrer (par exemple, pour distinguer les commandes des contrats récurrents ou si certains producteurs ont des délais différents) 

# 0.96.160 (2020-11-24)

* **Contrat:**
  - affichage simple Nombre de paiement
  - personnalisation en heures du délai de clôture des inscriptions avant la première distribution
  - bouton d'aide au remplissage du calendrier de distribution (toutes les semaines, toutes les deux semaines, tous les
    mois, suppression de toutes les dates)
  - calendrier des paiements, bouton de remplissage Premier jour de chaque mois et Dernier jour de chaque mois
  - clone pour la semaine suivante, deux semaine après et le mois d'après + fix clone/déplacement dates spécifiques
    configuration quantités
  - contrats Commandes variables (Paniers modulables ré-éditables avant chaque distribution pour les suivantes)
  - personnalisation du délai de clôture des inscriptions avant la première distribution
* **HelloAsso:**
  - shortcode formulaire (form_type)
  - affichage message au sujet de l'utilisation de son adresse email et nom pour l'adhésion sur le formulaire HelloAsso (renouvellement)
  - log des accès au callback si la clé n'est pas valide
* filtre adhésion (ok, nok, all), check publish
* disable autoupdate for Amapress 
* filtres A venir pour Période d'adhésion, Règlement Adhésions et Contrats
* **Mes contrats:** Récapitulatif des livraisons, affichage du total dans les groupages 
* option par défaut adhesion_shift_weeks et before_close_hours

# 0.96.150 (2020-11-20)

* **ICAL:**
  - correction de l'unicité des uid d'évènements de distribution des différents contrats ([#22](https://github.com/comptoirdesappli/amapress/issues/22))
  - ajout d'un espace avant le \n si jamais il n'est pas géré ([#22](https://github.com/comptoirdesappli/amapress/issues/22))
* **HelloAsso:** correction création adhésion avec la période d'adhésion de renouvellement
* **Paiement mensuel:** check d'une date de paiement par mois minimum 
* **Etat d'Amapress:**
  - ajout de section d'information sur la configuration de l'AMAP (contrat obligatoire, co-adhésion partielle...) +
    Responsables de distribution + Espace Intermittents + Système de garde de panier + Contrats avec paiement en ligne
    Stripe + adhésion HelloAsso (lien formulaire)
* **Mes contrats:** Récapitulatif des livraisons, merge vertical des cellules producteur/date de distribution ([#24](https://github.com/comptoirdesappli/amapress/issues/24)) 
* **Shortcodes:** ajout d'un shortcode pour présenter le formulaire HelloAsso (ou lien/bouton) avec la période d'adhésion, les infos contenues dans le shortcode et les infos sur l'amapien connecté

# 0.96.145 (2020-11-18)

* **Emails groupés:** clean du dossier de stockage de l'eml et de ses pièces jointes
* **Contrats:**
  - boutons d'aide au remplissage du calendrier (Toutes les dates de distribution, Première distribution de chaque mois,
    Dernière distribution de chaque mois, Supprimer toutes les dates) ([#24](https://github.com/comptoirdesappli/amapress/issues/24))
  - possibilité de choisir des dates de paiement spécifiques pour les paiements en plusieurs fois ([#24](https://github.com/comptoirdesappli/amapress/issues/24))

# 0.96.140 (2020-11-18)

* **File d'attente':** amélioration interface configuration File d'attente des emails avec affichage du nombre de mail maximum envoyés par l'hébergement par heure/jour suivant la configuration 
* **Récapitulatif des sommes dues:** bug affichage [#24](https://github.com/comptoirdesappli/amapress/issues/24) 
* **Assistant d'isncription/adhésion:** placeholder %%me:xxx%% dans plus de messages 
* **Récapitulatif des sommes dues:** affichage Total des paiements enregistrés 
* **Règlements contrats:** row action et bulk action Marquer remis ([#23](https://github.com/comptoirdesappli/amapress/issues/23)) 

# 0.96.135 (2020-11-16)

* **Assistant Adhésion:** message adhésion obligatoire, possibilité d'afficher les infos de l'amapien connecté (%%me:
  xxx%%)
* **Calendrier/ICAL:**
  - amélioration intégration des paiements adhésion
  - amélioration intégration des paiements contrats
  - Location/Geo/Adresse des évènements
* **Don par distribution:** option pour compter le don à part
* **Mes contrats:** Détails, affichage message aux référents
* **Contrats:**
  - export Configurations de paniers
  - message de commande aux producteurs (par ex, personnalisation)
  - check de la syntaxe, nombre de valeurs et somme des répartitions des règlements en plusieurs fois

# 0.96.130 (2020-11-15)

* **Détails inscriptions:** ajout info Don par distribution
* **Récapitulatif des livraisons:**
  - ajout d'une ligne pour les Don par distribution
  - tri par date/prod/ordre des produits
* **Récapitulatif des sommes dues:** ajout info Don par distribution
* export inscription, co-adhérent 1
* **Don par distribution:**
  - affichage du total du don + possibilité de don au centime (au lieux de 0.50)
  - placeholder total_sans_don
* **Modèles de contrats:** possibilité d'export partiel

# 0.96.125 (2020-11-14)

* **Don distribution:**
  - ajout/correction placeholders don_distribution_nom/don_distribution_desc
  - prise en compte du don dans le calcul des règlements

# 0.96.120 (2020-11-13)

* **HelloAsso:** notification du trésorier, actif par défaut
* **Don par distribution:**
  - possibilité de changer le libellé
  - description placeholders Don par distribution
* possibilité de redirection de la page Wordpress d'inscription des utilisateurs vers le formulaire d'adhésion
* **HelloAsso:**
  - gestion de l'association du formulaire avec la période d'adhésion (pour le renouvellement)
  - possibilité de mettre un lien pour Adhérer via HelloAsso dans l'inscription en ligne connectée (ou Mes contrats)
  - paramètre allow_classic_adhesion pour forcer (false) l'utilisation d'HelloAsso
* **Mes contrats/Inscriptions en ligne:** paramètre allow_inscriptions_without_adhesion
* **Placeholders:** contrat, placeholders adhesion_montant, adhesion_debut, adhesion_fin (si le montant de l'adhésion à l'AMAP doit appraitre sur le contrat principal par ex)

# 0.96.115 (2020-11-11)

* **HelloAsso:**
  - envoi notification de création de compte à l'admin et email de bienvenue au nouvel amapien
  - recherche par numéro d'adhésion HelloAsso pour ne pas renvoyer la confirmation d'adhésion en cas de multiple appel
    de callback d'HelloAsso
* **Contrats:** possibilité de don libre par distribution (pour augmenter le prix du panier par exemple)
* **Placeholders:**
  - quantites_prix_unitaire
  - séparation placeholders cofoyers.noms/coadherents.noms/touscoadherents.noms
  - séparation placeholders cofoyers.contacts/coadherents.contacts/touscoadherents.contacts

# 0.96.105 (2020-11-03)

* **Créneaux distribution:** gestion des co adhérents, inscription unique pour tous les co-adhérents 
* **Distributions:** affichage lien Se proposer comme gardien de panier si pas déjà gardien 

# 0.96.101 (2020-11-03)

* **Import CSV:** prise en compte description des paniers 

# 0.96.100 (2020-11-01)

* **Créneaux:** assouplissement de la syntaxe avec autorisation d'espaces
* **Import/Export CSV:**
  - gestion des colonnes Titre, Contenu, Auteur, Résumé suivant le support par le type de post
  - suppression colonne Image
  - modèle contrat, valeurs d'exemple pour nombre de chèques de 1 à 12 en chiffres
* **Rappels:** Contrats ouverts ou bientôt ouverts, documentation placeholders disponibles (contrat)
* **Adhésion:** envoi manuel du bulletin, bulletin PDF attaché
* **Finances:** possiblité d'affichage des montants par adhérents avec groupage par date de livraison/mois/trimestre et filtrage par contrat

# 0.96.90 (2020-10-27)

* **Mailing List:**
  - suppression de la gestion de la modération
  - intégration OVH Mailinglist
  - intégration Framaliste.org

# 0.96.82 (2020-10-20)
* **Adhésions/Inscriptions en ligne:** option sans clé/public, paramètre allow_existing_mail_for_public pour autoriser les emails correspondant à des comptes existants lors du processus d'adhésion
* **ICal:** meilleure gestion cancel/request individuel pour les inscriptions/désinscriptions + organisateur
* **Mes contrats:** prise en compte allow_adhesion
* **Adhésions:**
  - mail de validation (pour indiquer instructions d'inscriptions aux contrats)
  - action Marquer reçu et envoi adhésion validée
  - action Envoi adhésion validée
* **Contrats:** gestion des contrats glissants (maximum nombre de mois) en forçant une date de fin et le calcul des
  règlements en conséquence
* **Menu Wordpress/Site:** accès aux sections principales du backoffice

# 0.96.80(2020-10-19)
* check DOCX/placeholders, check DOCX valide (et vrai zip/docx)
* **Etat Amapress:** check période d'adhésion, bulletin DOCX
* **Utilisateurs:**
  - colonne Adhésions (cachée par défaut)
  - colonne Date de création (cachée par défaut) et non triable (contrainte format interne de stockage WP)
* ajout d'un ICal pour les inscriptions/désinscriptions Responsable Distributions, visites, évènements, AG...

# 0.96.70 (2020-10-17)

* Compatibilité User Taxonomy WP 5.5
* **Assistant inscriptions:**
  - mode choix automatique des dates d'encaissement
  - mode admin, autoriser la saisie directe des règlements
* **Règlements:**
  - colonne Mois/Année
  - filtre par contrat en dropdown pour activer le sous filtrage par date (par ex)

# 0.96.65 (2020-10-15)

* **Inscription en ligne:** filter_multi_contrat, ne prendre que les contrats que l'amapien a et qui sont souscrivables
* **Mailinglist:**
  - mise à jour de la prise en charge Ouvaton
  - test de connexion
* **Mes contrats:** détails de livraisons pour les paniers modulables
* **Paiement en ligne:** amélioration affichage dans le backoffice Stripe (description, métadonnées, liens)
* **Aide Shortcodes:** amélioration interface de recherche

# 0.96.60 (2020-10-13)

* **Adhésion paiement:**
  - informations HelloAsso en colonnes et en export
  - notification au trésorier personnalisable
  - placeholder HelloAsso
  - affichage colonne email adhérent
* **Import CSV utilisateur:** prise en compte de Mettre à jour l'utilisateur quand son nom ou son email existent
* **Intégration mailinglist:** indication de la manière de gérer les modérateurs manuellement
* **Messages:** destinataires Principaux actifs et tous les amapiens actifs
* **Rappels** évènements, AG + ouverture/cloture de contrat à tous les amapiens actifs
* **Utilisateurs:** filtre "amapien actif" (avec contrat ou avec adhésion ou intermittent ou collectif)
* **HelloAsso:**
  - gestion adhérent avec champs additionels (Adresse, Téléphone et Email adhérent)
  - gestion de la date de l'adhésion
* **Contrats:** gestion unité "mètre" et "centimètre"
* placeholders post:edit-href, post:title-edit-link, post:titre-edit-lien
* **Inscriptions en ligne:**
  - amélioration affichage dates d'encaissement pour paiement au mois
  - option pour autoriser l'amapien à saisir ses dates
  - paramètres include_contrat_subnames/exclude_contrat_subnames pour inclure/exclure des contrats par Nom
    complémentaire
* **Shortcodes:**
  - display-if, mode adhesion (reçue ou non), no_adhesion (pas d'adhésion en cours), adhesion_nok (non reçue)
  - shortcode amapien-connecte-infos, Rempli les informations de l'amapien connecté (dans le texte avec placeholders
    placé dans le shortcode) + gestion getProperties sur AmapressUser
* **Paiement en ligne:** montant minimal d'activation du paiement en ligne

# 0.96.45 (2020-10-08)
* amélioration affichage thumbnail recettes
* désactivation notification de màj auto plugin/theme si mode démo ou AMAPRESS_DISABLE_AUTOUPDATE_NOTIFICATIONS
* **Inscriptions:** possibilité d'envoyer un rappel de règlement non reçu
* **Intermittents:** purge des échanges de paniers plus de vieux de x mois (18 par défaut)

# 0.96.42 (2020-10-07)
* **Contrat/Inscription:** placeholders date_ouverture/date_cloture (et variantes)

# 0.96.40 (2020-10-04)
* configuration des messages de **New User Approve** (en mode texte) dans Réglages>New User Approve

# 0.96.30 (2020-10-02)
* gestion type adhérent Amapien principal (avec membres du foyers)
* **Inscription en ligne:** possibilité de modifier les labels des contrats disponibles et des inscriptions en cours
* intégration adhésion via **HelloAsso**

# 0.96.15 (2020-09-30)
* **Inscription distribution:** filtrage par contrat, gestion multilieux
* generation distrib et paniers si changement de plage de date du contrat
* mise à jour des distributions, contrats...quand mise à jour des lieu de distributions et présentations
* **Excel Quantités en colonnes:** améliorations look (bordures, titres, alternances, centrage...)
* désactivation des notifications d'association/désassociation de coadhérents/cofoyers via constante AMAPRESS_DISABLE_COADH_ASSO_MAILS/AMAPRESS_DISABLE_COADH_DEASSO_MAILS
* désactivation des notifications d'inscription/désinscription (intermittent, évènement, distribution...) via constante AMAPRESS_DISABLE_INSCRIPTIONS_MAILS/AMAPRESS_DISABLE_DESINSCRIPTIONS_MAILS
* outils de nettoyage et régénération des distributions et paniers (en cas de changement permanent de lieu par ex)

# 0.96.10 (2020-09-29)
* amélioration interface inscription, AG, Evenement, Visite
* gestion de liens vers pages d'inscriptions aux distributions séparées pour chaque lieux
* recall clotûre contrat

# 0.96.5 (2020-09-28)
* AG titre par défaut

# 0.96.0 (2020-09-25)
* **Contrats:** paiement en ligne via **Stripe**

# 0.95.245 (2020-09-23)

* **Assistant d'inscription:**
  - autoriser les membres du foyer à éditer les inscriptions de l'adhérent principal
  - ne pas autoriser la saisie de membre du foyer pour les co adhérents
  - filtrage des dates de paiement
  - filtrage lieu choisi dans l'adhésion (si choisi)
* **Assistants Inscriptions/Adhésions en ligne:** possibilité de modification du texte "Pour démarrer votre
  adhésion/inscription pour la saison xxx, veuillez renseigner votre adresse mail :" de l'étape 1 (non connecté)
* **Nettoyage:** distributions orphelines
* **Période d'adhésion:** champs nom

# 0.95.240 (2020-09-18)
* blocage de réservation des paniers, désactivé par défaut pour admin et responsable AMAP
* placeholder ${type_adhesion}

# 0.95.235 (2020-09-17)

* **Assistant adhésion:**
  - si actif, lieu d'adhésion requis
  - possibilité de checkbox de questions supplémentaires
* **Assistant adhésion/inscription:** possibilité de personnalisation du label des champs de saisie du/des numéros de
  chèques/virement
* **Mes contrats:** détails contrat et détails livraisons, accessible par les membres du foyer
* parsing des adresses sur deux lignes (et à virgule)
* **Visites:** affichage des tableaux d'inscriptions avec créneaux
* **Inscription paniers modulables:** colonnes (dates) alternées

# 0.95.225 (2020-09-16)

* **import CSV:**
  - amélioration import contrat
  - affichage colonnes ignorées
  - gestion écart de texte typographique (guillemets)

# 0.95.220 (2020-09-15)

* **Contrats:** affichage répartition au mois
* **Inscriptions:**
  - colonne date fin, motif, nb paiement affichable et exportables
  - colonne quantités, affichée en html
* filtrage Amapiens Principaux avec et sans contrat

# 0.95.210 (2020-09-14)
* **Adhésion:** membre du foyer, prendre l'adhésion de l'adhérent principal
* **Contrats:** paiement au mois uniquement (pas de paiement total)
* **Shortcodes:** amapien-details-paiements, possibilité d'afficher les dates d'encaissement (show_dates_encaissement) et les dates de livraison (show_dates_livraisons)

# 0.95.200 (2020-09-11)
* **Assistant adhésion:** possibilité de saisir un montant adhésion réseau/AMAP libre (-1 dans la configuraton des montants de la période d'adhésion)
* **Commentaires:** possibilité de désactiver les commentaires par constante AMAPRESS_DISABLE_xxx_COMMENTS
* AMAPRESS_DISABLE_JS_ERROR_LOG

# 0.95.190 (2020-09-09)
* Assistant d'Adhésion Intermittent
* **Espace intermittents:** option pour forcer l'adhésion préalable à l'AMAP avant de pouvoir réserver un panier disponible
* **Assistant d'inscription:** 
  - paniers_modulables_editor_height, possibilité de mettre autre que px (par ex, vh)

# 0.95.180 (2020-09-07)

* **Inscription en ligne:** paramètre show_close_date pour afficher la date de clôture des inscriptions
* **Messagerie:**
  - comptes archivables
  - membres du collectif, amapiens principaux, rôles dans le collectif
  - par défaut, Reply-To à l'utilisateur connecté

# 0.95.175 (2020-09-05)
* **Adhésions:** archivage

# 0.95.170 (2020-09-04)
* ClipboardJS compatibilité
* **Archivage utilisateurs:** entrée de menu, explication et titre de vue Utilisateurs archivables
* **Inscriptions en ligne:** paramètre show_max_deliv_dates pour afficher les dates de livraison des contrats ouverts à l'inscription
* **Rappels:** quantités à la prochaine distribution, possibilité d'attacher des excels des quantités à livrer (lignes ou colonnes)

# 0.95.165 (2020-09-03)
* wp-color-picker-alpha, 5.5 compatibility in Customizer

# 0.95.160 (2020-09-02)

* **Récapitulatif des livraisons:**
  - gestion d'un excel avec les produits en colonnes et onglet par date (Quantité à la prochaine distribution, archivage)
  - placeholder %%producteur_paniers_quantites_columns%% pour les rappels

# 0.95.150 (2020-09-01)
* **Messagerie:** ne pas activer le filtrage Référents pour les catégories de destinataires
* **Inscription en ligne:** notifications séparées aux référents pour nouvelles inscriptions, modifications ou annulations

# 0.95.140 (2020-08-31)
* **Vue Référents Producteurs:** afficher tous les référents même si pas de contrat actif

# 0.95.135 (2020-08-29)
* **Assistant d'inscription:** lieu souhaité, option N'importe lequel et Aucun
* **Utilisateurs:** filtre Comptes Archivables

# 0.95.130 (2020-08-28)

* **Assistant d'inscription:**
  - paramètre allow_trombi_decline pour cacher la case à cocher "ne pas apparaitre sur le trombinoscope"
* **Mes infos:**
  - paramètre address_required pour rendre l'adresse requise
  - paramètre allow_trombi_decline pour cacher la case à cocher "ne pas apparaitre sur le trombinoscope"

# 0.95.125 (2020-08-25)

* Assistant d'Inscription d'Amapiens Externes (anon-extern-amapien-inscription)
* **Assistant d'inscription:**
  - ajout d'un Message inscription aux distributions avant la finalisation des inscriptions
  - étape finalisation, option %%remaining_contrats_list%% pour afficher les contrats en liste ul/li (au lieu de liste à
    virgule)

# 0.95.120 (2020-08-24)
* option Libellé règlements active par défaut
* type adhérent, gestion co-adhérent avec contrat(s) propre(s) et autres cas de co-adhérents
* **Assistant adhésion:**
  - période d'adhésion, option pour désactiver la saisie des numéros de chèques
* **Inscription en ligne:**
  - documentation, option allow_new_mail pour ne pas autoriser les emails non associés à un compte existant
  - option show_only_subscribable_inscriptions pour n'afficher que les inscriptions à venir

# 0.95.115 (2020-08-22)
* **Mes infos/Assistant inscription/adhésion:** ne pas lier des membres du foyer/co adhérents déjà liés (depuis l'amapien principal ou autre)

# 0.95.110 (2020-08-20)
* **Adhésion en ligne connecté:** gestion track_no_renews
* **Mes infos:** paramètre show_adherents_infos pour afficher les informations de l'adhérent (co adhérents, membres du foyer)

# 0.95.105 (2020-08-19)
* **Rappels:** distributions, second rappel distinct aux responsables de distribution (par exemple pour leur annoncer à l'avance sans la liste d'émargement)
* **Utilisateurs:** filtre Non renouvellement
* **Utilisateurs:** séparation Filtre Co-adhérents, Amapien avec co-adhérents, Amapiens principaux

# 0.95.101 (2020-08-19)
* **Assistant inscription/adhésion en ligne:** check_adhesion_received bloquant dès l'étape coordonnées

# 0.95.100 (2020-08-18)
* **File de message/PHPMailer:** compatibilité WP 5.5 avec les changements intégration de PHPMailer
* **Distributions:** inscription/désinscription, possibilité de mettre des users en CC

# 0.95.90 (2020-08-15)
* **Etat Amapress:** check rôles spécifiques rsponsables des gardiens de distribution
* possibilité de règlements contrats par prélèvement

# 0.95.85 (2020-08-13)
* **Proposer son panier:** amélioration interface
* compatibilité color picker alpha avec Wordpress 5.5
* option **Fichier template du thème** pour affecter un fichier PHP spécifiques
  - aux affichages Amapress (Producteurs/Productions/Produits...) (par défaut singular.php)
  - aux affichages Archives Amapress (Producteurs/Productions/Produits...) (par défaut singular.php)

# 0.95.80 (2020-08-07)

* **Période d'adhésion:**
  - prendre la période d'adhésion en cours (par rapport à now et pas le min des dates de contrats actifs) ou la suivante
    à défaut
  - affichage aucune période avec la date recherchée
* ajustement des droits Référents et Producteur et Trésorier
* **Réglements Adhésions et Contrats** filtre Non reçu sans condition de date "active"
* **Adhésion en ligne:** possibilité de personnalisation du message de bienvenue

# 0.95.75 (2020-08-05)
* **Adhésion en ligne connecté:** documentation skip_coords
* **Configuration Assistant Inscription/Adhésion** lien pour aller de la section inscription à la section adhésion et inversement
* possibilité de définir une répartition des chèques non linéaire en % pour les règlements en plusieurs fois

# 0.95.73 (2020-08-02)
* compatibilité autofill-event.js uniquement sur les formulaires avec validation (bug widget sélecteur de catégories)

# 0.95.72 (2020-07-31)
* **Inscription/Adhésion en ligne:** séparation de la configuration entre Gestion Adhésions et Gestion Contrats
* **Quota contrats:** affichage contrats complets

# 0.95.70 (2020-07-31)
* documentation arguments allow_adhesion_lieu/allow_adhesion_message, inscription-en-ligne/adhesion-en-ligne
* **Inscription en ligne connectée:** gestion skip_coords si adhésion nécessaire
* placeholders .pseudo (adherent/coadherent/producteur) => nickname (pseudo) + .nom_public => displayname (nom à afficher publiquement)
* placeholders producteur.rue/.code_postal/.ville
* **Contrats:** 
  - option gestion de la limite en part de récolte (Coefficient de part, par ex, 0.5, 1)
  - affichage du nombre de parts dans la vue Gestion Contrats/Edition
    - placeholders nb_inscriptions, nb_parts, max_parts, dispo_parts
* **Distributions:** instruction de distribution par production pour ajouter à la liste d'émargement et/ou au rappel aux responsables de distribution (%%paniers_instructions_distribution%%)

# 0.95.65 (2020-07-29)
* **Règlement adhésions:** row action "Approuver amapien"
* **Adhésion en ligne:** arguments allow_adhesion_message (message à l'AMAP lors de l'adhésion) et allow_adhesion_lieu (lieu de distribution souhaité)

# 0.95.60 (2020-07-27)

* affichage contrats, afficher S'inscrire/Inscrit suivant amapien connecté
* **Inscription/Adhésion en ligne:**
  - bloquer les inscriptions si check_adhesion_received
  - mode admin, proposer tous les contrats même si pas ouvert aux inscriptions en ligne
  - paramètre use_steps_nums pour ne pas afficher les numéros d'étapes
  - possibilité de personnalisation des noms des étapes et messages personnalisés
  - gestion de la numérotation des étapes dans les différents cas (adhesion, inscription, adhesion+inscription,
    agreement...)
* check placeholders dans les modèles DOCX
* **Adhésion en ligne:** étape 1, affichage des dates de la période d'adhésion et non de celles des contrats ouverts
* **Contrats:** row action pour ouvrir/fermer les inscriptions en ligne
* **Demandes d'adhésions:** champs Date de demande (remplie automatiquement si formulaire)
* **Imports CSV:** import adhésion AMAP (avec les montants par défaut et sans l'import de la répartition)
* **Période d'adhésion:** check du modèle DOCX de bulletin par rapport aux placeholders disponibles

# 0.95.55 (2020-07-23)
* **Rappels:** affichage de la date d'envoi en heure locale
* **Gardiens de paniers:** option pour permettre pour une amapien d'affecter directement son gardien de panier
* **Groupes Amapiens:** possibilité de tagger les amapiens dans des groupes sans qu'ils soient membres du collectif, par exemple, Donateur, Membre d'une autre AMAP pour l'organisation de visite à la ferme en commun, Ancien producteur...

# 0.95.45 (2020-07-21)
* %%contenu_paniers%% (ne pas lister les paniers non renseignés) + %%liste_contrats%% avec nom complémentaire + personnalisés dans le rappels des livraisons par amapien
* présentation contrat/production/producteur, lien "s'inscrire" contextuel vers page inscription-en-ligne connecté ou non
* refactoring droit et limitation de la configuration à Admin et responsable Amap
* référent producteur non affecté n'a de droit sur aucun contrat
* titre des post (articles, lieu distribution, producteur, production...) requis si éditable
* **Distributions:**
  - affichage du nombre de personnes inscrites plusieurs fois
  - bug création distribution exceptionelle (lieu requis, info nb responsable, info créneaux)
  - si pas de contrat associé à la distribution alors afficher "distribution annulée ou reportée" et ne pas afficher les
    responsables + ne pas envoyer les rappels + ne pas proposer l'inscription
* **Inscriptions distributions:** ne pas lister les lieux sans responsables requis
* ajout de placeholders pseudo (display name/nom d'affichage) pour adherent, producteur...
* remettre les droits Amapress par défaut
* **Adhésions en ligne:** nouveaux shortcodes adhesion-en-ligne et adhesion-en-ligne-connecte
* **Assemblée générale:** gestion lieu externe et inscription/désinscription

# 0.95.35 (2020-07-17)
* **Visite:** lieu externe facultatif
* **Evènements:** envoi d'un mail aux inscrits quand un nouveau commentaire est ajouté
* **Responsables de distribution:**
  - possibilité d'inscription multiple
  - affichage inscription multiple et déinscription uniquement sur l'inscription principale (qui désinscrit tout)

# 0.95.32 (2020-07-12)
* **Inscription en ligne:** paramètre show_adhesion_infos pour ne pas afficher la validité de l'adhésion

# 0.95.30 (2020-07-11)

* **Espaces documents:** documentation des paramètres title et title_tag
* **Shortcodes:**
  - gestion non connectée de next-distrib-deliv ([#20](https://github.com/comptoirdesappli/amapress/issues/20))
  - next-distrib-deliv-paniers pour afficher les paniers aux prochaines distributions ([#20](https://github.com/comptoirdesappli/amapress/issues/20))

# 0.95.26 (2020-07-08)
* **multisite, superadmin** check role avec current_user_can (renvoie toujours true), ne renvoyer true que si le rôle demandé est administrator

# 0.95.25 (2020-07-08)

* **Inscription en ligne:**
  - affichage des options de paiement dans l'Étape 7/8 : Règlement et dans le détails des inscriptions
  - options pour désactiver le paiement de l'adhésion en chèque

# 0.95.20 (2020-07-07)
* **Adhésion:** gestion type de paiement (espèce, virement, monnaie locale), infos complémentaire de règlement + placeholders + filtrage
* **Distribution:** affichage variante de contrat
* **Inscription en ligne:**
  - gestion accès public sans clé (chiffrage de la clé fixe "public" avec les SALT Wordpress)
  - gestion choix du moyen de paiement pour les adhésion (virement, chèque, espèces, monnaie locale)
  - sécurisation accès public par honeypots
  - message supplémentaire configurable lorsque les inscriptions en ligne sont closes
  - mode allow_adhesion_alone pour autoriser les adhésions même si aucun contrat n'est ouvert à l'inscription en ligne

# 0.95.10 (2020-06-29)

* **Assistant inscription:** documentation check_adhesion_received et check_adhesion_received_or_previous sur [inscription-en-ligne] et [inscription-en-ligne-connecte]
* **Demande d'adhésion:**
  - affichage de la réponse type prérempli dans l'éditeur de demande d'adhésion
  - possibilité de création du compte utilisateur + possibilité d'envoi d'un mail de réponse "automatique"/prérempli
* **Widgets:** widget Amapress Catégories (recettes, produits, évènements)

# 0.95.5 (2020-06-28)
* amélioration doc placeholder dans les contrats DOCX (tableaux de quantités simple et paniers modulables)
* check placeholders inconnus dans les tableaux des contrats DOCX
* check placeholders inconnus en tant qu'erreur
* **Mes contrats:**
  - ne pas afficher "aucun contrat principal" sur [mes-contrats]
  - ne pas appliquer le CSS additionel

# 0.95.1 (2020-06-26)
* **Créneaux:** option pour ne pas envoyer la confirmation (amapien et admin)

# 0.95.0 (2020-06-19)

* mettre l'url du webservice Amapress à sa valeur par défaut https://convert.amapress.fr
* **Distribution:**
  - afficher un bouton Inscriptions menant vers la page d'inscriptions aux distributions
  - ne pas afficher l'encadré Intermittents si le système n'est pas actif
  - affichage du nombre de responsables du lieu, lieu de substitution, contrats
  - dans editer distribution (backoffice), en mode admin direct
  - précision sur l'édition du nombre de responsable (du lieu, supplémentaires) et séparation des boutons pour éditer
    les informations de chaque distribution (horaires, créneaux, infos)
  - affichage du nombre de responsables de distribution du lieu sous nombre de responsable supplémentaires
  - affichage du nombre total de responsables de distributions
  - remplacement bouton M'inscrire par un lien vers la page d'inscription aux distributions + refactoring
* **Formulaire de demande d'adhésions:**
  - ajout du formulaire dans les infos de contacts publics
  - placement dans le menu et nommage
* **Groupes de produits:** s'assurer que le multiple est > 1
* **Inscription distribution:**
  - mode scroll, responsive = false sinon le "plus" bug
  - rôle de responsables, uniquement pour les lieux principaux si plusieurs
* **Inscription en ligne:**
  - amélioration affichage des quantités avec gestion des groupes de produits
  - autoriser les co-adhérents à voir le détails des inscriptions de l'adhérent principal
  - lien vers contrats si déjà une adhésion
  - intégration complète de New User Approve (pas de notification nouvelle inscription utilisateur native puisque New
    User Approve envoie une notif) + bug send_welcome (suite issue [#19](https://github.com/comptoirdesappli/amapress/issues/19))
  - option Rendre accessible les pré-inscriptions en ligne pour un contrat uniquement si l'amapien a une inscription à
    un ou plusieurs autres contrats
  - paramètre check_adhesion_received pour empêcher l'inscription tant que l'adhésion n'est pas validée
  - paramètre check_adhesion_received_or_previous pour empêcher l'inscription tant que l'adhésion n'est pas validée à
    moins qu'une adhésion précédente ait été validée
  - paramètres globaux dans Tableau de bord>Gestion Contrats>Configuration, onglet Contrats pour la vérification
    d'adhésions validées
  - à la fin d'une inscription, afficher le bouton 'Livraisons' à côté du bouton 'Imprimer' (pour les paniers modulables)
  - étape récapitulatif et réglements, paramètre use_quantite_tables pour afficher un tableaux des quantités (date en
    ligne, quantités en colonnes)
  - **connecté:** paramètre skip_coords pour passer l'étape de saisie de coordonnées et des coadhérents + ne pas
    afficher les inscriptions en cours (par défaut, paramètre show_current_inscriptions)
  - **vue Sommes dues:** affichage du statut des réglèments des inscriptions
  - correction et gestion de l'autofill quand l'utilisateur fait un retour dans l'historique lors de l'inscription (
    utilisation du polyfill autofill-event.js)
  - intégration complète avec New User Approve (pas d'envoi de mail de bienvenue) + paramètre send_welcome pour
    interdire l'envoi du mail de bienvenue si nécessaire + mise à jour du remplacement de wp_new_user_notification (
    filtre wp_new_user_notification_email_admin) + mise en destinataire des Cc des notifications admin (Tableau de bord>
    Paramétrage>Paramétrage, onglet Notifications) de nouvel utilisateur à approuver par New User Approve (
    issue [#19](https://github.com/comptoirdesappli/amapress/issues/19))
  - paniers modulables, gestion de multiples par groupe de produits (syntaxe [] dans l'intitulé)
  - paniers modulables, groupage, tri par groupage
  - paniers modulables, multiples, afficher le nombre actuel dans l'erreur
  - paniers modulables, permettre le groupage suivant la syntaxe [nom du groupe] spécificité (par ex, [Bière blonde]
    33cl) dans l'interface de commande
  - possibilité de répartition des paiements en plusieurs fois au mois
  - gestion du mode "inscription partielle", avec Co-adhérents et Membres du foyer séparés + paramètre
    show_modify_coords pour afficher un bouton pour modifier ses coordonnées, ses membres du foyer et ses co-adhérents +
    possibilité de sélection de ses co-adhérents parmi la liste associé à l'adhérent principal
* **Quantités à la prochaine distribution:** correction calcul total (doublé) et nombre d'adhérents quand affichage des
  amapiens
* **Rappels:**
  - correction mise à jour de la planification si un des arguments du rappels change (heures de distribution, par ex) :
    suppression de tous les évènements relatifs et replanification
  - distributions, individuel aux amapiens, liens tests n'envoyer que quelques mails de test
  - amélioration affichage des dates heures de renvois et si pas de lien de renvoi manuel
  - quantités à la prochaine distribution, excel quantité joints, gestion des groupes de produits
  - quantités à la prochaine distribution, placeholders de quantités avec gestion des groupes de produits
  - récapitulatif à la clôture des inscriptions des contrats avec excels en pièces jointes
* **Statistiques Contrats:** amélioration interface (scroll) pour les paniers modulables
* **Visite:**
  - ne pas proposer l'inscription complète s'il y a des créneaux de visite
  - affichage d'un message d'avertissement contre les modifications si des amapiens sont déjà inscrits à des créneaux
  - statut (confirmée, à confirmer, annulée)
  - lieu externe (nom, adresse, accès)
  - inscriptions en créneaux configurables (par ex, matin, après-midi, journée)
* **Archivage:** gestion des contrat paniers modulables (sauvegarde des récapitulatifs de livraisons)
* **Co-adhérents/Membres du foyer:** séparation des deux types ; en mode "inscriptions partielles" les membres du foyer sont "co-adhérents" implicites et les co-adhérents sont attachés "par contrat" ; en mode "inscriptions complètes", les deux types sont implicitement "co-adhérents" sur tous les contrats souscrits 
* **Command Palette:** ajout Documentation Amapress dans la liste des recherchables 
* **Commentaires:** auto approbation des commentaires sur les évènements (visites, ag...) si amapien connecté et si l'option est activée quelque soit le réglage de la modération des commentaires dans les options générales 
* **Contact publics:** liens pour les responsables pour voir le paramètrage des infos de contact depuis la page contact et inversement
* **Contenu des paniers:** placeholder contenu_paniers (par ex pour utilisation dans le mail de rappels aux amapiens)
* **Contrats:**
  - amélioration interface champ Durée de la période de renouvellement et ajout champ Délai d'archivage
  - lien de génération des récapitulatifs de livraisons
* **Créneaux:**
  - nommage des créneaux et gestion des créneaux multiples au même horaire de départ (matin, aprem, journée)
  - option pour ne pas envoyer la confirmation (amapien et admin)
  - placeholders: amapiens-inscrits-liste, amapiens-creneaux-liste, amapiens-creneaux-table,
    amapiens-creneaux-table-coords, amapiens-inscrits-table, amapiens-inscrits-table-coords, creneaux-table,
    creneaux-liste
  - syntaxe double parenthèses pour ne pas afficher les horaires des créneaux nommés
  - possibilité d'inscription aux créneaux (ou responsable de distribution) par les intermittents + rappels
* **Créneaux distribution:**
  - affichage d'un message d'avertissement contre les modifications si des amapiens sont déjà inscrits à des créneaux
  - complément explications de configuration
  - liste émargement, bouton pour passer dans une vue exportable en XLSX
  - rappels d'inscription aux créneaux de distributions aux amapiens non encore inscrits
* **Demande d'adhésion:** ajout status Annulée
* **Edition Inscription:** lien vers une vue Récapitulatif des livraisons
* **Mes contrats:**
  - paramètre _show_details_button_ pour afficher des boutons Détails à la place de mettre le détails des inscriptions
    dans la lsite des contrats
  - possibilité d'édition complète des membres du foyer
* **Mes infos:** possibilité d'édition complète des membres du foyer + refactoring code + validation
* **Emails groupés:**
  - affichage du nombre de mails en attente et en erreur pour chaque liste
  - affichage du nombre de membres et d'emails séparemment
  - affichage du nombre de message en attente dans le menu
  - affichage nombre de mails en attente d'envoi et de modération dans le menu
  - ajout de champs pour exclure des groupes et des utilisateurs individuels
  - File attente, logs, suppression par message orginal pour tous les destinataires
  - lien pour télécharger le fichier .eml (archives et modération)
  - nettoyage logs file d'attentes séparé et à 7 jours par défaut, le nettoyage des archives ne change pas
  - option Préserver l'émetteur et regroupage avec préfixe de sujet et Réponse à (Reply to renommé)
  - options pour inclure les modérateurs et les sans modération dans les membres
  - parsing des mails de bounces pour indiquer les destinataires en erreur
  - warning de configuration d'un Cron externe si des Emails groupés sont configurés
* **Emails groupés/Mail queue:** bouton pour remettre tous les messages en erreur dans la file d'attente d'envoi
* **Espace intermittents:** assistant d'inscription non connecté à l'espace intermittent sécurisé par clé partagée,
  shortcode [anon-intermittents-inscription]
* **Etat Amapress:**
  - check clé Akismet
  - outil pour mettre à jour le contenu du site (DB) de HTTP en HTTPS
  - suggestion plugin Meta Slider
  - suggestion plugin WP Sweep
* **Evènements:** gestion multi jour
* **Formulaire de demande d'adhésions:**
  - gestion de l'affichage et sauvegarde en demande d'adhésion des contrats actifs/souscrivables (paramètre) cochés +
    filtrage et tri par ordre d'affichage ; case à cocher intermittent ; champ adresse en textarea ; affichage lieux
    principaux uniquement
  - suggestion plugin Contact Form 7 dans les extensions recommandées (1/) + Really Simple Captcha et Honeypot for
    Contact Form 7 pour l'anti bot de spam
* **Gardiens de paniers:**
  - message spécifique pour l'inscription et désinscription
  - affichage systématique sur la liste d'émargement + gestion désinscription + gestion commentaire du gardien de panier
  - paramètre allow_gardiens_comments pour masquer le commentaire d'inscription
* **Groupes de produits:**
  - amélioration affichage des quantités livrées (contrats, assistant d'inscription) avec mise en gras de la quantité et
    groupage par groupe produits s'il y en a + refactor getQuantite_pay_at_delivery
  - assistant inscription, gestion groupage dans récapitulatif de commandes et boutons Livraisons par contrat (paniers
    modulables) (+ shortcode)
  - génération contrats, gestion dans les tableaux par date (quantite_groupe, quantite_sans_groupe) + amélioration
    affichage par défaut paniers modulables + tableau détails par date et par produit + tableau par groupe de produits
  - gestion séparé d'un modèle de contrat par défaut pour les paniers modulables avec groupes de produits
  - lister les quantités dans l'ordre des groupes de produits s'il y en a (get_contrat_quantites)
  - Quantités à la prochaine distribution, gestion des groupes de produits + amélioration affichage "non plannifié" (si
    la date demandée n'a pas de distribution le jour ou suivants) + voir information distribution
* **Inscription distributions:**
  - amélioration interface par défaut (scroll et réduction de la taille de police) + ordre des responsables sur la liste
    d'éamargement + doc des paramètres sur le shortcode historique des responsables
  - ajout options de réglages de la vue d'inscription (largeur colonne, inscription co-adhérents/membres du foyer,
    taille de police, hauteur de vue, ordre des boutons d'inscription ...) dans Tableau de bord>Distributions>
    Configuration, onglet Inscription distribution
  - paramètre _prefer_inscr_button_first_ pour placer les boutons d'inscription en premier et les inscrits ensuite.
  - rôle de responsables, description placeholders resp_role, resp_role_desc et ajout resp_role_contrats
  - rôle de responsables, possibilité d'activer un rôle quand certaines productions sont livrées
  - possibilité de gestion des inscriptions des membres du foyer par l'amapien principal
  - correction affichage scroll + amapien sans contrat
* **Inscriptions/Adhésions:** placeholder %%id%% pour insérer une référence d'inscription/adhésion (par exemple dans la
  mention au sujet des paiements)
* **Inscriptions/Désinscriptions:** paramétrage de la clôture des inscriptions/désinscriptions (distributions, visite,
  évènements, cession de paniers) en heure avec l'évènement
* **Liste émargement:**
  - affichage des numéro de mobile (adhérents/repreneurs) pour les échanges de paniers
  - affichage du repreneur "non validé" si l'échange est encore en attente
* **Mail queue:** affichage du nombre de messages en erreur et en attente d'envoi dans les onglets correspondants
* intégration WP-Sweep (exlusion des terms des taxonomies Amapress hors produits et recettes) + cacher "Nettoyer tout" 
* délai d'expiration de sessions courtes (30 jours) et longues (90 jours) 
* envoi de message par l'hébergement et Emails groupés, lors de la distribution, ne pas envoyer à l'adresse du site et à chaque Email groupé si Cc/Bcc 
* gestion de l'exclusion des taxonomies Amapress pour WP-Sweep 
* gestion des membres du foyer et co-adhérents dans onglet Ajouter une coadhérent 
* icone Forum des Amap, visible par défaut en mode Mobile + changement de couleur 
* lien vers le Forum des Amap dans le bandeau Wordpress 
* possibilité de saisie de coordonnées GPS pour les producteurs, lieu de distribution, évènements externes (et amapiens) si la résolution par adresse ne fonctionne pas (par exemple, les lieux-dits et les fermes isolées sont difficiles à trouver sur OpenStreetMap) 
* **Quantité à la prochaine distribution:** affichage des rattrapages (double distributions, ...) 
* **Rappels libres:** système de rappels libre récurrents (ou non) configurable et envoyés par le site (par exemple, rappel de location de lieu de distribution, réglement hébergement...) 
* **Référents producteurs:** affichage du niveau de liage des référents producteurs (Producteur, Producteur/lieu, Production, Production/lieu) 
* **Responsables de distribution:** possibilité de rôles de responsables de distribution 6 à 10 
* **Shortcodes:** amapien-details-livraisons, Afficher les détails des livraisons de l'amapien par date ou producteur + amapien-details-paiements, Afficher le détails des sommes dues par l'amapien + calendrier-contrats, Afficher le calendrier des livraisons des contrats de l'amapien ou de tous les contrats 
* Recherche dans le Tableau de Bord, son menu, l'Etat d'Amapress, les shortcodes, les titres de pages du site et les panneaux d'administration (titre des onglets et des pages) 

# 0.94.25 (2020-04-18)

* **Créneaux distribution:**
  - affichage des créneaux horaires et des horaires de distribution configurés
  - paramètre _allow_slots_ pour pouvoir désactiver le choix de créneau par les amapiens (et les laisser fixer par les
    reponsables)
  - documentation paramètre _allow_gardiens_
  - tranche horaire facultative (hérite des horaires de l'évènement/distribution..) pour calculer les créneaux
* **Etat Amapress:**
  - check lieu avec contrat sans référent producteur
* **Inscription distributions:**
  - paramètre _responsive_ (auto pour détection si affichage mobile)
  - paramètre _fixed_column_width_ (Par défaut, %) pour fixer la largeur des colonnes Responsables ; % pour répartir la
    largeur de colonnes sur la largeur du tableau ; en em ou px pour forcer une largeur fixe
  - paramètre _scroll_y_ (en px) pour limite la hauteur à X pixels et permet la navigation verticale avec scroll dans la
    date de distributions
  - paramètre _font_size_ pour définir la taille relative du texte dans la vue en % ou em
  - paramètres _show_adresse_ et _show_roles_ à _false_ par défaut
  - bouton _Passer en mode Admin_ pour les responsables
  - paramètre _show_responsables_, pour ne pas afficher les colonnes d'inscription _Responsable de distribution_, par
    exemple pour faire une vue _Créneaux distributions et Gardiens de paniers_ seulement.
* **Rappels:**
  - rappels "Contrats ouverts ou bientôt ouverts" et "Contrats bientôt fermés"
* **Shortcodes:**
  - inscription en ligne (connecté et non connecté) et mes-contrats, ajout de paramètres _show_current_inscriptions_ (
    pour pouvoir désactiver l'affichage des inscriptions en cours) et _show_editable_inscriptions_ (pour désactiver
    aussi l'affichage des inscriptions encore éditables)
  - mes-contrats, gestion du paramètre _only_contrats_
  - mes-contrats, inscriptions en ligne non connecté et connecté, paramètre _use_contrat_term_ pour passer les termes de
    Contrat à Commande
  - paramètre only_contrats, affichage des valeurs possibles (ID des productions)
* Contrats, colonne Résumé, affichage dates ouverture inscription + dans Editer contrat en dessous de Statut

# 0.94.10 (2020-04-16)

* **Shortcodes:**
  - _inscription en ligne (connecté et non connecté) et mes-contrats_, ajout de paramètres **
    show_current_inscriptions** (pour pouvoir désactiver l'affichage des inscriptions en cours) et **
    show_editable_inscriptions** (pour désactiver aussi l'affichage des inscriptions encore éditables)
  - _mes-contrats_, gestion du paramètre **only_contrats**

# 0.94.0 (2020-04-14)

* **Créneaux horaires:** mettre le nom du responsable qui fait l'affectation dans le mail de confirmation
* **Distributions:** créneaux horaires
  - pour les amapies pour récupérer leurs paniers pour chaque distribution
  - inscription sur la page des inscriptions des responsables des distributions
  - affectation en mode admin (dans la liste d'émargement)
  - rappels individuels à chaque amapiens avec ses paniers et le créneau affecté ou choisi
  - dans l'agenda
  - affichage dans la liste d'émargement
* **Intermittents:** mettre le nom du responsable qui fait l'affectation du panier dans le mail de confirmation

# 0.93.100 (2020-04-12)
* **Emails groupés:** n'afficher que les groupes relatifs aux lieux principaux (Modérateurs et Membres)
* **Etat Amapress:** check des paramètres Adresse web de WordPress (URL) et Adresse web du site (URL) pour préfixe HTTPS
  si SSL activé (et Really Simple SSL conseillé comme outil uniquement si tout est déjà SSL)
* **Gardiens paniers:**
  - espace intermittents, absent céder son panier, lien vers les distributions pour faire garder son panier
  - interface pour les responsables pour gérer les gardes et affectation directement depuis la page de chaque
    distribution + gestion des échanges privés définis par les responsables + interface de choix de gardiens en
    datatable
  - lien vers la carte complète des amapiens
  - lien vers la distribution depuis shortcode inscription responsables de distributions
  - message personnalisable sur la page de chaque distribution
* placeholders lien vers la page de la carte des amapiens %%lien_carte_amapiens%%
* **Editeur de menu:** ajout d'un type d'entrée Prochaine distribution
* shortcodes des prochaines distributions next-distrib-*, next-emargement-*, amapress-redirect-next-* en fonction de l'amapien connecté et de ses livraison + shortcode next-distrib-deliv, listes des prochaines distributions et livraisons
* **Référencement:** intégration exclusion des pages protégées (donc vides pour les moteurs de recherches) et des évènements privés (donc vides également) pour Google XML Sitemaps (BlueChip fork)
* possibilité d'ajouter des destinataires en copie des emails de notification de nouveaux utilisateurs et de changement de mot de passe

# 0.93.90 (2020-04-11)
* **Etat Amapress:** remplacement et installation de Google XML Sitemaps (BlueChip fork) depuis Github pour filtrage des articles/pages et autres types exclus
* affichage message de désactivation de l'installateur d'Amapress après son utilisation
* edition modèle contrat, correction check utilisation des quantités pour autoriser leur suppression
* optimisations post-its et inscriptions responsables de distribution
* optimisations diverses

# 0.93.75 (2020-04-05)

* **Contrats:** amélioration affichage colonne Résumé (contrat pas encore ouvert à inscription en ligne et contrat
  récurrents)
* **Imports CSV:**
  - affichage des erreurs en multilignes (pre-wrap)
  - ne pas autoriser l'import CSV de contrats ou configurations de paniers sur des contrats ayant des inscriptions +
    option pour Autoriser la modification dans ce cas + option pour réimporter toutes les configurations de paniers si
    aucune inscription en cours (pour respecter l'ordre d'import) ;
* **Shortcodes:**
  - refactoring et amélioration de l'affichage du shortcode users_near
  - variantes [display-if-xxx] où xxx peut être logged, not-logged, intermittent, no-contrat, responsable-distrib (est
    responsable de distribution cette semaine), responsable-amap (peut accéder au Tableau de Bord)

# 0.93.65 (2020-04-05)
* **Gardiens de paniers:** inscription gardiens de paniers (et mode admin) + option général d'activation + enregistrement/désaffection des gardes avec mails de confirmation + affichage distance des gardiens par rapport à l'amapien connecté + affichage des gardes de paniers dans la liste d'émargement + rappels aux gardiens et aux amapiens avec paniers gardés

# 0.93.45 (2020-04-04)
* **Rôles:** Producteurs, pas de publications d'articles
* compatibilité avec Autoptimize (chargement des cartes Leaflet après le chargement du document)
* **Contrats:** check des placeholders et de la validité du modèle DOCX (contrat personnaisé, contrat vierge et bulletin
  d'adhésion) en notice erreur/warning dans Edition Contrat
* **Emails groupés:**
  - alertes de configuration du SMTP du compte si le nombre de membres est supérieur à 25 et dans Etat Amapress
  - membres et sans modérateurs (Trésoriers, Responsables, Rédacteurs...)
* **Etat Amapress:**
  - check des placeholders et de la validité du modèle DOCX (contrat personnaisé, contrat vierge et bulletin d'adhésion)
    dans la section Inscription en ligne
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
  - Modérateurs, ajout des rôles descriptif individuellement ; Membres : Tous les utilisateurs enregistrés, Contrats par
    nom complémentaires, Référents Producteurs, Amapiens jamais connectés, Amapiens avec adhésion, Amapiens sans
    adhésion, Amapiens avec adhésion sans contrat, Amapiens sans contrat, Amapiens avec contrat, Co-adhérents, Amapiens
    avec co-adhérents + ajout filtrage des utilisateurs avec adhésion en cours
* **Espace Intermittents:** mode admin pour céder les paniers à la place des amapiens et affecter directement le
  repreneur + notification par mail
* **Messagerie:**
  - affichage des listes de diffusions et Emails groupés configurés
  - Emails aux amapiens, lien vers Messagerie et suppression SMS aux amapiens

# 0.93.0 (2020-03-28)

* **Distributions:**
  - Report vers distribution non existante, labels 'distribution exceptionnel' et interface de création (choix date et
    lieux) ; le choix des contrats se fait en déplaçant des paniers vers cette nouvelle distribution.
  - suppression Emails/SMS aux amapiens (car limite technique de sms: et mailto: )
* **Pages de listes** (contrat, inscriptions...), possibilité de choisir les colonnes visibles par défaut
* **Email groupés:**
  - envoi sur smtp externe dans des mail queues séparées et envoi individuel (et non avec tous les destinataires en Bcc)
    + limitation mail par heure séparée
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

* **Inscription Partielles:** gestion des inscriptions partielles suivant option 'Autoriser la co-adhésion partielle sur
  seulement certains contrats'
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
* **Assistant inscription/Mes contrats :** option ignore_renouv_delta (true par défaut) pour masquer immédiatement les
  contrats terminés

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
  - option ne pas afficher la liste d'émargement si uniquement paniers modulables + indication distribution sur titre
    Détails panier modulable
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
* **Recall Quantités à livrer :** placeholders producteur_paniers_quantites_amapiens et
  producteur_paniers_quantites_amapiens_prix
* **Quantités à livrer :**
  - affichage adhérent par défaut uniquement pour paniers modulables
  - affichage "Pas de livraison" si aucune quantité à livrer
  - amélioration affichage prochaine distribution
  - correction compte ¤Toutes¤ (paniers modulables) si pas de commande pour une date + correction affichage "En tout" si
    affichage amapiens + correction prochaine distribution le jour même

# 0.91.40 (2020-01-07)

* **Recall Quantité à livrer :**
  - cacher les filtres et liens si placeholder
  - option ne pas envoyer aux référents
  - option filtrage prod_id pour test envoi
  - correction envoi au producteur
* **Recall Responsable distribution :** option pour envoyer les listes d'émargement complète et/ou avec les contrats
  distribués
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
  - liens pour accéder aux différents formats (récap global, récap pour les dates suivantes, récap par date/prochaine
    distribution)
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
  - paramètre notify_email pour mettre en copie une ou plusieurs adresses des notifications (Changement co-adhérents,
    Non renouvellement, Adhésion, Inscription)
  - paramètre allow_coadherents_inscription (true par déf) pour autoriser les co-adhérents à adhérer à l'AMAP

# 0.85.35 (2019-09-02)

* **Assistant inscription :**
  - notification assoc/déassoc des co-adhérents
  - paramètre track_no_renews pour proposer une zone "Je ne souhaite pas renouveler" et un motif et notifier par mail (
    paramètre track_no_renews_email)

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
  - affichage email du site
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
  - check permalien toujours visibleaffichage admin email dans 2/ Configuration
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