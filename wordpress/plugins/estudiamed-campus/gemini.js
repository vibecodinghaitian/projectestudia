'use strict';
const draftButton = document.querySelector('[data-estudia-draft]');
if (draftButton) {
  draftButton.addEventListener('click', async () => {
    const status = document.getElementById('estudia-draft-status');
    const area = document.getElementById('estudia_draft');
    if (area.value.trim() && !window.confirm('¿Reemplazar la propuesta actual por un borrador nuevo?')) return;
    draftButton.disabled = true;
    status.textContent = 'Generando…';
    try {
      const response = await fetch(draftButton.dataset.estudiaDraft, {
        method: 'POST', headers: { 'X-WP-Nonce': draftButton.dataset.nonce }
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message);
      area.value = data.draft;
      status.textContent = data.flag ? 'Borrador guardado. Revisa la posible discrepancia.' : 'Borrador guardado para revisión.';
    } catch (error) {
      status.textContent = error.message;
    } finally {
      draftButton.disabled = false;
    }
  });
}
