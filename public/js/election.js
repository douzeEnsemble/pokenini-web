function watchWinnerCheckboxes () {
  document
    .querySelectorAll('input[type="checkbox"][name="winners_slugs[]"')
    .forEach(function (element) {
      element.addEventListener('change', onChangeWinnerCheckbox);
    });
}

function onChangeWinnerCheckbox(event) {
  event.preventDefault();

  const target = event.target;
  
  changeCardBorderWinner(target);
  updateElectionCounter(target);
}

function changeCardBorderWinner(target) {
  const card = target.closest('.card');

  card.classList.remove('border-warning');
  if (target.checked) {
    card.classList.add('border-primary');
  } else {
    card.classList.remove('border-primary');
  }
}

function updateElectionCounter() {
  const checkboxes = document.querySelectorAll('input[type="checkbox"][name="winners_slugs[]"');

  if (null === checkboxes) {
    return;
  }
  
  const checkedCheckboxes = Array.from(checkboxes).filter(checkbox => checkbox.checked);
  const count = checkedCheckboxes.length;

  document
    .querySelectorAll('.election-counter')
    .forEach(function (element) {
      element.textContent = count;
    });
}

function watchCardClicking () {
  document
    .querySelectorAll('#election .election-card-image-container-regular')
    .forEach(function (element) {
      element.addEventListener('click', onCardClick);
    });
  document
    .querySelectorAll('#election .list-group-item strong')
    .forEach(function (element) {
      element.addEventListener('click', onCardClick);
    });
}

function onCardClick(event) {
  event.preventDefault();

  const target = event.target;
  const card = target.closest('.card');

  const checkboxes = card.querySelector('input[type="checkbox"][name="winners_slugs[]"');
  if (null !== checkboxes) {
    checkboxes.click();
  }
}

function watchSubmitAction () {
  document
    .querySelectorAll('.election-vote-submit')
    .forEach(function (element) {
      element.addEventListener('click', onSubmitVote);
    });
}

function onSubmitVote() {
  document
    .querySelectorAll('.election-vote-submit .spinner-border')
    .forEach(function (element) {
      element.attributes.removeNamedItem('hidden');
    });

  document
    .querySelectorAll('.election-vote-submit')
    .forEach(function (element) {
      const disabledAttr = document.createAttribute('disabled');
      element.attributes.setNamedItem(disabledAttr);
    });
  
  console.log('#election.submit()')
  document.getElementById('election').submit();
}

function watchCardMouseHover () {
  document
    .querySelectorAll('.card')
    .forEach(function (element) {
      element.addEventListener('mouseover', onCardMouseOver);
      element.addEventListener('mouseout', onCardMouseOut);
    });
}

function onCardMouseOver (event) {
  const card = event.target.closest('.card');

  card.classList
    .add('border-warning')
  ;
}

function onCardMouseOut (event) {
  const card = event.target.closest('.card');

  card.classList
    .remove('border-warning')
  ;
}