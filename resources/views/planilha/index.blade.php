<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="/images/audix-favicon.png">
    <script>
        (function () {
            var tema = null;
            try { tema = localStorage.getItem('audix-tema'); } catch (e) {}
            if (!tema) tema = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'escuro' : 'claro';
            document.documentElement.classList.toggle('dark', tema === 'escuro');
        })();
    </script>
    <title>Planilha Limpeza - {{ $supermercado?->nome }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/toast.js'])
</head>
<body class="bg-offwhite text-grafite">
    @include('partials.cabecalho', ['largura' => 'max-w-none'])
    <div class="px-4 pt-6 md:px-8">
        @include('partials.breadcrumb', ['itens' => [
            ['nome' => 'Início', 'url' => route('inicio')],
            ['nome' => 'Planilha de Higiene', 'url' => route('planilhas.supermercados', 'higiene')],
            ['nome' => $supermercado?->nome ?? 'Supermercado'],
        ]])
    </div>
    <div id="app" class="px-4 pb-8 md:px-8"></div>
    <script>
        window.AUDIX = {
            supermercadoId: {{ $supermercado?->id ?? 'null' }},
            supermercados: @json($supermercados),
            embutido: true,
            nomeSupermercado: @json($supermercado?->nome),
            nomeArquivo: @json('Planilha Limpeza - ' . ($supermercado?->nome)),
        };
    </script>
    <script>
        function alternarTema() {
            var escuro = document.documentElement.classList.toggle('dark');
            try { localStorage.setItem('audix-tema', escuro ? 'escuro' : 'claro'); } catch (e) {}
        }
    </script>
</body>
</html>
