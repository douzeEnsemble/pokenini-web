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
      if (response.status === 409) {
        throw new TooManyOnHubError();
      }

      if (response.status !== 200) {
        throw new Error('Something went wrong on api server!');
      }

      target.disabled = false;
      updateOnHubSwitches();

      new bootstrap.Toast(
        document.getElementById('successToast-'+dexSlug)
      ).show();
    })
    .catch(error => {
      console.error(error);

      target.checked = previousChecked;
      target.disabled = false;

      const toastId = error instanceof TooManyOnHubError ? 'tooManyOnHubToast' : 'errorToast-'+dexSlug;

      new bootstrap.Toast(
        document.getElementById(toastId)
      ).show();
    })
  ;
}

// Only a few albums go on the hub: the back refuses one more with a 409
class TooManyOnHubError extends Error {}

/*
 * On the trainer page, once the albums on the hub are chosen, the other ones can't be added:
 * their switch is disabled until one is removed.
 */
function updateOnHubSwitches()
{
  const list = document.querySelector('[data-max-on-hub]');

  if (null === list) {
    return;
  }

  const max = parseInt(list.getAttribute('data-max-on-hub'), 10);
  const switches = list.querySelectorAll('input[type="checkbox"][name$="-is_on_hub"]');
  const checkedCount = Array.from(switches).filter(input => input.checked).length;

  switches.forEach(function(input) {
    if (input.hasAttribute('data-locked') || input.checked) {
      return;
    }

    input.disabled = checkedCount >= max;
  });
}
