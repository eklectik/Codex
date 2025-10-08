# SaaS Control Plane

Ce dossier contient la plateforme centrale permettant de gérer les sites distants du réseau PBN.

## Installation

1. **Pré-requis** : PHP 8.0+, extension PDO (mysql et/ou sqlite), serveur web (Apache/Nginx), accès HTTPS recommandé.
2. **Cloner et configurer** :
   - Copier le dossier `saas/` sur votre serveur.
   - Dupliquer `config.php` si nécessaire et adapter :
     - `database.driver` à `mysql` pour la production ou `sqlite` pour un test local.
     - Renseigner l'hôte, la base et les identifiants MySQL si besoin.
     - Modifier les identifiants administrateur (`ADMIN_EMAIL`, `ADMIN_PASSWORD`).
3. **Base de données** :
   - MySQL : créer une base puis importer `sql/schema.mysql.sql`.
   - SQLite : la base `data/saas.sqlite` sera créée et initialisée automatiquement au premier lancement.
4. **Serveur web** : pointer le VirtualHost vers le dossier `saas/public/`.

Le schéma SQL inclut un site, une page, un article et un menu de démonstration pour démarrer rapidement.

## Utilisation

1. Accéder à `https://votre-saas/public/auth.php` et se connecter avec les identifiants admin.
2. Depuis le tableau de bord :
   - Ajouter un domaine (le token est généré automatiquement).
   - Cliquer sur "Contenu" pour gérer pages, articles et menus.
   - Utiliser le bouton "Push" pour déployer vers le site-node configuré.
3. Les routes API disponibles :
   - `GET /api.php/api/v1/sites` : liste des sites (session admin requise).
   - `POST /api.php/api/v1/sites` : création d'un site (form-data : `name`, `domain`, `mode`).
   - `POST /api.php/api/v1/push` : déploiement (form-data : `site_id`).
   - `GET /api.php/api/v1/site/bundle` : récupération du bundle (Bearer token du site requis).

### Exemples cURL

```bash
# Lister les sites (session admin requise via cookie)
curl https://saas.example.com/public/api.php/api/v1/sites

# Créer un site (identifiants admin en session)
curl -X POST https://saas.example.com/public/api.php/api/v1/sites \
  -d "name=Site Démo" -d "domain=demo-pbn.fr" -d "mode=push"

# Pousser le contenu d'un site
curl -X POST https://saas.example.com/public/api.php/api/v1/push \
  -d "site_id=1"

# Obtenir le bundle depuis un site-node
curl -H "Authorization: Bearer <SITE_TOKEN>" \
  https://saas.example.com/public/api.php/api/v1/site/bundle
```

## Sécurité

- Authentification par session pour l'interface d'administration.
- Jetons CSRF sur tous les formulaires.
- API publique protégée par jetons Bearer.
- Journalisation des déploiements dans `deploy_logs`.

## Développement

- PHP natif + PDO, aucun framework.
- Bootstrap 5 (CDN) pour l'interface.
- JS minimal (`public/js/app.js`) pour les actions asynchrones de push.
