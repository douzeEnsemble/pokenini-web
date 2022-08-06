function watchCatchStates()
{
  document.querySelectorAll('select').forEach(function(element) {
    element.addEventListener('change', onChangeCatchState)
  });
}

function onChangeCatchState(event)
{
  const target = event.target;

  saveChange(target);
  changeClass(target);
}

function saveChange(target)
{
  const dex = window.location.pathname.substring(window.location.pathname.lastIndexOf('/')+1);
  const pokemon = target.closest('.album-case').getAttribute('id');
  const catchState = target.value;

  const request = new Request(
    'https://localhost:4431/album/'+dex+'/'+pokemon+window.location.search,
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
    })
    .catch(error => {
      console.error(error);
    })
  ;
}

function changeClass(target)
{
  const albumCase = target.closest('.album-case');

  for (const i in catchStates) {
    const item = 'catch-state-'+catchStates[i].slug;

    target.classList.toggle(item);
    albumCase.classList.toggle(item);
  }
}
