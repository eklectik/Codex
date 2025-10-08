document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form.js-push').forEach(form => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const endpoint = form.getAttribute('action');
            const formData = new FormData(form);
            const container = document.querySelector(form.dataset.feedback ?? '#push-feedback');
            if (container) {
                container.innerHTML = '<div class="alert alert-info">Déploiement en cours...</div>';
            }
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const data = await response.json();
                if (container) {
                    if (response.ok && data.ok) {
                        container.innerHTML = '<div class="alert alert-success">Contenu déployé avec succès.</div>';
                    } else {
                        container.innerHTML = '<div class="alert alert-danger">Échec du déploiement : ' + (data.error || response.statusText) + '</div>';
                    }
                }
            } catch (error) {
                if (container) {
                    container.innerHTML = '<div class="alert alert-danger">Erreur réseau : ' + error.message + '</div>';
                }
            }
        });
    });
});
