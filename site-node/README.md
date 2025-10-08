# Site Node

Mini CMS statique recevant le contenu du SaaS via push ou pull.

## Déploiement

1. Copier l'intégralité du dossier `site-node/` sur l'hébergement du domaine (HTTPS recommandé).
2. Éditer `config.php` :
   - `saas_api_url` : URL publique de l'API SaaS (ex : `https://saas.example.com/public/api.php`).
  - `site_token` : token généré dans le SaaS pour ce domaine.
  - `mode` : `push` (par défaut) ou `pull` si vous souhaitez un cron.
3. S'assurer que le dossier `storage/` est accessible en écriture par PHP.

## Synchronisation

### Mode push

Le SaaS appelle `https://<domaine>/sync.php` avec un JSON contenant pages, articles et menus. Le script vérifie le token et met à jour les fichiers `storage/*.json`.

### Mode pull

Configurer une tâche CRON pour exécuter `php /chemin/site-node/pull.php` toutes les 6 heures par exemple. Le script récupère le bundle via l'API et met à jour les fichiers JSON.

```bash
# Exemple de cron toutes les 6h
0 */6 * * * php /var/www/site-node/pull.php >> /var/log/site-pull.log 2>&1
```

## Tests rapides

```bash
# Depuis le serveur du site-node
curl -H "Authorization: Bearer <SITE_TOKEN>" \
  https://saas.example.com/public/api.php/api/v1/site/bundle

# Simuler un push manuel
curl -X POST https://domaine.example/sync.php \
  -H "Authorization: Bearer <SITE_TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"pages":[],"posts":[],"menus":[]}'
```

## Front-office

Les routes disponibles :
- `/` : page d'accueil ou liste des articles.
- `/page/<slug>` : page statique.
- `/blog` : liste des articles publiés.
- `/post/<slug>` : article complet.

Le thème `theme.php` utilise Bootstrap 5 et affiche le menu `primary` si présent.
