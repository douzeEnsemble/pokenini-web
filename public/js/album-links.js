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

  document.querySelectorAll(".dex-pick-card").forEach(function (card) {
    card.addEventListener("click", function () {
      document.querySelectorAll(".dex-pick-card").forEach(function (c) {
        c.classList.remove("selected");
      });
      card.classList.add("selected");
      selectedTargetDexSlug = card.dataset.dexSlug;
      document.getElementById("create-link").disabled = false;
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
      renderLinks(dexSlug, links);
      filterPickerGrid(links);
    })
    .catch(function (error) {
      console.error(error);
      new bootstrap.Toast(document.getElementById("linksToastError")).show();
    });
}

function renderLinks(dexSlug, links) {
  const container = document.getElementById("active-links");
  container.innerHTML = "";

  const badge = document.getElementById("album-links-count");
  badge.textContent = links.length;
  badge.hidden = links.length === 0;

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

  links.forEach(function (link) {
    container.appendChild(
      buildLinkRow(dexSlug, link, directionIcons, directionLabels)
    );
  });
}

function buildLinkRow(dexSlug, link, directionIcons, directionLabels) {
  const row = document.createElement("div");
  row.className = "list-group-item d-flex align-items-center gap-2 dex-link-row";

  const img = document.createElement("img");
  img.src = dexBannerUrlTemplate.replace("%s", link.target_dex_slug);
  img.alt = "";
  row.appendChild(img);

  const info = document.createElement("div");
  info.className = "flex-fill";

  const name = document.createElement("div");
  name.className = "fw-bold small";
  name.textContent = locale === "fr" ? link.target_french_name : link.target_name;
  info.appendChild(name);

  const direction = document.createElement("div");
  direction.className = "form-text";
  direction.innerHTML = '<i class="bi ' + directionIcons[link.direction] + '"></i> ';
  direction.append(directionLabels[link.direction]);
  info.appendChild(direction);

  row.appendChild(info);

  const deleteButton = document.createElement("button");
  deleteButton.type = "button";
  deleteButton.className = "btn btn-sm btn-outline-danger";
  deleteButton.title = linksLabels.deleteTitle;
  deleteButton.innerHTML = '<i class="bi bi-trash"></i>';
  deleteButton.addEventListener("click", function () {
    deleteLink(dexSlug, link.id);
  });
  row.appendChild(deleteButton);

  return row;
}

function filterPickerGrid(links) {
  const linkedSlugs = links.map(function (link) {
    return link.target_dex_slug;
  });

  document.querySelectorAll(".dex-pick-card").forEach(function (card) {
    card.hidden = linkedSlugs.indexOf(card.dataset.dexSlug) !== -1;
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
