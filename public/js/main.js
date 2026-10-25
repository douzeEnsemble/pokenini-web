function watchCookieManager() {
  document.querySelectorAll('.cookie-manager button').forEach(function (element) {
    element.addEventListener('click', onCliceCookieManager);
  });
}

function onCliceCookieManager() {
  tarteaucitron.userInterface.openPanel();
}

function watchDexCategoryLinks() {
  document.querySelectorAll('.dex-category-link').forEach(function (element) {
    element.addEventListener('click', onClickDexCategoryLink);
  });
}

function onClickDexCategoryLink(event) {
  const list = document.querySelector(event.currentTarget.dataset.dexCategoryList);
  if (list) {
    bootstrap.Collapse.getOrCreateInstance(list, { toggle: false }).show();
  }
}
