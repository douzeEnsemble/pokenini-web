// A first click (or key) picks a colour set, a second one on the same set confirms it: a double click answers at once
function onColorChoiceClick(event) {
  const choice = event.currentTarget;
  if (choice.classList.contains('is-selected')) {
    return;
  }

  event.preventDefault();

  document.querySelectorAll('.color-choice.is-selected').forEach((selected) => {
    selected.classList.remove('is-selected');
    selected.setAttribute('aria-pressed', 'false');
  });

  choice.classList.add('is-selected');
  choice.setAttribute('aria-pressed', 'true');
}

function onColorGameKeydown(event) {
  if (event.altKey || event.ctrlKey || event.metaKey || event.repeat) {
    return;
  }

  const choice = document.querySelector('.color-choice[data-key="' + event.key + '"]');
  if (null !== choice) {
    event.preventDefault();
    choice.click();

    return;
  }

  if ('Enter' !== event.key) {
    return;
  }

  const selectedChoice = document.querySelector('.color-choice.is-selected');
  if (null !== selectedChoice && !document.activeElement.classList.contains('color-choice')) {
    event.preventDefault();
    selectedChoice.click();

    return;
  }

  const nextRound = document.querySelector('a.next-round');
  if (null !== nextRound && document.activeElement !== nextRound) {
    event.preventDefault();
    nextRound.click();
  }
}

document.querySelectorAll('.color-choice').forEach((choice) => {
  choice.addEventListener('click', onColorChoiceClick);
});
document.addEventListener('keydown', onColorGameKeydown);
