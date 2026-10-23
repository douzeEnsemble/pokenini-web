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

// Shows the chosen version (with the grid or the score alone) in the preview, and shares it
function onGameShareVersionClick(event) {
  const version = event.currentTarget;
  const panel = version.closest('.game-share-panel');
  const text = 'grid' === version.dataset.version ? panel.dataset.shareGrid : panel.dataset.shareScore;

  panel.querySelectorAll('.game-share-version').forEach((button) => {
    button.classList.toggle('active', button === version);
    button.setAttribute('aria-pressed', String(button === version));
  });
  panel.querySelector('.game-share-preview').textContent = text;
  panel.querySelector('.game-share').dataset.shareText = text;
}

document.querySelectorAll('.game-share-version').forEach((button) => {
  button.addEventListener('click', onGameShareVersionClick);
});
document.querySelectorAll('.game-share').forEach((button) => {
  button.addEventListener('click', onGameShareClick);
});
