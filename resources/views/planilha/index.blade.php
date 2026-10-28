<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Planilha Limpeza - {{ $supermercado?->nome }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-offwhite text-grafite">
    <div class="px-4 pt-4 md:px-8">
        <a href="{{ route('planilhas.supermercados', 'higiene') }}" class="text-sm font-medium text-azul">← Trocar supermercado</a>
    </div>
    <div id="app" class="p-4 md:p-8"></div>
    <script>
        window.AUDIX = {
            supermercadoId: {{ $supermercado?->id ?? 'null' }},
            supermercados: @json($supermercados),
            embutido: true,
            nomeSupermercado: @json($supermercado?->nome),
            nomeArquivo: @json('Planilha Limpeza - ' . ($supermercado?->nome)),
        };
    </script>
</body>
</html>
