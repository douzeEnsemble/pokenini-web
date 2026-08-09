function watchTrainerTabs() {
  const hash = window.location.hash;

  if (!hash) {
    return;
  }

  const tabButton = document.querySelector('[data-bs-target="' + hash + '"]');

  if (!tabButton) {
    return;
  }

  new bootstrap.Tab(tabButton).show();
}
