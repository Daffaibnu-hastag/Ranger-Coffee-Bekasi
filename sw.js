// sw.js
self.addEventListener('push', function(event) {
    const data = event.data ? event.data.json() : {};
    
    const title = data.title || "Pesanan Selesai! 🎉";
    const options = {
        body: data.body || "Kopi nikmat kamu sudah siap diambil di meja bar!",
        icon: "assets/img/logo.png",
        badge: "assets/img/logo.png",
        vibrate: [200, 100, 200],
        data: {
            url: data.url || "index.php"
        }
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    event.waitUntil(
        clients.openWindow(event.notification.data.url)
    );
});