# Share link auto-copy + toast Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Clicking the album "share" link copies the share URL to the clipboard and shows a confirmation/error toast, instead of navigating.

**Architecture:** Server-rendered toast markup (two new Bootstrap toasts, merged into the existing `_toasts.html.twig` container) plus a JS click handler on `.share` that prevents navigation, reads the link's browser-resolved absolute `href`, calls `navigator.clipboard.writeText()`, and shows the matching toast.

**Tech Stack:** Symfony 8 / Twig, vanilla JS (no bundler — plain files under `public/js/`), Bootstrap 5 (Toast component, already used elsewhere), PHPUnit + Panther (browser tests via Selenium).

## Global Constraints

- Docker-only toolchain: run all commands via `docker compose exec php ...` — no PHP/Composer/PHPUnit on the host.
- No modal, no visible link/input, no manual copy button (simplified from the issue's literal wording per explicit user decision — see spec).
- Do not change the `.share` anchor's `href`, class, or visibility condition (`{% if not dex.isPrivate %}`) — only add a click listener to it.
- `final` classes for test classes; every test class is `@internal`, uses `#[CoversClass]` or `#[CoversNothing]`, and extends `WebTestCase` (integration) or `AbstractBrowserTestCase` (browser).
- Spec: `docs/superpowers/specs/2026-07-06-share-link-copy-toast-design.md`.

---

### Task 1: Share toasts markup (translations + macro + container gate)

**Files:**
- Modify: `translations/messages+intl-icu.en.yaml`
- Modify: `translations/messages+intl-icu.fr.yaml`
- Modify: `templates/Album/_album_macros.html.twig:241` (after the existing `toasts` macro)
- Modify: `templates/Album/_toasts.html.twig` (full rewrite, currently 10 lines)
- Test: `tests/src/Integration/Controller/Album/Display/IntroTest.php`

**Interfaces:**
- Produces: Twig macro `macro.shareToasts()` (no params) rendering `<div id="shareToastSuccess">` / `<div id="shareToastError">`, used by `_toasts.html.twig`.
- Produces: translation keys `album.share.toast.success`, `album.share.toast.error`.
- Consumes: nothing new (uses existing `dex.isPrivate` and `allowedToEdit` variables already in scope in `_toasts.html.twig`, per `templates/Album/index.html.twig:66` which includes it).

- [ ] **Step 1: Write the failing test assertions**

In `tests/src/Integration/Controller/Album/Display/IntroTest.php`, every existing test method already asserts either:
```php
$this->assertCountFilter($crawler, 0, '#intro .share');
```
or
```php
$this->assertCountFilter($crawler, 1, '#intro .share');
```
Add matching toast-count assertions right after each. Since the exact line `$this->assertCountFilter($crawler, 0, '#intro .share');` is repeated identically in 5 methods (`testIntroHome`, `testIntroDemoList3`, `testIntroGoldSilverCrystal`, `testIntroBlackWhiteFrench`, `testIntroBlackWhiteEnglish`), and `$this->assertCountFilter($crawler, 1, '#intro .share');` is repeated identically in 2 methods (`testIntroDemoLiteShiny`, `testIntroDemoAnotherTrainer`), do this as two find-and-replace-all edits:

Replace every occurrence of:
```php
        $this->assertCountFilter($crawler, 0, '#intro .share');
```
with:
```php
        $this->assertCountFilter($crawler, 0, '#intro .share');

        $this->assertCountFilter($crawler, 0, '#shareToastSuccess');
        $this->assertCountFilter($crawler, 0, '#shareToastError');
```

Replace every occurrence of:
```php
        $this->assertCountFilter($crawler, 1, '#intro .share');
```
with:
```php
        $this->assertCountFilter($crawler, 1, '#intro .share');

        $this->assertCountFilter($crawler, 1, '#shareToastSuccess');
        $this->assertCountFilter($crawler, 1, '#shareToastError');
```

- [ ] **Step 2: Run the tests to verify they fail**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Album/Display/IntroTest.php
```
Expected: 7 failures, each "Failed asserting that actual size 0 matches expected size ..." (or similar) for `#shareToastSuccess`/`#shareToastError`, since those elements don't exist yet.

- [ ] **Step 3: Add translation keys**

In `translations/messages+intl-icu.en.yaml`, insert after line 142 (`open_distinguish_types: More`, the last line of the `offcanvas:` block, before the blank line and `update:` top-level section):
```yaml
  share:
    toast:
      success: "Link copied to the clipboard!"
      error: "Could not copy the link automatically, please copy it manually."
```
(indented at the same level as `offcanvas:`, i.e. 2 spaces, as a new child of `album:`)

In `translations/messages+intl-icu.fr.yaml`, insert after line 147 (`open_distinguish_types: Avancées`, the last line of the `offcanvas:` block, before the blank line and `update:` top-level section):
```yaml
  share:
    toast:
      success: "Lien copié dans le presse-papier !"
      error: "Impossible de copier le lien automatiquement, merci de le copier manuellement."
```

- [ ] **Step 4: Add the `shareToasts()` macro**

In `templates/Album/_album_macros.html.twig`, insert after line 241 (`{% endmacro %}`, closing the existing `toasts` macro) and before line 243 (`{% macro boxTitle(boxNumber) %}`):
```twig

{% macro shareToasts() %}
  <div id="shareToastSuccess" class="toast text-bg-success" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">{{ 'album.share.toast.success'|trans }}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>

  <div id="shareToastError" class="toast text-bg-danger" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">{{ 'album.share.toast.error'|trans }}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
{% endmacro %}
```

- [ ] **Step 5: Widen the toast container gate**

Replace the full content of `templates/Album/_toasts.html.twig` (currently):
```twig
{% import "Album/_album_macros.html.twig" as macro %}

{% if allowedToEdit %}
    <div class="toast-container position-fixed bottom-0 mb-5 end-0 p-3">
        {% for item in list %}
            {{ macro.toasts(item) }}
        {% endfor %}
    </div>
{% endif %}
```
with:
```twig
{% import "Album/_album_macros.html.twig" as macro %}

{% if allowedToEdit or not dex.isPrivate %}
    <div class="toast-container position-fixed bottom-0 mb-5 end-0 p-3">
        {% if allowedToEdit %}
            {% for item in list %}
                {{ macro.toasts(item) }}
            {% endfor %}
        {% endif %}

        {% if not dex.isPrivate %}
            {{ macro.shareToasts() }}
        {% endif %}
    </div>
{% endif %}
```

- [ ] **Step 6: Run the tests to verify they pass**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Album/Display/IntroTest.php
```
Expected: all tests green (`OK (7 tests, ...)`).

- [ ] **Step 7: Commit**

```bash
git add translations/messages+intl-icu.en.yaml translations/messages+intl-icu.fr.yaml templates/Album/_album_macros.html.twig templates/Album/_toasts.html.twig tests/src/Integration/Controller/Album/Display/IntroTest.php
git commit -m "feat(album): add share success/error toast markup"
```

---

### Task 2: Click handler — copy to clipboard, show toast, no navigation

**Files:**
- Modify: `public/js/album.js` (currently 155 lines, append new functions)
- Modify: `templates/Album/index.html.twig:81`
- Test: `tests/src/Browser/Album/ShareLinkTest.php` (new)

**Interfaces:**
- Consumes: `#shareToastSuccess` / `#shareToastError` elements produced by Task 1 (must land first).
- Produces: `watchShareLink()` (called once at page load from `templates/Album/index.html.twig`), `onShareLinkClick(event)` (internal, registered as the `click` listener on every `.share` element).

- [ ] **Step 1: Write the failing browser test**

Create `tests/src/Browser/Album/ShareLinkTest.php`:
```php
<?php

declare(strict_types=1);

namespace App\Tests\Browser\Album;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

/**
 * @internal
 */
#[CoversNothing]
#[Group('api-mocked-testing')]
final class ShareLinkTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    public function testClickingShareLinkWithWorkingClipboardShowsSuccessToastAndDoesNotNavigate(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/album/demoliteshiny');

        $client->executeScript(
            "Object.defineProperty(navigator, 'clipboard', { configurable: true, value: { writeText: () => Promise.resolve() } });"
        );

        $this->assertSelectorIsNotVisible('#shareToastSuccess');
        $this->assertSelectorIsNotVisible('#shareToastError');

        $urlBeforeClick = $client->getCurrentURL();

        $client->click($crawler->filter('#intro .share')->link());

        $this->assertSame($urlBeforeClick, $client->getCurrentURL());

        $this->assertSelectorWillBeVisible('#shareToastSuccess');
        $this->assertSelectorWillNotBeVisible('#shareToastSuccess');
        $this->assertSelectorWillNotBeVisible('#shareToastError');
    }

    public function testClickingShareLinkWithFailingClipboardShowsErrorToastAndDoesNotNavigate(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/album/demoliteshiny');

        $client->executeScript(
            "Object.defineProperty(navigator, 'clipboard', { configurable: true, value: { writeText: () => Promise.reject() } });"
        );

        $this->assertSelectorIsNotVisible('#shareToastSuccess');
        $this->assertSelectorIsNotVisible('#shareToastError');

        $urlBeforeClick = $client->getCurrentURL();

        $client->click($crawler->filter('#intro .share')->link());

        $this->assertSame($urlBeforeClick, $client->getCurrentURL());

        $this->assertSelectorWillBeVisible('#shareToastError');
        $this->assertSelectorWillNotBeVisible('#shareToastError');
        $this->assertSelectorWillNotBeVisible('#shareToastSuccess');
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Browser/Album/ShareLinkTest.php
```
Expected: both tests time out / fail on `assertSelectorWillBeVisible('#shareToastSuccess')` (or `#shareToastError`), since nothing shows the toast yet — clicking `.share` still navigates (no listener attached), so the `assertSame($urlBeforeClick, ...)` assertion should fail first.

- [ ] **Step 3: Implement the click handler**

Append to `public/js/album.js` (after the final `activateReadMode` function, currently ending at line 155):
```js

function watchShareLink() {
  document.querySelectorAll(".share").forEach(function (element) {
    element.addEventListener("click", onShareLinkClick);
  });
}

function onShareLinkClick(event) {
  event.preventDefault();

  const shareUrl = event.currentTarget.href;

  if (!navigator.clipboard) {
    new bootstrap.Toast(document.getElementById("shareToastError")).show();
    return;
  }

  navigator.clipboard.writeText(shareUrl)
    .then(function () {
      new bootstrap.Toast(document.getElementById("shareToastSuccess")).show();
    })
    .catch(function () {
      new bootstrap.Toast(document.getElementById("shareToastError")).show();
    });
}
```

- [ ] **Step 4: Wire it up**

In `templates/Album/index.html.twig`, change:
```twig
    <script>
    (function() {
        watchScreenshotMode();
    })();
    </script>
```
to:
```twig
    <script>
    (function() {
        watchScreenshotMode();
        watchShareLink();
    })();
    </script>
```

- [ ] **Step 5: Run the test to verify it passes**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Browser/Album/ShareLinkTest.php
```
Expected: `OK (2 tests, ...)`.

- [ ] **Step 6: Run the full existing test suite to check for regressions**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Album
docker compose exec php php vendor/bin/phpunit tests/src/Browser/Album
```
Expected: all green.

- [ ] **Step 7: Commit**

```bash
git add public/js/album.js templates/Album/index.html.twig tests/src/Browser/Album/ShareLinkTest.php
git commit -m "feat(album): copy share link to clipboard on click with toast feedback"
```

---

## Self-Review Notes

- **Spec coverage:** click interception (Task 2 Step 3-4) ✓, absolute URL via `event.currentTarget.href` (Task 2 Step 3) ✓, success/error toasts (Task 1) ✓, no-navigator.clipboard fallback (Task 2 Step 3) ✓, merged toast container (Task 1 Step 5) ✓, translations (Task 1 Step 3) ✓, existing `.share` anchor/`IntroTest` untouched apart from additive assertions ✓.
- **Deviation from spec's testing section:** the spec proposed asserting "one of the two toasts becomes visible" without controlling which, since Clipboard API availability is environment-dependent. This plan instead stubs `navigator.clipboard` via `executeScript` before clicking, making both the success and the error path deterministic and independently assertable — a stricter test than the spec called for, still fully consistent with its intent (verify the click doesn't navigate and produces the expected toast).
- **Placeholder scan:** none — every step has complete, runnable code.
- **Type/id consistency:** `#shareToastSuccess` / `#shareToastError` used identically across the macro (Task 1), the JS (Task 2), and both test files.
