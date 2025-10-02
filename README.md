# Carburants Malins

Application PHP pour consulter en temps réel les stations-service les moins chères autour d'une position géographique en s'appuyant sur les données publiques de [data.gouv.fr](https://www.data.gouv.fr/).

## Fonctionnalités principales

- Téléchargement et import automatique du jeu de données officiel « Prix des carburants » (script `bin/update_data.php`).
- Interface cartographique dynamique (Leaflet + OpenStreetMap) permettant :
  - la géolocalisation automatique de l'utilisateur (avec possibilité de sélectionner un point sur la carte),
  - la recherche des stations dans un rayon personnalisable,
  - l'affichage des carburants les moins chers sous forme de tuiles interactives,
  - la visualisation des stations sur la carte et l'accès direct à un itinéraire.
- CMS léger : création, édition, suppression des pages (mentions légales, CGU, etc.) et des articles de blog avec éditeur WYSIWYG (Quill) et import d'images.
- Menu personnalisable et pages responsive adaptées aux mobiles et tablettes.

## Installation

1. Installer les dépendances système nécessaires (PHP 8.1+, extensions `pdo_sqlite`, `curl`, `zip`, `fileinfo`).
2. Cloner le dépôt puis initialiser la base de données via l'import du dataset :

```bash
php bin/update_data.php
```

3. Configurer votre serveur web pour servir le dossier `public/`.
4. (Optionnel) Ajouter une tâche cron pour actualiser quotidiennement les données :

```cron
0 6 * * * php /chemin/vers/le/projet/bin/update_data.php >> /chemin/vers/le/projet/storage/update.log 2>&1
```

## Configuration

Le fichier `config.php` permet d'ajuster :

- `db_path` : chemin du fichier SQLite.
- `data_source_url` : URL de téléchargement du flux open data (instantané). Renseignez votre clé API personnelle si nécessaire (ex. `https://donnees.roulez-eco.fr/opendata/instantane?apikey=VOTRE_CLE`).
- `download_timeout` : durée maximale du téléchargement en secondes.
- `max_radius_km` : rayon maximum autorisé lors d'une recherche.

## Développement

- Les données sont stockées dans SQLite (`storage/database.sqlite`).
- Les assets front-end se trouvent dans `public/css` et `public/js`.
- Les contenus (pages/blog) sont gérés via `public/admin`.

## Sécurité

Le module d'administration est volontairement ouvert dans cette preuve de concept. Pour une mise en production, prévoir au minimum :

- Une authentification (Basic Auth, SSO, etc.).
- Une protection CSRF sur les formulaires.
- La mise en place de quotas et de caching pour les appels API.

## Licence

Projet fourni à titre d'exemple pédagogique.
