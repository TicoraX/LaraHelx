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

// Mostrar u ocultar la contraseña. El botón viene oculto para que sin JS no aparezca.
document.querySelectorAll('[data-toggle-password]').forEach((button) => {
  const input = document.getElementById(button.getAttribute('aria-controls'));
  button.hidden = false;
  button.addEventListener('click', () => {
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(visible));
    button.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
  });
});

// Confirmación en vivo: solo una ayuda visual; la validación real la hace el servidor.
document.querySelectorAll('input[data-match]').forEach((confirmation) => {
  const original = confirmation.form?.querySelector(`input[name="${confirmation.dataset.match}"]`);
  const message = document.getElementById(confirmation.getAttribute('aria-describedby'));
  if (!original || !message?.classList.contains('field__help')) return; // con error del servidor, manda el servidor
  const check = () => {
    const done = confirmation.value !== '';
    const same = done && confirmation.value === original.value;
    message.textContent = done ? (same ? 'Las contraseñas coinciden.' : 'Las contraseñas no coinciden.') : '';
    message.dataset.state = done ? (same ? 'ok' : 'error') : '';
  };
  confirmation.addEventListener('input', check);
  original.addEventListener('input', () => confirmation.value && check());
});

// Formulario devuelto con errores: el foco va al primer campo inválido (04 §6).
document.querySelector('[aria-invalid="true"]')?.focus();

// Flash: se cierra solo con su botón, nunca por tiempo.
document.addEventListener('click', (event) => {
  event.target.closest('[data-dismiss]')?.parentElement.remove();
});
