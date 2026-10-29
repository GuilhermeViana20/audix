@php($tipo = $tipo ?? 'higiene')
@php($nomeTipo = $tipo === 'temperatura' ? 'Planilha de Temperatura' : 'Planilha de Higiene')
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
    <title>{{ $tipo === 'temperatura' ? 'Planilha Temperatura' : 'Planilha Limpeza' }} - {{ $supermercado?->nome }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/toast.js'])
</head>
<body class="audix-shell xl:flex xl:h-screen xl:flex-col xl:overflow-hidden {{ $tipo === 'temperatura' ? 'tela-temperatura' : '' }} bg-offwhite text-grafite">
    <!-- Topo fixo: menu, navegação e títulos num fundo branco único; o restante rola à parte -->
    <div class="planilha-topo shrink-0 border-b border-grafite/10 bg-white">
        @include('partials.cabecalho', ['largura' => 'max-w-none'])
        <div class="planilha-breadcrumb px-4 pt-6 md:px-8">
            @include('partials.breadcrumb', ['itens' => [
                ['nome' => 'Início', 'url' => route('inicio')],
                ['nome' => $nomeTipo, 'url' => route('planilhas.supermercados', $tipo)],
                ['nome' => $supermercado?->nome ?? 'Supermercado'],
            ]])
        </div>
        <div class="grid gap-6 px-4 pb-5 pt-4 md:px-8 xl:grid-cols-[2fr_3fr]">
            <div id="topo-supermercado"></div>
            <div id="topo-previa"></div>
        </div>
    </div>
    <div id="app" class="px-4 pb-8 pt-6 md:px-8 xl:flex xl:min-h-0 xl:flex-1 xl:flex-col xl:overflow-hidden xl:pb-6"></div>
    <script>
        window.AUDIX = {
            supermercadoId: {{ $supermercado?->id ?? 'null' }},
            supermercados: @json($supermercados),
            embutido: true,
            nomeSupermercado: @json($supermercado?->nome),
            tipo: @json($tipo),
            nomeArquivo: @json(($tipo === 'temperatura' ? 'Planilha Temperatura - ' : 'Planilha Limpeza - ') . ($supermercado?->nome)),
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
