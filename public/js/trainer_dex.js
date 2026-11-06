function watchAttributes()
{
  document.querySelectorAll('input[type="checkbox"]').forEach(function(element) {
    element.addEventListener('change', onChangeAttributes)
  });
}

function onChangeAttributes(event)
{
  const target = event.target;
  const previousChecked = !target.checked;

  // Read the form before disabling the switch: a disabled input is left out of the form data
  const data = getAttributes(target.closest('form'));

  target.disabled = true;

  saveChange(target, previousChecked, data);
}

function getAttributes(form)
{
  const dexSlug = form.getAttribute('data-dex');

  const formData = new FormData(form);
  const data = {};
  for (const [key, value] of formData) {
    // Only checked input are returned
    data[key.replace(dexSlug+'-', '')] = true;
  }

  return data;
}

function saveChange(target, previousChecked, data)
{
  const dexSlug = target.closest('form').getAttribute('data-dex');

  const request = new Request(
    '/'+locale+'/trainer/dex/'+dexSlug,
    {
        method: 'PUT',
        body: JSON.stringify(data)
    }
  );

  fetch(request)
    .then(response => {
      if (response.status !== 200) {
        throw new Error('Something went wrong on api server!');
      }

      target.disabled = false;

      new bootstrap.Toast(
        document.getElementById('successToast-'+dexSlug)
      ).show();
    })
    .catch(error => {
      console.error(error);

      target.checked = previousChecked;
      target.disabled = false;

      new bootstrap.Toast(
        document.getElementById('errorToast-'+dexSlug)
      ).show();
    })
  ;
}

