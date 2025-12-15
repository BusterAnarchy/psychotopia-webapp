const STORAGE_KEY = 'psychotopia-filter-query';
const FILTER_KEYS = ['date_debut', 'date_fin', 'range', 'no_cut', 'familles', 'formes'];

const sanitizeSearch = (search = '') => {
  const raw = typeof search === 'string' ? search : '';
  const normalized = raw.startsWith('?') ? raw.slice(1) : raw;
  const source = new URLSearchParams(normalized);
  const filtered = new URLSearchParams();

  FILTER_KEYS.forEach((key) => {
    const values = source.getAll(key);
    if (!values.length) {
      return;
    }
    values.forEach((value) => {
      if (value !== null && value !== '') {
        filtered.append(key, value);
      }
    });
  });

  return filtered.toString();
};

const readStoredSearch = () => {
  try {
    return window.localStorage.getItem(STORAGE_KEY) || '';
  } catch (error) {
    return '';
  }
};

const persistSearch = (search) => {
  const sanitized = sanitizeSearch(search);
  try {
    if (sanitized) {
      window.localStorage.setItem(STORAGE_KEY, sanitized);
    } else {
      window.localStorage.removeItem(STORAGE_KEY);
    }
  } catch (error) {
    // ignore persistence errors
  }
  return sanitized;
};

const appendSearchToLinks = (search) => {
  const sanitized = sanitizeSearch(search);
  if (!sanitized) {
    return;
  }
  const preservedParams = new URLSearchParams(sanitized);

  document.querySelectorAll('a[href]:not([data-ignore-filter-persistence])').forEach((anchor) => {
    const href = anchor.getAttribute('href');
    if (!href || href.startsWith('#')) {
      return;
    }
    const lowerHref = href.toLowerCase();
    if (lowerHref.startsWith('mailto:') || lowerHref.startsWith('tel:') || lowerHref.startsWith('javascript:')) {
      return;
    }

    let url;
    try {
      url = new URL(href, window.location.origin);
    } catch (error) {
      return;
    }
    if (url.origin !== window.location.origin) {
      return;
    }

    const targetParams = new URLSearchParams(url.search);
    let changed = false;
    preservedParams.forEach((value, key) => {
      if (!targetParams.has(key)) {
        targetParams.set(key, value);
        changed = true;
      }
    });

    if (!changed) {
      return;
    }

    const queryString = targetParams.toString();
    const relativeUrl = `${url.pathname}${queryString ? `?${queryString}` : ''}${url.hash}`;
    anchor.setAttribute('href', relativeUrl);
  });
};

export const initFilterPersistence = () => {
  const currentSearch = sanitizeSearch(window.location.search);
  const storedSearch = currentSearch ? persistSearch(currentSearch) : readStoredSearch();

  if (!window.location.search && storedSearch) {
    const nextUrl = `${window.location.pathname}?${storedSearch}${window.location.hash}`;
    window.location.replace(nextUrl);
    return;
  }

  const propagateSearch = currentSearch || storedSearch;
  const applyToLinks = () => appendSearchToLinks(propagateSearch);
  if (propagateSearch) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', applyToLinks, { once: true });
    } else {
      applyToLinks();
    }
  }

  window.addEventListener('filters:applied', (event) => {
    const detail = event.detail || {};
    const newSearch = sanitizeSearch(detail.search || '');
    persistSearch(newSearch);
  });

  window.addEventListener('filters:cleared', () => {
    persistSearch('');
  });
};
