function watchCatchStates() {
  document.querySelectorAll(".album-container select").forEach(function (element) {
    element.addEventListener("change", onChangeCatchState);
  });
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

  saveChange(target);
  changeClass(target);
}

function onActivateEditMode(event) {
  event.preventDefault();

  const target = event.target;

  activateEditMode(target);
}

function onActivateAllEditMode(event) {
  event.preventDefault();

  document
    .querySelectorAll(".album-case-catch-state-edit-action")
    .forEach(function (element) {
      activateEditMode(element);
    });

  const target = event.target;

  const editMode = target.parentElement.querySelector(
    ".album-all-catch-state-edit-action"
  );
  editMode.setAttribute("hidden", true);

  const readMode = target.parentElement.querySelector(
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

  const target = event.target;

  const editMode = target.parentElement.querySelector(
    ".album-all-catch-state-edit-action"
  );
  editMode.removeAttribute("hidden");

  const readMode = target.parentElement.querySelector(
    ".album-all-catch-state-read-action"
  );
  readMode.setAttribute("hidden", true);
}

function saveChange(target) {
  const pokemon = target.closest(".album-case").getAttribute("id");
  const catchState = target.value;

  const request = new Request("/" + locale + "/album/" + dex + "/" + pokemon, {
    method: "PATCH",
    body: catchState,
  });

  fetch(request)
    .then((response) => {
      if (response.status !== 200) {
        throw new Error("Something went wrong on api server!");
      }

      new bootstrap.Toast(
        document.getElementById("successToast-" + pokemon)
      ).show();
    })
    .catch((error) => {
      console.error(error);

      new bootstrap.Toast(
        document.getElementById("errorToast-" + pokemon)
      ).show();
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
