// The banner now presents the games and the elections: a new key, so it shows again to those who closed the elections one
const jumbotronKey = 'app/album-dex-list/jumbotron/games-elections/hidden';

function watchJumbotronCloseButton() {
  const jumbotron = document.getElementById('jumbotron');

  if (localStorage.getItem(jumbotronKey) === 'true') {
    return;
  }

  document
  .querySelectorAll(".jumbotron-close")
  .forEach(function (element) {
    element.addEventListener("click", onCloseJumbotron);
  });

  // The accept links navigate to the games or the elections - only remember the choice,
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
