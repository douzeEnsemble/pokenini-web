function watchCookieManager()
{
  document.querySelectorAll('.cookie-manager button').forEach(function(element) {
    element.addEventListener('click', onCliceCookieManager);
  });
}

function onCliceCookieManager()
{
  tarteaucitron.userInterface.openPanel();
}