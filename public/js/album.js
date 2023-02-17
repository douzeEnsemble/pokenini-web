function watchToggleShinyMode() {
  document
    .querySelectorAll(".album-modal-icon-shiny")
    .forEach(function (element) {
      element.addEventListener("click", onDisplayShinyImageMode);
    });
  document
    .querySelectorAll(".album-modal-icon-regular")
    .forEach(function (element) {
      element.addEventListener("click", onDisplayRegularImageMode);
    });
}

function onDisplayShinyImageMode(event) {
  event.preventDefault();

  const target = event.target;
  const modalBody = target.closest(".modal-body");

  modalBody.querySelector('.album-modal-image-container-regular').setAttribute("hidden", true);
  modalBody.querySelector('.album-modal-image-container-shiny').removeAttribute('hidden');
  modalBody.querySelector('.album-modal-icon-regular').classList.remove('active');
  modalBody.querySelector('.album-modal-icon-shiny').classList.add('active');
}

function onDisplayRegularImageMode(event) {
  event.preventDefault();

  const target = event.target;
  const modalBody = target.closest(".modal-body");

  modalBody.querySelector('.album-modal-image-container-regular').removeAttribute('hidden');
  modalBody.querySelector('.album-modal-image-container-shiny').setAttribute("hidden", true);
  modalBody.querySelector('.album-modal-icon-regular').classList.add('active');
  modalBody.querySelector('.album-modal-icon-shiny').classList.remove('active');
}

function watchToggleIntroDetails() {
  document
    .getElementById('toggle-intro-details')
    .addEventListener("click", onToggleIntroDetails);
}

function onToggleIntroDetails(event) {
  event.preventDefault();

  document
    .querySelectorAll('.album-intro-details')
    .forEach(function (element) {
      element.toggleAttribute('hidden');
    });
}