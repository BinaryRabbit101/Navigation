/**
 * Registers the PWA service worker (public/sw.js).
 *
 * Registration is best-effort: an insecure context, a browser without service
 * workers, or a failed fetch must never surface as an error in the page.
 */
export function registerServiceWorker(): void {
    if (typeof window === 'undefined' || !('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {
            // Intentionally silent — the app works fine without it.
        });
    });
}
