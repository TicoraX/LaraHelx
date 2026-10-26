// Loading de formularios (03 §2): al enviar, el botón principal queda deshabilitado y evita el doble envío.
document.addEventListener('submit', (event) => {
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
