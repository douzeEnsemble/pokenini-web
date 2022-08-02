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
  saveColor(target);
}

function saveChange(target)
{
  const dex = window.location.pathname.substring(window.location.pathname.lastIndexOf('/')+1);
  const pokemon = target.closest('.album-case').getAttribute('id');
  const catchState = target.value;

  const request = new Request('https://localhost:4431/album/'+dex+'/'+pokemon, {method: 'PATCH', body: catchState});

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

function saveColor(target)
{
  const albumCase = target.closest('.album-case');

  for (const property in catchStateClassColors) {
    const classes = catchStateClassColors[property].split(' ');
    classes.forEach(function (item) {
      target.classList.remove(item);
      albumCase.classList.remove(item);
    });
  }

  const classes = catchStateClassColors[target.value].split(' ');
  classes.forEach(function (item) {
    target.classList.add(item);
    albumCase.classList.add(item);
  });
}
