# Drop Catching Dashboard

Ce mini-dashboard statique permet de surveiller une liste de noms de domaine en attente de drop catching. Il affiche des métriques synthétiques ainsi qu'un tableau filtrable.

## Utilisation

1. Lancez un serveur HTTP statique dans ce dossier, par exemple :
   ```bash
   python -m http.server 8000
   ```
2. Ouvrez ensuite <http://localhost:8000> dans votre navigateur.

Les données affichées sont simulées. Adaptez le tableau `domains` dans `app.js` pour brancher vos propres données (API registrar, base SQL, etc.).
