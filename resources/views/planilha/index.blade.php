<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>audix · Higiene</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-offwhite text-grafite">
    <div id="app"></div>
    <script>
        window.AUDIX = {
            supermercadoId: {{ $supermercado?->id ?? 'null' }},
            supermercados: @json($supermercados),
        };
    </script>
</body>
</html>
