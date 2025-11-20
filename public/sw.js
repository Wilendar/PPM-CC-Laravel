// Empty Service Worker - prevents 404 errors
// This file exists only to prevent console errors from old/cached SW registration attempts

self.addEventListener('install', (event) => {
    // Skip waiting to activate immediately
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    // Claim clients immediately
    event.waitUntil(self.clients.claim());

    // Unregister this service worker (cleanup)
    self.registration.unregister().then(() => {
        console.log('Service Worker unregistered successfully');
    });
});

// No fetch handler - this SW does nothing
