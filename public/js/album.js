function watchCatchStates()
{
  document.querySelectorAll('select').forEach(function(element) {
    element.addEventListener('change', onChangeCatchState);
  });
}

function watchToggleEditMode()
{
  document.querySelectorAll('.album-case-catch-state-edit-action').forEach(function(element) {
    element.addEventListener('click', onToggleEditMode);
  });

  document.querySelectorAll('.album-all-catch-state-edit-action').forEach(function(element) {
    element.addEventListener('click', onToggleAllMode);
  });
  document.querySelectorAll('.album-all-catch-state-read-action').forEach(function(element) {
    element.addEventListener('click', onToggleAllMode);
  });
}

function onChangeCatchState(event)
{
  const target = event.target;

  saveChange(target);
  changeClass(target);
}

function onToggleEditMode(event)
{
  event.preventDefault();

  const target = event.target;

  toggleEditMode(target);
}

function onToggleAllMode(event)
{
  event.preventDefault();

  document.querySelectorAll('.album-case-catch-state-edit-action').forEach(function(element) {
    toggleEditMode(element);
  });

  const target = event.target;

  const editMode = target.parentElement.querySelector('.album-all-catch-state-edit-action');
  editMode.toggleAttribute('hidden');

  const readMode = target.parentElement.querySelector('.album-all-catch-state-read-action');
  readMode.toggleAttribute('hidden');
}

function saveChange(target)
{
  const pokemon = target.closest('.album-case').getAttribute('id');
  const catchState = target.value;

  const request = new Request(
    '/'+locale+'/album/'+dex+'/'+pokemon,
    {
        method: 'PATCH',
        body: catchState
    }
  );

  fetch(request)
    .then(response => {
      if (response.status !== 200) {
        throw new Error('Something went wrong on api server!');
      }

      new bootstrap.Toast(
        document.getElementById('successToast-'+pokemon)
      ).show();
    })
    .catch(error => {
      console.error(error);

      new bootstrap.Toast(
        document.getElementById('errorToast-'+pokemon)
      ).show();
    })
  ;
}

function changeClass(target)
{
  const albumCase = target.closest('.album-case');

  for (const i in catchStates) {
    const item = 'catch-state-'+catchStates[i].slug;

    target.classList.remove(item);
    albumCase.classList.remove(item);
  }

  const currentCatchState = albumCase.querySelector('select');

  target.classList.add('catch-state-'+currentCatchState.value);
  albumCase.classList.add('catch-state-'+currentCatchState.value);
}

function toggleEditMode(target)
{
  const albumCatchState = target.closest('.album-case-catch-state');
  const albumAction = albumCatchState.parentNode.querySelector('.album-case-action');

  albumCatchState.toggleAttribute('hidden');
  albumAction.toggleAttribute('hidden');
}