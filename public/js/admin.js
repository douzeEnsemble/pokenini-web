function watchActionLogToggles() {
    document.querySelectorAll(".admin-item-current .admin-item-toggle").forEach(function (element) {
      element.addEventListener("click", onToggleCurrent);
    });
    document.querySelectorAll(".admin-item-last .admin-item-toggle").forEach(function (element) {
      element.addEventListener("click", onToggleLast);
    });
}
  
function onToggleCurrent(event) {
    event.preventDefault();

    const target = event.target;

    const currentItem = target.closest('.admin-item-current');
    const lastItem = currentItem.parentNode.querySelector('.admin-item-last');
    const adminItem = target.closest('.admin-item');
  
    currentItem.setAttribute('hidden', true);
    lastItem.removeAttribute('hidden');

    adminItem.querySelectorAll('.icon-square').forEach(icon => {
      icon.classList.replace(
        'bg-'+adminItem.getAttribute('data-current-bg-style'), 
        'bg-'+adminItem.getAttribute('data-last-bg-style'),
      );
    });
    adminItem.querySelectorAll('.icon-square .bi').forEach(icon => {
      icon.classList.replace(
        'text-'+adminItem.getAttribute('data-current-text-style'), 
        'text-'+adminItem.getAttribute('data-last-text-style'),
      );
    });
    adminItem.querySelectorAll('.border-top').forEach(icon => {
      icon.classList.replace(
        'border-'+adminItem.getAttribute('data-current-bg-style'), 
        'border-'+adminItem.getAttribute('data-last-bg-style'),
      );
    });
}
function onToggleLast(event) {
  event.preventDefault();

  const target = event.target;

  const lastItem = target.closest('.admin-item-last');
  const currentItem = lastItem.parentNode.querySelector('.admin-item-current');
  const adminItem = target.closest('.admin-item');

  currentItem.removeAttribute('hidden');
  lastItem.setAttribute('hidden', true);

  adminItem.querySelectorAll('.icon-square').forEach(icon => {
    icon.classList.replace(
      'bg-'+adminItem.getAttribute('data-last-bg-style'),
      'bg-'+adminItem.getAttribute('data-current-bg-style'), 
    );
  });
  adminItem.querySelectorAll('.icon-square .bi').forEach(icon => {
    icon.classList.replace(
      'text-'+adminItem.getAttribute('data-last-text-style'),
      'text-'+adminItem.getAttribute('data-current-text-style'), 
    );
  });
  adminItem.querySelectorAll('.border-top').forEach(icon => {
    icon.classList.replace(
      'border-'+adminItem.getAttribute('data-last-bg-style'),
      'border-'+adminItem.getAttribute('data-current-bg-style'), 
    );
  });
}

function activateTabForHash() {
  const hash = window.location.hash;
  if (!hash) {
    return;
  }
  const target = document.querySelector(hash);
  if (!target) {
    return;
  }
  const pane = target.closest('.tab-pane');
  if (!pane || pane.classList.contains('active')) {
    return;
  }
  const trigger = document.querySelector(`[data-bs-target="#${pane.id}"]`);
  if (!trigger) {
    return;
  }
  new bootstrap.Tab(trigger).show();
  target.scrollIntoView();
}

function watchForceConfirm() {
    document.querySelectorAll(".admin-item-cta[data-confirm-message]").forEach(function (button) {
      const form = button.closest('form');
      if (!form) {
        return;
      }
      form.addEventListener('submit', function (event) {
        if (!window.confirm(button.getAttribute('data-confirm-message'))) {
          event.preventDefault();
        }
      });
    });
}