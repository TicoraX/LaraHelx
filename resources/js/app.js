// Confirmación de borrado (03 §2): se registra antes que el loading para frenar el envío primero.
const confirmDialog = document.getElementById('confirmar');
let pending = null;
document.addEventListener('submit', (event) => {
  const form = event.target;
  if (!confirmDialog || !form.matches('form[data-confirm]') || form.dataset.confirmed) return;
  event.preventDefault();
  pending = form;
  confirmDialog.querySelector('h2').textContent = form.dataset.confirmTitle;
  confirmDialog.querySelector('p').textContent = form.dataset.confirm;
  confirmDialog.showModal(); // el foco va a Cancelar (autofocus); Esc cierra sin enviar
});
confirmDialog?.addEventListener('close', () => {
  if (confirmDialog.returnValue === 'ok' && pending) {
    pending.dataset.confirmed = '1';
    pending.requestSubmit();
  }
  confirmDialog.returnValue = '';
  pending = null;
});

// Loading de formularios (03 §2): al enviar, el botón principal queda deshabilitado y evita el doble envío.
document.addEventListener('submit', (event) => {
  if (event.defaultPrevented) return;
  const button = event.target.matches('form[data-submit-once]') && event.target.querySelector('button[type="submit"]');
  if (!button) return;
  button.disabled = true;
  button.setAttribute('aria-busy', 'true');
  button.textContent = button.dataset.busyText ?? 'Guardando…';
});

// Formulario devuelto con errores: el foco va al primer campo inválido (04 §6).
document.querySelector('[aria-invalid="true"]')?.focus();

// Flash: se cierra solo con su botón, nunca por tiempo.
document.addEventListener('click', (event) => {
  event.target.closest('[data-dismiss]')?.parentElement.remove();
});
