# Prevent Leaving Page During Pending Catch-State Change Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stop the user from silently navigating away (closing the tab, reloading, clicking a link) from the album page while a catch-state PATCH request is still in flight, by showing the browser's native `beforeunload` confirmation dialog.

**Architecture:** `public/js/album-edit.js` already handles catch-state `<select>` changes with an optimistic-UI `fetch()` PATCH per change (`saveChange`). This plan adds a module-level `pendingChangesCount` counter (incremented on change, decremented once the request settles via `.finally()`) and a `beforeunload` listener that calls `event.preventDefault()` only while the counter is above zero. No Twig or backend changes are needed — the listener is registered inside the existing `watchCatchStates()` function, which already only runs when the user is allowed to edit.

**Tech Stack:** Plain vanilla JS (no bundler/framework) served from `public/js/`, PHPUnit + Symfony Panther browser tests against Chrome/Firefox via Selenium, Moco HTTP mock fixtures.

## Global Constraints

- Full spec: `docs/superpowers/specs/2026-07-28-prevent-leave-pending-catch-state-design.md`.
- **Do not run `git commit` at any point while executing this plan.** The user's standing instruction is to never commit proactively — leave all changes staged/unstaged for the user to review and commit themselves. Each task ends with "verify tests pass", not "commit".
- **Empirically confirmed (spiked against this project's real Chrome and Firefox Selenium containers before writing this plan):** WebDriver-initiated navigation bypasses the native `beforeunload` dialog entirely on both browsers — no dialog appears, no exception is thrown. Tests must NOT attempt to trigger real navigation and observe a native dialog (e.g. via `switchTo()->alert()`); they must instead dispatch a synthetic `beforeunload` event via `executeScript` and read back `event.defaultPrevented`.
- Do not add debouncing, request queueing, or any "unsaved edit mode" tracking — only actual in-flight PATCH requests should ever block navigation. This is out of scope per the spec.
- No custom `beforeunload` dialog text — modern browsers force their own generic wording regardless of what's set on `event.returnValue`, so don't try to work around this.

---

## Task 1: Write failing browser tests for the pending-change guard

**Files:**
- Modify: `tests/resources/moco/Back/moco.json`
- Modify: `tests/src/Browser/Album/SelectAndLabelTest.php`

**Interfaces:**
- Consumes: `AbstractBrowserTestCase::getNewClient()`, `GetUserToken::getFakeUserToken()`, `User::addTrainerRole()`, `AbstractBrowserTestCase::loginUser()`, PHPUnit/Panther's `assertSelectorWillBeVisible()` — all already used elsewhere in this file.
- Produces: two new test methods (`testActionCatchStatePendingChangeBlocksUnload`, `testActionCatchStateResolvedChangeAllowsUnload`) that Task 2's JS implementation must make pass, unmodified.

The existing generic catch-state PATCH stub in `moco.json` responds instantly. To observe the "still pending" state, one specific Pokémon (`ivysaur`, present in the `demo.json` fixture already used by this test file) gets its own stub with an artificial 3-second `latency`, using the same override pattern already used for `squirtle`/`blastoise`.

- [ ] **Step 1: Add the `ivysaur` latency stub to the Moco fixture**

In `tests/resources/moco/Back/moco.json`, find this exact block (the `blastoise` 500 stub, immediately followed by the generic catch-state stub):

```json
      "text": {
        "match": "tobreed"
      }
    },
    "response": {
      "status": "500"
    }
  },
  {
    "request": {
      "uri": {
        "match": "/album/[\\w-]*/[\\w-]*"
```

Replace it with (inserting a new `ivysaur` stub between the two, same indentation style as the surrounding stubs):

```json
      "text": {
        "match": "tobreed"
      }
    },
    "response": {
      "status": "500"
    }
  },
  {
    "request": {
      "uri": {
        "match": "/album/[\\w-]*/ivysaur"
      },
      "method": "patch",
      "headers": {
        "X-Provider": {
          "match": ".*"
        },
        "authorization": {
          "match": "Bearer .*"
        }
      },
      "text": {
        "match": "no|toevolve|tobreed|totransfer|totrade|yes"
      }
    },
    "response": {
      "status": "200",
      "latency": 3000
    }
  },
  {
    "request": {
      "uri": {
        "match": "/album/[\\w-]*/[\\w-]*"
```

- [ ] **Step 2: Validate the JSON is still well-formed**

Run: `docker compose exec php php tools/jsonlint/vendor/bin/jsonlint tests/resources/moco/Back/moco.json` (or `make jsonlint` if you prefer the Makefile target)
Expected: no syntax errors reported.

- [ ] **Step 3: Add the two new test methods**

In `tests/src/Browser/Album/SelectAndLabelTest.php`, add these two methods at the end of the class, just before the closing `}` (after `testActionCatchStateChangeError`):

```php
    public function testActionCatchStatePendingChangeBlocksUnload(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demo');

        $client->executeScript("document.getElementById('ivysaur').scrollIntoView();");

        $client->click(
            $client
                ->getCrawler()
                ->filter('#ivysaur-catch-state-edit-action')
                ->link()
        );

        $form = $client->getCrawler()->filter('#album-form')->form();

        /** @var ChoiceFormField $field */
        $field = $form->get('catch-state[ivysaur]');
        $field->setValue('toevolve');

        $blocksUnload = $client->executeScript(<<<'JS'
                const event = new Event('beforeunload', {cancelable: true});
                window.dispatchEvent(event);
                return event.defaultPrevented;
            JS);

        $this->assertTrue($blocksUnload);
    }

    public function testActionCatchStateResolvedChangeAllowsUnload(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demo');

        $client->executeScript("document.getElementById('ivysaur').scrollIntoView();");

        $client->click(
            $client
                ->getCrawler()
                ->filter('#ivysaur-catch-state-edit-action')
                ->link()
        );

        $form = $client->getCrawler()->filter('#album-form')->form();

        /** @var ChoiceFormField $field */
        $field = $form->get('catch-state[ivysaur]');
        $field->setValue('toevolve');

        $this->assertSelectorWillBeVisible('#successToast-ivysaur');

        $blocksUnload = $client->executeScript(<<<'JS'
                const event = new Event('beforeunload', {cancelable: true});
                window.dispatchEvent(event);
                return event.defaultPrevented;
            JS);

        $this->assertFalse($blocksUnload);
    }
```

- [ ] **Step 4: Run the new tests to verify the first one fails**

Run: `docker compose exec php php vendor/bin/phpunit --filter "testActionCatchStatePendingChangeBlocksUnload|testActionCatchStateResolvedChangeAllowsUnload" tests/src/Browser/Album/SelectAndLabelTest.php`

Expected: `testActionCatchStatePendingChangeBlocksUnload` FAILS (`Failed asserting that false is true`) — there is no `beforeunload` listener yet, so `event.defaultPrevented` is always `false`. `testActionCatchStateResolvedChangeAllowsUnload` passes trivially (there's no listener to prevent anything either way) — that's expected at this stage; it becomes a meaningful regression guard once Task 2 adds the listener, and must stay green afterwards.

---

## Task 2: Implement the pending-changes counter and `beforeunload` guard

**Files:**
- Modify: `public/js/album-edit.js`

**Interfaces:**
- Consumes: nothing new — only browser globals (`window`, `fetch`, `Event`) and the existing DOM structure.
- Produces: module-level `pendingChangesCount` and `onBeforeUnload`, used only internally by this file.

- [ ] **Step 1: Add the counter and the `beforeunload` handler**

In `public/js/album-edit.js`, replace:

```js
function watchCatchStates() {
  document.querySelectorAll(".album-container select").forEach(function (element) {
    element.dataset.committedValue = element.value;
    element.addEventListener("change", onChangeCatchState);
  });
}
```

with:

```js
let pendingChangesCount = 0;

function watchCatchStates() {
  document.querySelectorAll(".album-container select").forEach(function (element) {
    element.dataset.committedValue = element.value;
    element.addEventListener("change", onChangeCatchState);
  });

  window.addEventListener("beforeunload", onBeforeUnload);
}

function onBeforeUnload(event) {
  if (pendingChangesCount > 0) {
    event.preventDefault();
    event.returnValue = "";
  }
}
```

- [ ] **Step 2: Increment the counter when a change is submitted**

Replace:

```js
function onChangeCatchState(event) {
  const target = event.target;
  const previousValue = target.dataset.committedValue ?? target.value;

  target.disabled = true;

  saveChange(target, previousValue);
}
```

with:

```js
function onChangeCatchState(event) {
  const target = event.target;
  const previousValue = target.dataset.committedValue ?? target.value;

  target.disabled = true;
  pendingChangesCount++;

  saveChange(target, previousValue);
}
```

- [ ] **Step 3: Decrement the counter exactly once per request, regardless of outcome**

Replace:

```js
  fetch(request)
    .then((response) => {
      if (response.status !== 200) {
        throw new Error("Something went wrong on api server!");
      }

      target.dataset.committedValue = catchState;
      changeClass(target);
      target.disabled = false;

      new bootstrap.Toast(
        document.getElementById("successToast-" + pokemon)
      ).show();
    })
    .catch((error) => {
      console.error(error);

      target.value = previousValue;
      changeClass(target);
      target.disabled = false;

      new bootstrap.Toast(
        document.getElementById("errorToast-" + pokemon)
      ).show();
    });
}
```

with:

```js
  fetch(request)
    .then((response) => {
      if (response.status !== 200) {
        throw new Error("Something went wrong on api server!");
      }

      target.dataset.committedValue = catchState;
      changeClass(target);
      target.disabled = false;

      new bootstrap.Toast(
        document.getElementById("successToast-" + pokemon)
      ).show();
    })
    .catch((error) => {
      console.error(error);

      target.value = previousValue;
      changeClass(target);
      target.disabled = false;

      new bootstrap.Toast(
        document.getElementById("errorToast-" + pokemon)
      ).show();
    })
    .finally(() => {
      pendingChangesCount--;
    });
}
```

Note: decrementing must happen in a single `.finally()`, not once each in `.then` and `.catch` — on a non-200 response, the existing code `throw`s *inside* `.then`, which then also falls through to `.catch`; decrementing in both branches would double-count for that path.

- [ ] **Step 4: Run the two new tests to verify they now pass**

Run: `docker compose exec php php vendor/bin/phpunit --filter "testActionCatchStatePendingChangeBlocksUnload|testActionCatchStateResolvedChangeAllowsUnload" tests/src/Browser/Album/SelectAndLabelTest.php`

Expected: both PASS.

- [ ] **Step 5: Run the full `SelectAndLabelTest.php` file to check for regressions**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Browser/Album/SelectAndLabelTest.php`

Expected: all tests PASS, including the pre-existing `testActionCatchStateChangeSuccess` and `testActionCatchStateChangeError` (their toast/rollback assertions are unaffected by the new counter, but this confirms the `.finally()` change didn't break the existing success/error flows).

- [ ] **Step 6: Run the same file against Firefox**

Run: `docker compose exec -e PANTHER_BROWSER_NAME=firefox -e PANTHER_SELENIUM_HOST=http://firefox:4444/wd/hub php php vendor/bin/phpunit tests/src/Browser/Album/SelectAndLabelTest.php`

Expected: all tests PASS (matches `make tests-browser-firefox` coverage for this file).
