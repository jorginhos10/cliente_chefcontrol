// sw.js — Service worker mínimo, solo para cumplir el requisito de instalabilidad
// (Add to Home Screen). No cachea nada todavía: deja pasar todas las peticiones
// directo a la red.

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', () => {
    // Sin intercepción real: requerido para que el navegador considere la PWA instalable.
});
