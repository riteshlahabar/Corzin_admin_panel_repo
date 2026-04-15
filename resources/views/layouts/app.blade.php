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
        });
    </script>
    
    @stack('scripts')
    
</body>
</html>

