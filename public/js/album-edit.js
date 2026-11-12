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

function watchToggleEditMode() {
  document
    .querySelectorAll(".album-case-catch-state-edit-action")
    .forEach(function (element) {
      element.addEventListener("click", onActivateEditMode);
    });
  document
    .querySelectorAll(".album-case-catch-state-label")
    .forEach(function (element) {
      element.addEventListener("click", onActivateEditMode);
    });

  document
    .querySelectorAll(".album-all-catch-state-edit-action")
    .forEach(function (element) {
      element.addEventListener("click", onActivateAllEditMode);
    });
  document
    .querySelectorAll(".album-all-catch-state-read-action")
    .forEach(function (element) {
      element.addEventListener("click", onActivateAllReadMode);
    });
}

function onChangeCatchState(event) {
  const target = event.target;
  const previousValue = target.dataset.committedValue ?? target.value;

  target.disabled = true;
  target.setAttribute("aria-busy", "true");

  saveCatchStateChange(target, previousValue);
}

function onActivateEditMode(event) {
  event.preventDefault();

  openCatchStateSheet(event.currentTarget);
}

function onActivateAllEditMode(event) {
  event.preventDefault();

  document
    .querySelectorAll(".album-case-catch-state-edit-action")
    .forEach(function (element) {
      activateEditMode(element);
    });

  const target = event.currentTarget;

  const editMode = target.closest(".group-actions").querySelector(
    ".album-all-catch-state-edit-action"
  );
  editMode.setAttribute("hidden", true);

  const readMode = target.closest(".group-actions").querySelector(
    ".album-all-catch-state-read-action"
  );
  readMode.removeAttribute("hidden");
}

function onActivateAllReadMode(event) {
  event.preventDefault();

  document
    .querySelectorAll(".album-case-catch-state-edit-action")
    .forEach(function (element) {
      activateReadMode(element);
    });

  const target = event.currentTarget;

  const editMode = target.closest(".group-actions").querySelector(
    ".album-all-catch-state-edit-action"
  );
  editMode.removeAttribute("hidden");

  const readMode = target.closest(".group-actions").querySelector(
    ".album-all-catch-state-read-action"
  );
  readMode.setAttribute("hidden", true);
}

function saveCatchStateChange(target, previousValue, undoable = false) {
  const pokemon = target.closest(".album-case").getAttribute("id");
  const catchState = target.value;

  const request = new Request("/" + locale + "/album/" + dex + "/" + pokemon, {
    method: "PATCH",
    body: catchState,
  });

  pendingChangesCount++;

  fetch(request)
    .then((response) => {
      if (response.status !== 200) {
        throw new Error("Something went wrong on api server!");
      }

      target.dataset.committedValue = catchState;
      changeClass(target);
      target.disabled = false;
      target.removeAttribute("aria-busy");

      if (undoable) {
        showUndoToast(target.closest(".album-case"), previousValue, catchState);
      } else {
        new bootstrap.Toast(
          document.getElementById("successToast-" + pokemon)
        ).show();
      }
    })
    .catch((error) => {
      console.error(error);

      target.value = previousValue;
      changeClass(target);
      updateCatchStateLabel(target.closest(".album-case"), previousValue);
      target.disabled = false;
      target.removeAttribute("aria-busy");

      new bootstrap.Toast(
        document.getElementById("errorToast-" + pokemon)
      ).show();
    })
    .finally(() => {
      pendingChangesCount--;
    });
}

function changeClass(target) {
  const albumCase = target.closest(".album-case");

  for (const i in catchStates) {
    const item = "catch-state-" + catchStates[i].slug;

    target.classList.remove(item);
    albumCase.classList.remove(item);
  }

  const currentCatchState = albumCase.querySelector("select");

  target.classList.add("catch-state-" + currentCatchState.value);
  albumCase.classList.add("catch-state-" + currentCatchState.value);
}

function activateEditMode(target) {
  const albumCatchState = target.closest(".album-case-catch-state");
  const albumAction =
    albumCatchState.parentNode.querySelector(".album-case-action");

  albumCatchState.setAttribute("hidden", true);
  albumAction.removeAttribute("hidden");

  bootstrap.Tooltip.getInstance('.album-all-catch-state-read-action').hide();
}

function activateReadMode(target) {
  const albumCatchState = target.closest(".album-case-catch-state");
  const albumAction =
    albumCatchState.parentNode.querySelector(".album-case-action");

  albumCatchState.removeAttribute("hidden");
  albumAction.setAttribute("hidden", true);

  bootstrap.Tooltip.getInstance('.album-all-catch-state-edit-action').hide();
}

/* Quick marking: a sheet opened by the label of a case, an undo toast after
   each change, and a mode to mark several cases in a row. All of it goes
   through the select of the case, which stays the source of truth. */
let sheetCase = null;
let sheetTrigger = null;
let seriesState = null;
let seriesActive = false;
let undoChange = null;

function watchQuickMarking() {
  const sheet = document.getElementById("catch-state-sheet");

  sheet.querySelectorAll(".catch-state-sheet-option").forEach(function (option) {
    option.addEventListener("click", function () {
      const albumCase = sheetCase;
      bootstrap.Offcanvas.getInstance(sheet).hide();
      applyCatchState(albumCase, option.dataset.catchState, true);
    });
  });
  sheet.addEventListener("hidden.bs.offcanvas", function () {
    if (sheetTrigger && document.body.contains(sheetTrigger)) {
      sheetTrigger.focus();
    }
    sheetCase = null;
    sheetTrigger = null;
  });

  document.getElementById("undoToast-action").addEventListener("click", onUndo);

  document.querySelector(".album-series-action").addEventListener("click", function () {
    setSeriesMode(!seriesActive);
  });
  const bar = document.getElementById("series-bar");
  bar.querySelector(".series-bar-done").addEventListener("click", function () {
    setSeriesMode(false);
  });
  bar.querySelectorAll(".series-chip").forEach(function (chip) {
    chip.addEventListener("click", function () {
      selectSeriesState(chip.dataset.catchState);
    });
  });
  // Captured before the modal triggers of the case see the click
  document.getElementById("album-form").addEventListener("click", onSeriesCaseClick, true);
  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape" && seriesActive && !document.querySelector(".modal.show, .offcanvas.show")) {
      setSeriesMode(false);
    }
  });
}

