importScripts('https://www.gstatic.com/firebasejs/12.12.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/12.12.0/firebase-messaging-compat.js');

firebase.initializeApp({
  apiKey: "AIzaSyDl71BcXPsQVeR1DSjoPzyfxIzBVdCJeb0",
  authDomain: "corzindairymanagementsystem.firebaseapp.com",
  projectId: "corzindairymanagementsystem",
  storageBucket: "corzindairymanagementsystem.firebasestorage.app",
  messagingSenderId: "152533202294",
  appId: "1:152533202294:web:f7717de2654ee5353a3657"
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
  const title = payload?.notification?.title || 'Notification';
  const options = {
    body: payload?.notification?.body || 'You have a new update.',
    icon: '/assets/images/favicon.ico',
  };
  self.registration.showNotification(title, options);
});

