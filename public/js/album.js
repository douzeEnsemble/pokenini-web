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

function watchScreenshotMode() {
  document
    .querySelectorAll('.screenshot-mode-on')
    .forEach(function (element) {
      element.addEventListener("click", onEnableScreenshotMode);
    });

  document
    .querySelectorAll('.screenshot-mode-off')
    .forEach(function (element) {
      element.addEventListener("click", onDisableScreenshotMode);
    });
}

function onEnableScreenshotMode(event) {
  event.preventDefault();

  document
    .querySelectorAll('.album-case-catch-state-container')
    .forEach(function (element) {
      element.setAttribute('hidden', '');
    });

  document
    .querySelectorAll('.screenshot-mode-on')
    .forEach(function (element) {
      element.setAttribute('hidden', '');
    });

  document
    .querySelectorAll('.screenshot-mode-off')
    .forEach(function (element) {
      element.removeAttribute('hidden');
    });

  bootstrap.Tooltip.getInstance('.screenshot-mode-on').hide();

  swapNode(
    document.querySelector('.screenshot-mode-off'),
    document.querySelector('.screenshot-mode-on')
  );
}

function onDisableScreenshotMode(event) {
  event.preventDefault();

  document
    .querySelectorAll('.album-case-catch-state-container')
    .forEach(function (element) {
      element.removeAttribute('hidden');
    });

  document
    .querySelectorAll('.screenshot-mode-off')
    .forEach(function (element) {
      element.setAttribute('hidden', '');
    });

  document
    .querySelectorAll('.screenshot-mode-on')
    .forEach(function (element) {
      element.removeAttribute('hidden');
    });

  bootstrap.Tooltip.getInstance('.screenshot-mode-off').hide();

  swapNode(
    document.querySelector('.screenshot-mode-on'),
    document.querySelector('.screenshot-mode-off')
  );
}

function watchToAdjustSelectSizes() {
  adjustSelectSizes();
}

function adjustSelectSizes() {
  // A multiple select with size=1 is unusable on touch devices, so keep the
  // same visible rows at every viewport width.
  document.querySelectorAll(".offcanvas select[multiple]").forEach(function (select) {
    select.size = 4;
  });
}

// https://stackoverflow.com/a/45657360
function swapNode(firstNode, secondNode) {
  const afterSecondNode = secondNode.nextElementSibling;
  const parent = secondNode.parentNode;

  firstNode.replaceWith(secondNode);
  parent.insertBefore(firstNode, afterSecondNode);
}

function watchShareLink() {
  document.querySelectorAll(".share").forEach(function (element) {
    element.addEventListener("click", onShareLinkClick);
  });
}

function onShareLinkClick(event) {
  event.preventDefault();

  const shareUrl = event.currentTarget.href;

  if (!navigator.clipboard) {
    new bootstrap.Toast(document.getElementById("shareToastError")).show();
    return;
  }

  navigator.clipboard.writeText(shareUrl)
    .then(function () {
      new bootstrap.Toast(document.getElementById("shareToastSuccess")).show();
    })
    .catch(function () {
      new bootstrap.Toast(document.getElementById("shareToastError")).show();
    });
}
