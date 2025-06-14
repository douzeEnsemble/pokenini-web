function watchFilters()
{
  document.querySelectorAll('#dexFilters select').forEach(function(element) {
    element.addEventListener('change', onChangeFilters)
  });
}

function onChangeFilters(event)
{
  const target = event.target;

  filterChange(target);
}

function filterChange(target)
{
  const form = target.closest('form');
  
  var formData = new FormData(form);

  const params = new URLSearchParams();

  for (const [key, value] of formData.entries()) {
    if (value.trim() !== '') {
      params.append(key, value);
    }
  }

  const queryString = params.toString();

  window.location.assign('/'+locale+'/trainer?'+queryString);
}

