<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>audix · Higiene</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { azul: '#4063FF', grafite: '#151922', offwhite: '#F5F7FA' } } } };
    </script>
    @vite(['resources/js/app.js'])
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
