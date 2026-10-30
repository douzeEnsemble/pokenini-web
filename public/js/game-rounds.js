// Shows all the rounds of the game, or only the wrong ones
function onGameRoundsFilterClick(event) {
  const filter = event.currentTarget;

  document.querySelectorAll('.game-rounds-filter button').forEach((button) => {
    button.classList.toggle('active', button === filter);
    button.setAttribute('aria-pressed', String(button === filter));
  });
  document.querySelector('.game-rounds').classList.toggle('only-wrong', 'wrong' === filter.dataset.filter);
}

document.querySelectorAll('.game-rounds-filter button').forEach((button) => {
  button.addEventListener('click', onGameRoundsFilterClick);
});
