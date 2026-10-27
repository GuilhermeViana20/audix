<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>audix · @yield('titulo')</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-offwhite text-grafite min-h-screen">
<header class="bg-grafite text-white">
    <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4 md:px-8">
        <a href="{{ route('inicio') }}" class="text-2xl font-extrabold tracking-tight">audi<span class="text-azul">x</span></a>
        <p class="hidden text-xs text-white/55 sm:block">Inteligência em cada controle.</p>
    </div>
</header>

<main class="mx-auto max-w-5xl px-4 py-6 md:px-8 md:py-10">
    @yield('conteudo')
</main>
</body>
</html>
