const jumbotronKey = 'app/album-dex-list/jumbotron/hidden';

function watchJumbotronCloseButton() {
  const jumbotron = document.getElementById('jumbotron');

  console.debug(localStorage.getItem(jumbotronKey));

  if (localStorage.getItem(jumbotronKey) === 'true') {
    return;
  }

  document
  .querySelectorAll(".jumbotron-close")
  .forEach(function (element) {
    element.addEventListener("click", onCloseJumbotron);
  });

  // The accept link navigates to the election - only remember the choice,
  // don't preventDefault or the navigation itself would be cancelled.
  document
  .querySelectorAll(".jumbotron-accept")
  .forEach(function (element) {
    element.addEventListener("click", rememberJumbotronSeen);
  });

  jumbotron.removeAttribute('hidden');
}

function onCloseJumbotron(event) {
  event.preventDefault();

  document.getElementById('jumbotron').setAttribute('hidden', true);

  rememberJumbotronSeen();
}

function rememberJumbotronSeen() {
  localStorage.setItem(jumbotronKey, 'true');
}
