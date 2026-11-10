document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('ImportRecipeForm');
    if (!form) return;
    const button = document.getElementById('addButton');
    const status = document.getElementById('importStatus');
    const error = document.getElementById('importError');
    let pending = false;
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (pending) return;
        error.hidden = true;
        error.textContent = '';
        const method = form.querySelector('.re-method.is-active').dataset.method;
        const field = document.getElementById(method);
        const value = method === 'image' ? field.files[0] : field.value.trim();
        if (!value) {
            error.textContent = 'Bitte fülle die gewählte Importmethode aus.';
            error.hidden = false;
            field.focus();
            return;
        }
        const body = new FormData();
        body.append(method, value);
        pending = true;
        const controls = Array.from(form.querySelectorAll('button, input, textarea'));
        const disabled = controls.map(control => control.disabled);
        controls.forEach(control => { control.disabled = true; });
        form.setAttribute('aria-busy', 'true');
        status.hidden = false;
        button.textContent = 'Wird importiert …';
        let redirecting = false;
        try {
            const response = await fetch('/importRecipe', { method: 'POST', body });
            let data;
            try { data = await response.json(); }
            catch (_) { throw new Error('Der Server hat keine gültige Antwort geliefert. Bitte versuche es erneut.'); }
            if (!response.ok || data.error) throw new Error(data.error || 'Das Rezept konnte nicht importiert werden.');
            if (!data.redirect) throw new Error('Die Weiterleitung zum Rezept fehlt. Bitte lade die Seite neu.');
            window.location.href = data.redirect;
            redirecting = true;
        } catch (failure) {
            error.textContent = failure instanceof TypeError
                ? 'Die Verbindung zum Server ist fehlgeschlagen. Bitte versuche es erneut.' : failure.message;
            error.hidden = false;
        } finally {
            if (!redirecting) {
                pending = false;
                controls.forEach((control, index) => { control.disabled = disabled[index]; });
                form.setAttribute('aria-busy', 'false');
                status.hidden = true;
                button.textContent = 'Rezept hinzufügen';
            }
        }
    });
});
