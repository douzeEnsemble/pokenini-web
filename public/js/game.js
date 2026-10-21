// A first click (or key) picks a choice, a second one on the same choice confirms it: a double click answers at once
function onGameChoiceClick(event) {
  const choice = event.currentTarget;
  if (choice.classList.contains('is-selected')) {
    return;
  }

  event.preventDefault();

  document.querySelectorAll('.game-choice.is-selected').forEach((selected) => {
    selected.classList.remove('is-selected');
    selected.setAttribute('aria-pressed', 'false');
  });

  choice.classList.add('is-selected');
  choice.setAttribute('aria-pressed', 'true');
}

function onGameKeydown(event) {
  if (event.altKey || event.ctrlKey || event.metaKey || event.repeat) {
    return;
  }

  const choice = document.querySelector('.game-choice[data-key="' + event.key + '"]');
  if (null !== choice) {
    event.preventDefault();
    choice.click();

    return;
  }

  if ('Enter' !== event.key) {
    return;
  }

  const selectedChoice = document.querySelector('.game-choice.is-selected');
  if (null !== selectedChoice && !document.activeElement.classList.contains('game-choice')) {
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

document.querySelectorAll('.game-choice').forEach((choice) => {
  choice.addEventListener('click', onGameChoiceClick);
});
document.addEventListener('keydown', onGameKeydown);
