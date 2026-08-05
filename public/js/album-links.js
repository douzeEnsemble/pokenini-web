function watchAlbumLinks() {
  const section = document.getElementById("album-links-section");

  if (!section) {
    return;
  }

  const dexSlug = section.dataset.dexSlug;
  let selectedTargetDexSlug = null;

  document
    .getElementById("offcanvas")
    .addEventListener("shown.bs.offcanvas", function () {
      loadLinks(dexSlug);
    });

  function selectCard(card) {
    if (card.classList.contains("linked")) {
      return;
    }

    document.querySelectorAll(".dex-pick-card").forEach(function (c) {
      c.classList.remove("selected");
    });
    card.classList.add("selected");
    selectedTargetDexSlug = card.dataset.dexSlug;
    document.getElementById("create-link").disabled = false;
  }

  document.querySelectorAll(".dex-pick-card").forEach(function (card) {
    card.addEventListener("click", function () {
      selectCard(card);
    });
    card.addEventListener("keydown", function (event) {
      if (event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        selectCard(card);
      }
    });
  });

  document
    .getElementById("create-link")
    .addEventListener("click", function () {
      createLink(dexSlug, selectedTargetDexSlug);
    });
}

function createLink(dexSlug, selectedTargetDexSlug) {
  if (!selectedTargetDexSlug) {
    return;
  }

  const direction = document.querySelector(
    'input[name="link-direction"]:checked'
  ).value;

  const request = new Request("/" + locale + "/album_link/" + dexSlug, {
    method: "POST",
    body: JSON.stringify({
      targetDexSlug: selectedTargetDexSlug,
      bidirectional: direction === "both",
    }),
  });

  fetch(request)
    .then(function (response) {
      if (response.status !== 201) {
        throw new Error("Something went wrong on api server!");
      }

      loadLinks(dexSlug);
      new bootstrap.Toast(document.getElementById("linksToastSuccess")).show();
    })
    .catch(function (error) {
      console.error(error);
      new bootstrap.Toast(document.getElementById("linksToastError")).show();
    });
}

function loadLinks(dexSlug) {
  fetch("/" + locale + "/album_link/" + dexSlug)
    .then(function (response) {
      if (response.status !== 200) {
        throw new Error("Something went wrong on api server!");
      }

      return response.json();
    })
    .then(function (links) {
      renderPickerGrid(dexSlug, links);
    })
    .catch(function (error) {
      console.error(error);
      new bootstrap.Toast(document.getElementById("linksToastError")).show();
    });
}

function renderPickerGrid(dexSlug, links) {
  const badge = document.getElementById("album-links-count");
  badge.textContent = links.length;
  badge.hidden = links.length === 0;

  const linkedBySlug = {};
  links.forEach(function (link) {
    linkedBySlug[link.target_dex_slug] = link;
  });

  const directionIcons = {
    to: "bi-arrow-right",
    from: "bi-arrow-left",
    both: "bi-arrow-left-right",
  };
  const directionLabels = {
    to: linksLabels.directionTo,
    from: linksLabels.directionFrom,
    both: linksLabels.directionBoth,
  };

  document.querySelectorAll(".dex-pick-card").forEach(function (card) {
    card.classList.remove("selected");

    const previousUnlinkButton = card.querySelector(".unlink-btn");
    if (previousUnlinkButton) {
      previousUnlinkButton.remove();
    }

    const previousChip = card.querySelector(".chip-under");
    if (previousChip) {
      previousChip.remove();
    }

    const link = linkedBySlug[card.dataset.dexSlug];

    if (!link) {
      card.classList.remove("linked");
      delete card.dataset.linkId;

      return;
    }

    card.classList.add("linked");
    card.dataset.linkId = link.id;

    const unlinkButton = document.createElement("button");
    unlinkButton.type = "button";
    unlinkButton.className = "unlink-btn";
    unlinkButton.title = linksLabels.deleteTitle;
    unlinkButton.innerHTML = '<i class="bi bi-x-lg"></i>';
    unlinkButton.addEventListener("click", function (event) {
      event.stopPropagation();
      deleteLink(dexSlug, link.id);
    });
    card.appendChild(unlinkButton);

    const chip = document.createElement("span");
    chip.className = "chip-under";
    chip.innerHTML =
      '<i class="bi ' +
      directionIcons[link.direction] +
      '"></i> ' +
      directionLabels[link.direction];
    card.querySelector("small").appendChild(chip);
  });
}

function deleteLink(dexSlug, linkId) {
  const request = new Request("/" + locale + "/album_link/" + linkId, {
    method: "DELETE",
  });

  fetch(request)
    .then(function (response) {
      if (response.status !== 200) {
        throw new Error("Something went wrong on api server!");
      }

      loadLinks(dexSlug);
      new bootstrap.Toast(document.getElementById("linksToastSuccess")).show();
    })
    .catch(function (error) {
      console.error(error);
      new bootstrap.Toast(document.getElementById("linksToastError")).show();
    });
}
