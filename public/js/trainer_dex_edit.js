function watchAttributes()
{
  document.querySelectorAll('input[type="checkbox"]').forEach(function(element) {
    element.addEventListener('change', onChangeAttributes)
  });
}

function onChangeAttributes(event)
{
  const target = event.target;

  saveChange(target);
}

function saveChange(target)
{
  const form = target.closest('form');
  const dexSlug = form.getAttribute('data-dex');
  console.log(form);

  var formData = new FormData(form);
  var data = {};
  for (const [key, value] of formData) {
    data[key.replace(dexSlug+'-', '')] = value;
  }

  const request = new Request(
    '/'+locale+'/trainer/dex/'+dexSlug,
    {
        method: 'PUT',
        body: data
    }
  );

  fetch(request)
    .then(response => {
      if (response.status !== 200) {
        throw new Error('Something went wrong on api server!');
      }

      new bootstrap.Toast(
        document.getElementById('successToast-'+dexSlug)
      ).show();
    })
    .catch(error => {
      console.error(error);

      new bootstrap.Toast(
        document.getElementById('errorToast-'+dexSlug)
      ).show();
    })
  ;
}

