<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="/images/audix-favicon.png">
    <title>audix · @yield('titulo')</title>
    <script>
        // Tema salvo (ou preferência do sistema) aplicado antes da página aparecer
        (function () {
            var tema = null;
            try { tema = localStorage.getItem('audix-tema'); } catch (e) {}
            if (!tema) tema = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'escuro' : 'claro';
            document.documentElement.classList.toggle('dark', tema === 'escuro');
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/carregando.js', 'resources/js/toast.js'])
</head>
<body class="audix-shell flex min-h-screen flex-col bg-offwhite text-grafite">
@include('partials.cabecalho')

<main class="audix-main mx-auto w-full flex-1 px-4 py-6 md:px-8 md:py-10">
    @yield('breadcrumb')
    @yield('conteudo')
</main>

<footer class="audix-footer mt-12 border-t border-grafite/10 bg-white/95">
    <div class="flex flex-col gap-3 px-4 py-4 text-xs md:flex-row md:items-center md:justify-between md:gap-6 md:px-8">
        <div class="flex items-center gap-3">
            <img src="/images/audix-logo.png" alt="audix" class="logo-tema h-6 w-auto">
            <span class="text-grafite/60">Inteligência em cada controle.</span>
        </div>
        <p class="text-grafite/60">
            <a href="https://guilherme-devfullstack.com/" target="_blank" rel="noopener" class="font-semibold text-azul hover:underline">Guilherme DevFullStack</a>
            <span class="mx-1">·</span> © {{ date('Y') }} · Todos os direitos reservados
        </p>
        <p class="text-grafite/60">ACCP Alimentos &amp; Tecnologia <span class="mx-1">|</span> contato@accpconsultoria.com.br</p>
    </div>
</footer>

<script>
    function alternarTema() {
        var escuro = document.documentElement.classList.toggle('dark');
        try { localStorage.setItem('audix-tema', escuro ? 'escuro' : 'claro'); } catch (e) {}
    }
</script>
    @if (session('ok') || $errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                @if (session('ok'))
                    window.toast(@json(session('ok')), 'sucesso');
                @endif
                @if ($errors->any())
                    window.toast(@json($errors->first()), 'erro');
                @endif
            });
        </script>
    @endif
</body>
</html>
