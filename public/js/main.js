function watchCookieManager() {
  document.querySelectorAll('.cookie-manager button').forEach(function (element) {
    element.addEventListener('click', onCliceCookieManager);
  });
}

function onCliceCookieManager() {
  tarteaucitron.userInterface.openPanel();
}

function watchDexCategoryLinks() {
  document.querySelectorAll('.dex-category-link').forEach(function (element) {
    element.addEventListener('click', onClickDexCategoryLink);
  });
}

function onClickDexCategoryLink(event) {
  const list = document.querySelector(event.currentTarget.dataset.dexCategoryList);
  if (list) {
    bootstrap.Collapse.getOrCreateInstance(list, { toggle: false }).show();
  }
}

// --- Readable text on API-provided colors ---------------------------------
// Catch-state and Pokémon-type colors come from the API as CSS custom
// properties (--catch-state-*, --pokemon-type-*) emitted in inline <style>
// blocks. For each one, pick black or white text from the color's WCAG
// relative luminance and inject the matching rules.

function relativeLuminance(r, g, b) {
  const lin = [r, g, b].map(function (v) {
    const c = v / 255;
    return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
  });
  return 0.2126 * lin[0] + 0.7152 * lin[1] + 0.0722 * lin[2];
}

function parseCssColor(value) {
  // Resolve any CSS color syntax through a canvas, which normalizes to hex/rgba.
  const ctx = document.createElement('canvas').getContext('2d');
  if (!ctx) {
    return null;
  }
  const candidate = value.trim();
  // An invalid value would leave fillStyle at its '#000' default and be read
  // back as black, so reject it up front.
  if (!candidate || !window.CSS || !CSS.supports('color', candidate)) {
    return null;
  }
  ctx.fillStyle = '#000';
  ctx.fillStyle = candidate;
  const normalized = ctx.fillStyle;
  let match = /^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i.exec(normalized);
  if (match) {
    return [parseInt(match[1], 16), parseInt(match[2], 16), parseInt(match[3], 16)];
  }
  match = /^rgba?\((\d+),\s*(\d+),\s*(\d+)/.exec(normalized);
  return match ? [Number(match[1]), Number(match[2]), Number(match[3])] : null;
}

function readableTextColor(value) {
  const rgb = parseCssColor(value);
  if (!rgb) {
    return null;
  }
  // Black wins over white when the background is lighter than ~0.179.
  return relativeLuminance(rgb[0], rgb[1], rgb[2]) > 0.179 ? '#000' : '#fff';
}

function collectRootCustomProperties(prefix) {
  const found = {};
  Array.prototype.forEach.call(document.styleSheets, function (sheet) {
    let rules;
    try {
      rules = sheet.cssRules;
    } catch (e) {
      return; // cross-origin stylesheet
    }
    Array.prototype.forEach.call(rules, function (rule) {
      if (rule.selectorText !== ':root' || !rule.style) {
        return;
      }
      for (let i = 0; i < rule.style.length; i++) {
        const name = rule.style[i];
        if (name.indexOf(prefix) === 0) {
          found[name.slice(prefix.length)] = rule.style.getPropertyValue(name);
        }
      }
    });
  });
  return found;
}

function applyContrastColors() {
  const css = [];

  const catchStates = collectRootCustomProperties('--catch-state-');
  Object.keys(catchStates).forEach(function (slug) {
    const color = readableTextColor(catchStates[slug]);
    if (!color) {
      return;
    }
    css.push(
      '.album-case.catch-state-' + slug + ' .album-case-catch-state,' +
      '.album-case.catch-state-' + slug + ' .album-case-action,' +
      'tr.catch-state-' + slug + ' td,tr.catch-state-' + slug + ' th' +
      '{color:' + color + '}'
    );
  });

  const types = collectRootCustomProperties('--pokemon-type-');
  Object.keys(types).forEach(function (slug) {
    const color = readableTextColor(types[slug]);
    if (!color) {
      return;
    }
    css.push(
      '.album-modal-types.pokemon-type-' + slug + ',' +
      '.election-card-types.pokemon-type-' + slug + ',' +
      '.filters-types.pokemon-type-' + slug +
      '{color:' + color + '}'
    );
  });

  if (css.length > 0) {
    const style = document.createElement('style');
    style.textContent = css.join('\n');
    document.head.appendChild(style);
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', applyContrastColors);
} else {
  applyContrastColors();
}
