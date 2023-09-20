function watchActionLogToggles() {
    document.querySelectorAll(".admin-item-current .admin-item-toggle").forEach(function (element) {
      element.addEventListener("click", onToggleCurrent);
    });
    document.querySelectorAll(".admin-item-last .admin-item-toggle").forEach(function (element) {
      element.addEventListener("click", onToggleLast);
    });
}
  
function onToggleCurrent(event) {
    event.preventDefault();

    const target = event.target;

    const currentItem = target.parentNode.parentNode;
    const lastItem = target.parentNode.parentNode.nextSibling.nextSibling;
    const adminItem = target.closest('.admin-item');
  
    currentItem.setAttribute('hidden', true);
    lastItem.removeAttribute('hidden');

    adminItem.classList.replace(
      'list-group-item-'+adminItem.getAttribute('data-current-list-group-item'), 
      'list-group-item-'+adminItem.getAttribute('data-last-list-group-item')
    );
}
function onToggleLast(event) {
  event.preventDefault();

  const target = event.target;

  const lastItem = target.parentNode.parentNode;
  const currentItem = target.parentNode.parentNode.previousSibling.previousSibling;
  const adminItem = target.closest('.admin-item');

  currentItem.removeAttribute('hidden');
  lastItem.setAttribute('hidden', true);

  adminItem.classList.replace(
    'list-group-item-'+adminItem.getAttribute('data-last-list-group-item'), 
    'list-group-item-'+adminItem.getAttribute('data-current-list-group-item')
  );
}