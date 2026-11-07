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

function watchForceConfirm() {
    document.querySelectorAll(".admin-item-cta[data-confirm-message]").forEach(function (button) {
      const form = button.closest('form');
      if (!form) {
        return;
      }
      let confirmed = false;
      form.addEventListener('submit', function (event) {
        if (confirmed) {
          return;
        }
        event.preventDefault();
        showConfirmModal(button, function () {
          confirmed = true;
          // requestSubmit(button) keeps the clicked button's name/value.
          if (typeof form.requestSubmit === 'function') {
            form.requestSubmit(button);
          } else {
            form.submit();
          }
        }, function () {
          confirmed = false;
        });
      });
    });
}

// Bootstrap confirmation modal built on the fly. Texts come from optional
// data-confirm-title / data-confirm-label / data-cancel-label attributes on
// the button; otherwise the button's own text is the confirm label.
function showConfirmModal(button, onConfirm, onReset) {
    const isFr = (document.documentElement.lang || '').toLowerCase().startsWith('fr');
    const message = button.getAttribute('data-confirm-message');
    const title = button.getAttribute('data-confirm-title') || 'Confirmation';
    const confirmLabel = button.getAttribute('data-confirm-label') || button.textContent.trim() || 'OK';
    const cancelLabel = button.getAttribute('data-cancel-label') || (isFr ? 'Annuler' : 'Cancel');

    const modalEl = document.createElement('div');
    modalEl.className = 'modal fade';
    modalEl.tabIndex = -1;
    modalEl.setAttribute('aria-labelledby', 'confirm-modal-title');
    modalEl.setAttribute('aria-hidden', 'true');
    modalEl.innerHTML =
      '<div class="modal-dialog modal-dialog-centered"><div class="modal-content">' +
        '<div class="modal-header">' +
          '<h2 class="modal-title fs-5" id="confirm-modal-title"></h2>' +
          '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>' +
        '</div>' +
        '<div class="modal-body"><p class="mb-0 confirm-modal-message"></p></div>' +
        '<div class="modal-footer">' +
          '<button type="button" class="btn btn-secondary confirm-modal-cancel" data-bs-dismiss="modal"></button>' +
          '<button type="button" class="btn btn-danger confirm-modal-ok"></button>' +
        '</div>' +
      '</div></div>';
    modalEl.querySelector('.modal-title').textContent = title;
    modalEl.querySelector('.confirm-modal-message').textContent = message;
    modalEl.querySelector('.btn-close').setAttribute('aria-label', cancelLabel);
    modalEl.querySelector('.confirm-modal-cancel').textContent = cancelLabel;
    const okButton = modalEl.querySelector('.confirm-modal-ok');
    okButton.textContent = confirmLabel;

    document.body.appendChild(modalEl);
    const modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });

    okButton.addEventListener('click', function () {
      // Loading state: the form then submits natively (page navigation), so
      // the server-side result/error page is what the user sees next.
      okButton.disabled = true;
      modalEl.querySelector('.confirm-modal-cancel').disabled = true;
      okButton.insertAdjacentHTML(
        'afterbegin',
        '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>'
      );
      try {
        onConfirm();
      } catch (error) {
        console.error(error);
        okButton.disabled = false;
        modalEl.querySelector('.confirm-modal-cancel').disabled = false;
        okButton.querySelector('.spinner-border')?.remove();
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger mt-3 mb-0';
        alert.setAttribute('role', 'alert');
        alert.textContent = isFr ? "L'envoi a échoué, réessayez." : 'Submission failed, please try again.';
        modalEl.querySelector('.modal-body').appendChild(alert);
      }
    });

    function resetState() {
      okButton.disabled = false;
      modalEl.querySelector('.confirm-modal-cancel').disabled = false;
      okButton.querySelector('.spinner-border')?.remove();
      if (typeof onReset === 'function') {
        onReset();
      }
    }

    // bfcache restore: the page comes back mid-submit, so unlock everything.
    function onPageShow(event) {
      if (event.persisted) {
        resetState();
        modal.hide();
      }
    }
    window.addEventListener('pageshow', onPageShow);

    modalEl.addEventListener('hidden.bs.modal', function () {
      window.removeEventListener('pageshow', onPageShow);
      resetState();
      modal.dispose();
      modalEl.remove();
    });
    modal.show();
}
