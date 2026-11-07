/*
 * Loads what the hub puts forward (albums, elections, games) once the page is shown, so that it opens at once.
 * A part that can't be loaded, or has nothing to show, is removed, or keeps what it showed before (data-hub-highlights-fallback).
 */
function loadHubHighlights()
{
  document.querySelectorAll('[data-hub-highlights]').forEach(function(container) {
    fetch(container.getAttribute('data-hub-highlights'), {headers: {'X-Requested-With': 'XMLHttpRequest'}})
      .then(response => {
        // A redirection (to the login page) or an empty answer is not a highlight
        if (response.status !== 200 || response.redirected) {
          throw new Error('No highlights');
        }

        return response.text();
      })
      .then(html => {
        container.innerHTML = html;
        container.setAttribute('aria-busy', 'false');
        container.classList.add('is-loaded');
      })
      .catch(() => {
        if (container.hasAttribute('data-hub-highlights-fallback')) {
          container.setAttribute('aria-busy', 'false');

          return;
        }

        container.remove();
      })
    ;
  });
}

document.addEventListener('DOMContentLoaded', loadHubHighlights);
