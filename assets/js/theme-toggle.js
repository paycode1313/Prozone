/**
 * ============================================
 * PROZONE THEME TOGGLE
 * File: assets/js/theme-toggle.js
 * Deskripsi: Mengatur light/dark mode dengan:
 *   - localStorage persistence
 *   - System preference detection
 *   - Optional DB sync (jika user login)
 *   - Prevent flash of wrong theme (FOUC)
 * ============================================
 */

(function () {
  'use strict';

  const STORAGE_KEY = 'prozone-theme';
  const THEMES = ['light', 'dark'];

  /**
   * Dapatkan tema yang harus dipakai.
   * Prioritas: localStorage > system preference > server-side (body class) > 'light'
   */
  function getInitialTheme() {
    // 1. Check localStorage (user explicit choice)
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored && THEMES.includes(stored)) {
      return stored;
    }

    // 2. Check system preference
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
      return 'dark';
    }

    // 3. Check class yang sudah di-set server-side (dari $_SESSION['theme'])
    if (document.body.classList.contains('dark-mode')) {
      return 'dark';
    }
    if (document.body.classList.contains('light-mode')) {
      return 'light';
    }

    // 4. Default
    return 'light';
  }

  /**
   * Terapkan tema ke document.
   * Pakai data-theme di <html> agar bisa di-style oleh CSS.
   */
  function applyTheme(theme) {
    const html = document.documentElement;
    const body = document.body;

    if (theme === 'dark') {
      html.classList.add('dark-mode');
      html.classList.remove('light-mode');
      body.classList.add('dark-mode');
      body.classList.remove('light-mode');
      html.setAttribute('data-theme', 'dark');
    } else {
      html.classList.add('light-mode');
      html.classList.remove('dark-mode');
      body.classList.add('light-mode');
      body.classList.remove('dark-mode');
      html.setAttribute('data-theme', 'light');
    }

    // Dispatch event untuk komponen yang butuh respond ke perubahan tema
    window.dispatchEvent(new CustomEvent('themechange', { detail: { theme } }));
  }

  /**
   * Simpan preferensi ke localStorage.
   * Jika user login, juga sync ke server.
   */
  function persistTheme(theme) {
    try {
      localStorage.setItem(STORAGE_KEY, theme);
    } catch (e) {
      // localStorage mungkin tidak tersedia (private mode, dll)
      console.warn('localStorage tidak tersedia:', e);
    }

    // Sync ke server jika endpoint tersedia.
    // Pakai navigator.sendBeacon agar tidak blocking navigasi.
    if (navigator.sendBeacon && window.PROZONE_USER_LOGGED_IN) {
      try {
        const formData = new FormData();
        formData.append('theme', theme);
        navigator.sendBeacon('api/set-theme.php', formData);
      } catch (e) {
        // Silent fail - tema akan sync next page load
      }
    }
  }

  /**
   * Toggle tema.
   */
  function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    applyTheme(next);
    persistTheme(next);
    updateToggleButton(next);
  }

  /**
   * Update icon/aria pada toggle button.
   * Cari button dengan data-theme-toggle atau .theme-toggle-btn.
   */
  function updateToggleButton(theme) {
    const buttons = document.querySelectorAll('[data-theme-toggle], .theme-toggle-btn');
    buttons.forEach((btn) => {
      const sun = btn.querySelector('[data-theme-icon="sun"]');
      const moon = btn.querySelector('[data-theme-icon="moon"]');
      if (sun && moon) {
        sun.style.display = theme === 'dark' ? 'block' : 'none';
        moon.style.display = theme === 'light' ? 'block' : 'none';
      }
      btn.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
      btn.setAttribute(
        'aria-label',
        theme === 'dark' ? 'Beralih ke mode terang' : 'Beralih ke mode gelap'
      );
      btn.setAttribute('title', theme === 'dark' ? 'Mode Terang' : 'Mode Gelap');
    });
  }

  /**
   * Listen ke system preference change (hanya jika user belum set explicit choice).
   */
  function watchSystemPreference() {
    if (!window.matchMedia) return;
    const mq = window.matchMedia('(prefers-color-scheme: dark)');
    const handler = (e) => {
      // Hanya respond jika user belum set explicit choice
      if (!localStorage.getItem(STORAGE_KEY)) {
        const theme = e.matches ? 'dark' : 'light';
        applyTheme(theme);
        updateToggleButton(theme);
      }
    };
    if (mq.addEventListener) {
      mq.addEventListener('change', handler);
    } else if (mq.addListener) {
      mq.addListener(handler); // Older Safari
    }
  }

  /**
   * Bind click handlers ke toggle button.
   */
  function bindToggleButtons() {
    const buttons = document.querySelectorAll('[data-theme-toggle], .theme-toggle-btn');
    buttons.forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        toggleTheme();
      });
    });
  }

  /**
   * Inisialisasi.
   * Dipanggil saat DOM ready.
   */
  function init() {
    const theme = getInitialTheme();
    applyTheme(theme);
    updateToggleButton(theme);
    bindToggleButtons();
    watchSystemPreference();

    // Re-bind saat ada button baru di-added (misal dari AJAX)
    // Pakai MutationObserver untuk catch dynamic content
    if (window.MutationObserver) {
      const observer = new MutationObserver(() => {
        bindToggleButtons();
        updateToggleButton(
          document.documentElement.getAttribute('data-theme') || 'light'
        );
      });
      observer.observe(document.body, { childList: true, subtree: true });
    }
  }

  // Expose public API
  window.ProzoneTheme = {
    get: () => document.documentElement.getAttribute('data-theme') || 'light',
    set: (theme) => {
      if (!THEMES.includes(theme)) return;
      applyTheme(theme);
      persistTheme(theme);
      updateToggleButton(theme);
    },
    toggle: toggleTheme,
  };

  // Run saat DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
