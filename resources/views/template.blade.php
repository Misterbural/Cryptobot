<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>@yield('title')</title>
</head>
<body>
    @yield('content')
    <footer>
        Cryptobot &copy; Copyright 2018 - PLEB WEB SAS
    </footer>
    <script type="text/javascript" src="//www.amcharts.com/lib/3/amcharts.js"></script>
    <script type="text/javascript" src="//www.amcharts.com/lib/3/serial.js"></script>
    <script type="text/javascript" src="//www.amcharts.com/lib/3/amstock.js"></script>
    <script type="text/javascript" src="//www.amcharts.com/lib/3/themes/light.js"></script>
    <script type="text/javascript" src="//www.amcharts.com/lib/3/plugins/dataloader/dataloader.min.js"></script>
    @yield('scripts')
</body>
</html>