function caseCatchState(albumCase) {
  return albumCase.querySelector("select").value;
}

function catchStateName(slug) {
  const found = catchStates.find((state) => state.slug === slug);

  return found ? found.name : slug;
}

function pokemonNameOf(albumCase) {
  return albumCase.querySelector(".album-case-name").textContent.trim();
}

function openCatchStateSheet(trigger) {
  const albumCase = trigger.closest(".album-case");
  const sheet = document.getElementById("catch-state-sheet");

  sheetCase = albumCase;
  sheetTrigger = trigger;

  document.getElementById("catch-state-sheet-title").textContent =
    quickLabels.sheetTitle.replace("{pokemonName}", pokemonNameOf(albumCase));

  const current = caseCatchState(albumCase);
  sheet.querySelectorAll(".catch-state-sheet-option").forEach(function (option) {
    const isCurrent = option.dataset.catchState === current;

    option.classList.toggle("active", isCurrent);
    option.querySelector(".bi-check-lg").hidden = !isCurrent;
    if (isCurrent) {
      option.setAttribute("aria-current", "true");
    } else {
      option.removeAttribute("aria-current");
    }
  });

  bootstrap.Offcanvas.getOrCreateInstance(sheet).show(trigger);
}

function updateCatchStateLabel(albumCase, slug) {
  const label = albumCase.querySelector(".album-case-catch-state-label");

  if (label) {
    label.textContent = catchStateName(slug);
  }
}

function applyCatchState(albumCase, slug, undoable) {
  const select = albumCase.querySelector("select");

  if (select.disabled || select.value === slug) {
    return;
  }

  const previous = select.value;

  // Optimistic: saveCatchStateChange puts it back if the server refuses
  select.value = slug;
  changeClass(select);
  updateCatchStateLabel(albumCase, slug);
  select.disabled = true;
  select.setAttribute("aria-busy", "true");

  saveCatchStateChange(select, previous, undoable);
}

function showUndoToast(albumCase, previous, current) {
  undoChange = { albumCase: albumCase, previous: previous };

  document.getElementById("undoToast-text").textContent =
    quickLabels.undoMessage
      .replace("{pokemonName}", pokemonNameOf(albumCase))
      .replace("{stateName}", catchStateName(current));

  bootstrap.Toast.getOrCreateInstance(document.getElementById("undoToast")).show();
}

function onUndo() {
  const change = undoChange;

  undoChange = null;
  bootstrap.Toast.getOrCreateInstance(document.getElementById("undoToast")).hide();

  if (change) {
    applyCatchState(change.albumCase, change.previous, false);
  }
}

function setSeriesMode(active) {
  seriesActive = active;

  const bar = document.getElementById("series-bar");
  const action = document.querySelector(".album-series-action");

  bar.hidden = !active;
  action.setAttribute("aria-pressed", active ? "true" : "false");
  action.classList.toggle("active", active);
  document.body.classList.toggle("album-series", active);

  // Bootstrap listens to the clicks of its triggers while they are captured,
  // before any listener of ours: in this mode the triggers must not be some
  document.querySelectorAll(".album-case [data-bs-toggle='modal'], .album-case [data-series-toggle]").forEach(
    function (trigger) {
      if (active) {
        trigger.dataset.seriesToggle = trigger.dataset.bsToggle;
        trigger.removeAttribute("data-bs-toggle");
      } else {
        trigger.dataset.bsToggle = trigger.dataset.seriesToggle;
        delete trigger.dataset.seriesToggle;
      }
    }
  );

  if (active) {
    const nav = document.querySelector("nav.fixed-bottom");
    const root = document.documentElement.style;

    root.setProperty("--series-bar-bottom", (nav ? nav.offsetHeight : 0) + "px");
    root.setProperty("--series-bar-height", bar.offsetHeight + "px");
    bootstrap.Toast.getOrCreateInstance(document.getElementById("undoToast")).hide();
  } else {
    seriesState = null;
    selectSeriesState(null);
    document.documentElement.style.removeProperty("--series-bar-height");
  }
}

function selectSeriesState(slug) {
  seriesState = slug;

  document.querySelectorAll("#series-bar .series-chip").forEach(function (chip) {
    const pressed = chip.dataset.catchState === slug;

    chip.setAttribute("aria-pressed", pressed ? "true" : "false");
    if (pressed) {
      chip.scrollIntoView({ block: "nearest", inline: "nearest" });
    }
  });

  const hint = document.getElementById("series-bar-hint");
  hint.textContent = slug ? hint.dataset.hint : hint.dataset.pick;
}

function onSeriesCaseClick(event) {
  if (!seriesActive) {
    return;
  }

  const albumCase = event.target.closest(".album-case");
  if (!albumCase) {
    return;
  }

  // In this mode a tap marks the case: nothing else may open
  event.preventDefault();
  event.stopPropagation();

  if (seriesState === null) {
    document.getElementById("series-bar-hint").classList.add("series-bar-hint-alert");
    window.setTimeout(function () {
      document.getElementById("series-bar-hint").classList.remove("series-bar-hint-alert");
    }, 600);

    return;
  }

  applyCatchState(albumCase, seriesState, true);
}
