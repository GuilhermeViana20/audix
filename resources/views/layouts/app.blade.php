<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen flex-col bg-offwhite text-grafite">
@include('partials.cabecalho')

<main class="mx-auto w-full max-w-5xl flex-1 px-4 py-6 md:px-8 md:py-10">
    @yield('breadcrumb')
    @yield('conteudo')
</main>

<footer class="mt-12 border-t border-grafite/10 bg-white/95">
    <div class="mx-auto flex max-w-5xl flex-col gap-6 px-4 py-8 text-sm md:flex-row md:items-center md:justify-between md:px-8">
        <div>
            <img src="/images/audix-logo.png" alt="audix" class="logo-tema h-8 w-auto">
            <p class="mt-2 text-grafite/60">Inteligência em cada controle.</p>
        </div>
        <div class="text-grafite/60 md:text-right">
            <p class="font-semibold text-grafite/80">ACCP Alimentos &amp; Tecnologia</p>
            <p>contato@accpconsultoria.com.br</p>
            <p class="mt-2 text-xs">© {{ date('Y') }} · Todos os direitos reservados</p>
        </div>
    </div>
</footer>

<script>
    function alternarTema() {
        var escuro = document.documentElement.classList.toggle('dark');
        try { localStorage.setItem('audix-tema', escuro ? 'escuro' : 'claro'); } catch (e) {}
    }
</script>
</body>
</html>
