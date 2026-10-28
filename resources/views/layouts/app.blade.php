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
<header class="sticky top-0 z-30 border-b border-grafite/10 border-t-4 border-t-azul bg-white/95 shadow-sm backdrop-blur">
    <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3 md:px-8">
        <a href="{{ route('inicio') }}" class="flex items-center">
            <img src="/images/audix-logo.png" alt="audix – Inteligência em cada controle" class="logo-tema h-10 w-auto md:h-12">
        </a>
        <div class="flex items-center gap-2 text-sm font-medium">
            @if (request()->routeIs('inicio'))
                <span aria-current="page" class="rounded-lg bg-azul/10 px-3 py-1.5 font-semibold text-azul">Planilhas</span>
            @else
                <a href="{{ route('inicio') }}" class="rounded-lg px-3 py-1.5 text-grafite/70 transition hover:bg-grafite/5 hover:text-azul">Planilhas</a>
            @endif
            @if (request()->routeIs('supermercados.*'))
                <span aria-current="page" class="rounded-lg bg-azul/10 px-3 py-1.5 font-semibold text-azul">Supermercados</span>
            @else
                <a href="{{ route('supermercados.index') }}" class="rounded-lg px-3 py-1.5 text-grafite/70 transition hover:bg-grafite/5 hover:text-azul">Supermercados</a>
            @endif
            </nav>
            <button type="button" onclick="alternarTema()" aria-label="Alternar modo escuro/claro" title="Alternar modo escuro/claro"
                    class="ml-1 flex h-9 w-9 items-center justify-center rounded-lg text-grafite/70 transition hover:bg-grafite/5 hover:text-azul">
                <svg class="h-5 w-5 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
                <svg class="hidden h-5 w-5 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
            </button>
        </div>
    </div>
</header>

<main class="mx-auto w-full max-w-5xl flex-1 px-4 py-6 md:px-8 md:py-10">
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
