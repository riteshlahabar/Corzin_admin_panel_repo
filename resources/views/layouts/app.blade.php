<!DOCTYPE html>
<html lang="en" dir="ltr" data-startbar="light" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <title>@yield('title', 'Corzin Dairy Management System')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Premium Multipurpose Admin & Dashboard Template" name="description" />
    <meta content="" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <script>
        (function () {
            try {
                var storedTheme = localStorage.getItem('corzin_theme');
                if (storedTheme === 'dark' || storedTheme === 'light') {
                    document.documentElement.setAttribute('data-bs-theme', storedTheme);
                }
            } catch (e) {}
        })();
    </script>

    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">

    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .pagination {
            align-items: center;
            gap: 0.15rem;
            margin-bottom: 0;
        }
        .pagination svg {
            width: 0.9rem;
            height: 0.9rem;
        }
        .pagination .page-link {
            min-width: 2rem;
            text-align: center;
            line-height: 1.2;
        }
        .corzin-table-pagination,
        .corzin-server-pagination {
            border-top: 0 !important;
            padding-top: 0.75rem;
        }
        .corzin-table-pagination .page-link,
        .corzin-server-pagination .page-link {
            border-radius: 0.35rem;
        }
        .corzin-page-hidden {
            display: none !important;
        }
        .page-title {
            display: inline-block;
            margin-bottom: 0;
            padding: 0.5rem 0.9rem;
            border-radius: 0.85rem;
            background: #e8f7eb;
            color: #14532d;
            font-weight: 800;
            line-height: 1.2;
        }
    </style>
    @stack('styles')
</head>

<body>

    @include('layouts.header')

    @include('layouts.sidebar')

    <div class="page-wrapper">
        
        <div class="page-content">
            <div class="container-fluid">
                
                @yield('content')
                
            </div>
        </div>
        @include('layouts.footer')
        
    </div>
    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    <script src="{{ asset('js/table-pagination.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            try {
                var root = document.documentElement;
                var toggle = document.getElementById('light-dark-mode');
                var current = root.getAttribute('data-bs-theme');
                if (current === 'dark' || current === 'light') {
                    localStorage.setItem('corzin_theme', current);
                }
                if (toggle) {
                    toggle.addEventListener('click', function () {
                        setTimeout(function () {
                            var next = root.getAttribute('data-bs-theme') || 'light';
                            localStorage.setItem('corzin_theme', next);
                        }, 0);
                    });
                }
            } catch (e) {}

            try {
                var profileToggle = document.getElementById('profile-menu-toggle');
                if (profileToggle && window.bootstrap && window.bootstrap.Dropdown) {
                    var profileDropdown = window.bootstrap.Dropdown.getOrCreateInstance(profileToggle);
                    profileToggle.addEventListener('click', function (e) {
                        e.preventDefault();
                        profileDropdown.toggle();
                    });
                }
            } catch (e) {}
        });
    </script>
    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/12.12.0/firebase-app.js";
        import { getMessaging, getToken, onMessage } from "https://www.gstatic.com/firebasejs/12.12.0/firebase-messaging.js";

        const firebaseConfig = {
            apiKey: "AIzaSyDl71BcXPsQVeR1DSjoPzyfxIzBVdCJeb0",
            authDomain: "corzindairymanagementsystem.firebaseapp.com",
            projectId: "corzindairymanagementsystem",
            storageBucket: "corzindairymanagementsystem.firebasestorage.app",
            messagingSenderId: "152533202294",
            appId: "1:152533202294:web:f7717de2654ee5353a3657"
        };

        const vapidKey = "BFLLbKIdALmrxeJ7iBx9MMTbtR1qU4d-A3ysNZUu2Jrbo9WsdAwFr2Vl9fEZR5SWw8o4bYG1YtCQ-VGYIclDlMY";

        async function registerWebPush() {
            try {
                if (!('serviceWorker' in navigator) || !('Notification' in window)) {
                    return;
                }

                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    return;
                }

                const app = initializeApp(firebaseConfig);
                const messaging = getMessaging(app);
                const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js');

                const token = await getToken(messaging, {
                    vapidKey,
                    serviceWorkerRegistration: registration,
                });

                if (!token) {
                    return;
                }

                await fetch('/api/web-push/register-token', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        token: token,
                        device_name: 'Admin Web',
                        user_agent: navigator.userAgent || '',
                    }),
                });

                onMessage(messaging, (payload) => {
                    const title = payload?.notification?.title || 'Notification';
                    const body = payload?.notification?.body || 'You have a new update.';
                    try {
                        new Notification(title, { body });
                    } catch (e) {}
                });
            } catch (e) {
                console.warn('Web push init failed:', e);
            }
        }

        registerWebPush();
    </script>
    
    @stack('scripts')
    
</body>
</html>

