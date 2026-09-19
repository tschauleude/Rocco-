/**
 * Umschalter zwischen hellem und dunklem Design.
 * Ohne Klick entscheidet das Betriebssystem – die Wahl überlebt den Seitenwechsel.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'marian-theme';

    function readStoredTheme() {
        try {
            return window.localStorage.getItem(STORAGE_KEY);
        } catch (error) {
            // Privater Modus oder blockierte Speicherung: dann eben ohne Gedächtnis.
            return null;
        }
    }

    function storeTheme(theme) {
        try {
            window.localStorage.setItem(STORAGE_KEY, theme);
        } catch (error) {
            // Nicht schlimm – die Umschaltung gilt dann nur für diese Seite.
        }
    }

    function apply(theme) {
        if (theme) {
            document.documentElement.setAttribute('data-theme', theme);
        } else {
            document.documentElement.removeAttribute('data-theme');
        }
    }

    function currentTheme() {
        var stored = readStoredTheme();
        if (stored) {
            return stored;
        }

        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    apply(readStoredTheme());

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-theme-toggle]');
        if (!button) {
            return;
        }

        var next = currentTheme() === 'dark' ? 'light' : 'dark';
        apply(next);
        storeTheme(next);

        // Diagramme lesen ihre Farben aus CSS-Variablen und müssen neu gezeichnet werden.
        window.dispatchEvent(new Event('resize'));
    });
}());
