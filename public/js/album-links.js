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
    if (card.classList.contains("dex-pick-card-current")) {
      return;
    }

    card.addEventListener("click", function (event) {
      if (event.target.closest(".dex-pick-view")) {
        return;
      }
      selectCard(card);
    });
    card.addEventListener("keydown", function (event) {
      if (event.target.closest(".dex-pick-view")) {
        return;
      }
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

  watchDexPickerFilters();
}

function watchDexPickerFilters() {
  const filters = document.querySelectorAll('[id^="dex-picker-filter-"]');

  filters.forEach(function (filter) {
    filter.addEventListener("change", applyDexPickerFilters);
  });
}

function applyDexPickerFilters() {
  const attributeFilters = [
    { filterId: "dex-picker-filter-privacy", dataKey: "filterPrivacy" },
    { filterId: "dex-picker-filter-homepaged", dataKey: "filterHomepaged" },
    { filterId: "dex-picker-filter-released", dataKey: "filterReleased" },
    { filterId: "dex-picker-filter-shiny", dataKey: "filterShiny" },
    { filterId: "dex-picker-filter-premium", dataKey: "filterPremium" },
  ];
  const linkedFilter = document.getElementById("dex-picker-filter-linked");

  document.querySelectorAll(".dex-pick-card").forEach(function (card) {
    let visible = true;

    attributeFilters.forEach(function (entry) {
      const select = document.getElementById(entry.filterId);
      if (visible && select && select.value && card.dataset[entry.dataKey] !== select.value) {
        visible = false;
      }
    });

    if (visible && linkedFilter && linkedFilter.value) {
      const isLinked = card.classList.contains("linked") ? "1" : "0";
      if (isLinked !== linkedFilter.value) {
        visible = false;
      }
    }

    card.classList.toggle("dex-pick-hidden", !visible);
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

    if (card.classList.contains("dex-pick-card-current")) {
      // The current dex's own card is never selectable and never carries a
      // link (linking a dex to itself makes no sense) — leave it untouched.
      return;
    }

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

  applyDexPickerFilters();
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
