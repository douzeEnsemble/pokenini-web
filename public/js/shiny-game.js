function onShinyGameKeydown(event) {
  if (event.altKey || event.ctrlKey || event.metaKey || event.repeat) {
    return;
  }

  const choice = document.querySelector('.shiny-choice[data-key="' + event.key + '"]');
  if (null !== choice) {
    event.preventDefault();
    choice.click();

    return;
  }

  const nextRound = document.querySelector('a.next-round');
  if ('Enter' === event.key && null !== nextRound && document.activeElement !== nextRound) {
    event.preventDefault();
    nextRound.click();
  }
}

document.addEventListener('keydown', onShinyGameKeydown);
