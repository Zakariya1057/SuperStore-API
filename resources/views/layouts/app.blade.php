<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title }}</title>

    <meta charset="UTF-8">
    <meta name="description" content="{{ $description }}">
    <meta name="keywords" content="{{ $keywords }}">
    <meta name="author" content="SuperStore">

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Spartan:wght@500&display=swap" rel="stylesheet">

</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md shadow-sm fixed-top">
            <div class="container">

                <a class="navbar-brand text-light company-name" href="{{ url('/') }}">
                    {{ config('app.name', 'SuperStore') }}
                </a>

                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link btn-light btn text-dark font-weight-bold px-3" href="https://apps.apple.com/za/app/superstore-groceries-offers/id1537442192">Download</a>
                    </li>
                </ul>

            </div>
        </nav>

        <main>
            @yield('content')
        </main>
    </div>

    <!-- Footer -->
    <footer class="page-footer font-small blue">
        <!-- Copyright -->
        <div class="footer-copyright text-center py-3">© 2021 Copyright: <span class="text-primaryt mr-4"> SuperStoreSite.com<span> · <a href="#">Privacy</a> · <a href="#">Terms & Conditions<a/> </div>
        <!-- Copyright -->
    </footer>
    <!-- Footer -->

</body>
</html>
