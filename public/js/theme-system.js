/**
 * Global Theme System
 * Handles Light/Dark mode toggling and persistence
 */

const ThemeSystem = {
    init: () => {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'light') {
            document.body.classList.add('light-theme');
        }
        ThemeSystem.updateIcon();
    },

    toggle: () => {
        const body = document.body;
        body.classList.toggle('light-theme');

        const isLight = body.classList.contains('light-theme');
        localStorage.setItem('theme', isLight ? 'light' : 'dark');

        ThemeSystem.updateIcon();
    },

    updateIcon: () => {
        const icon = document.getElementById('themeIcon');
        if (!icon) return;

        const isLight = document.body.classList.contains('light-theme');
        if (isLight) {
            // Moon Icon (Switch to Dark)
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>';
        } else {
            // Sun Icon (Switch to Light)
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>';
        }
    }
};

// Initialize on load
window.addEventListener('domincontentloaded', () => {
    // Wait? No, 'load' is safer if defer, but DOMContentLoaded is faster.
    // However, fast application is better to prevent flash.
    ThemeSystem.init();
});

// Also run immediately in case body is ready (prevent flash)
if (document.body) {
    ThemeSystem.init();
} else {
    // Wait for body
    new MutationObserver((mutations, obs) => {
        if (document.body) {
            ThemeSystem.init();
            obs.disconnect();
        }
    }).observe(document.documentElement, { childList: true });
}

// Expose global toggle function for buttons
window.toggleTheme = ThemeSystem.toggle;
