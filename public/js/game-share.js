// Shares the result of a game: the share menu of the device on a phone, a copy of the text elsewhere
function showGameShareToast(id) {
  new bootstrap.Toast(document.getElementById(id)).show();
}

function copyGameShareText(text) {
  if (!navigator.clipboard) {
    showGameShareToast('gameShareToastError');

    return;
  }

  navigator.clipboard.writeText(text)
    .then(() => showGameShareToast('gameShareToastSuccess'))
    .catch(() => showGameShareToast('gameShareToastError'));
}

function onGameShareClick(event) {
  const text = event.currentTarget.dataset.shareText;

  if (navigator.share && window.matchMedia('(pointer: coarse)').matches) {
    navigator.share({ text: text })
      .catch((error) => {
        // The player closed the share menu
        if ('AbortError' === error.name) {
          return;
        }

        copyGameShareText(text);
      });

    return;
  }

  copyGameShareText(text);
}

document.querySelectorAll('.game-share').forEach((button) => {
  button.addEventListener('click', onGameShareClick);
});
