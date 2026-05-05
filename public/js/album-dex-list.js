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

  jumbotron.removeAttribute('hidden');
}

function onCloseJumbotron(event) {
  event.preventDefault();

  const jumbotron = document.getElementById('jumbotron');

  jumbotron.setAttribute('hidden', true);

  localStorage.setItem(jumbotronKey, 'true');
}
