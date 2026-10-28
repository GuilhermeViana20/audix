<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>audix · @yield('titulo')</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-offwhite text-grafite min-h-screen">
<header class="sticky top-0 z-30 border-b border-grafite/10 bg-white/95 shadow-sm backdrop-blur">
    <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3 md:px-8">
        <a href="{{ route('inicio') }}" class="flex items-center">
            <img src="/images/audix-logo.png" alt="audix – Inteligência em cada controle" class="h-10 w-auto md:h-12">
        </a>
        <nav class="flex items-center gap-4 text-sm font-medium text-grafite/70">
            <a href="{{ route('inicio') }}" class="transition hover:text-azul">Planilhas</a>
            <a href="{{ route('supermercados.index') }}" class="transition hover:text-azul">Supermercados</a>
        </nav>
    </div>
</header>

<main class="mx-auto max-w-5xl px-4 py-6 md:px-8 md:py-10">
    @yield('conteudo')
</main>
</body>
</html>
