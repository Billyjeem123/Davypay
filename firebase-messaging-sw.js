importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: "AIzaSyAgg5CYsRqY_AufmcoztAeFdxs4qG5XcRs",
    authDomain: "billia-app.firebaseapp.com",
    projectId: "billia-app",
    storageBucket: "billia-app.firebasestorage.app",
    messagingSenderId: "561668990127",
    appId: "1:561668990127:web:184a302774c4ecd322e05a",
    measurementId: "G-ZWW604JGX4"
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
    console.log('[firebase-messaging-sw.js] Received background message ', payload);
    const { title, body, icon } = payload.notification;

    self.registration.showNotification(title, {
        body: body,
        icon: icon || '/favicon.ico'
    });
});
