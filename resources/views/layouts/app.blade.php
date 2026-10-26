<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>audix · @yield('titulo')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        azul: '#4063FF',
                        grafite: '#151922',
                        offwhite: '#F5F7FA',
                    },
                },
            },
        };
    </script>
</head>
<body class="bg-offwhite text-grafite min-h-screen">
<div class="flex min-h-screen">

    {{-- Menu lateral (identidade audix) --}}
    <aside class="hidden md:flex w-60 shrink-0 flex-col bg-grafite text-white sticky top-0 h-screen px-4 py-7">
        <a href="{{ route('supermercados.index') }}" class="px-2 text-3xl font-extrabold tracking-tight">
            audi<span class="text-azul">x</span>
        </a>
        <p class="px-2 pt-1 pb-6 text-xs text-white/55 leading-snug">Inteligência em cada controle.</p>

        <nav class="grid gap-1">
            <a href="{{ route('supermercados.index') }}"
               class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs('supermercados.index') ? 'bg-azul text-white shadow-lg shadow-azul/30' : 'text-white/80 hover:bg-white/5' }}">
                <span class="w-4 text-center">▦</span> Visão geral
            </a>
            <span class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-white/35 cursor-not-allowed">
                <span class="w-4 text-center">🌡</span> Temperatura
                <em class="ml-auto not-italic text-[10px] uppercase tracking-wide text-white/40">em breve</em>
            </span>
            <a href="{{ route('higiene.index') }}"
               class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs('higiene.*') ? 'bg-azul text-white shadow-lg shadow-azul/30' : 'text-white/80 hover:bg-white/5' }}">
                <span class="w-4 text-center">💧</span> Higiene
            </a>
            <span class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-white/35 cursor-not-allowed">
                <span class="w-4 text-center">⚙</span> CIP
            </span>
        </nav>

        <div class="mt-auto grid gap-1 border-t border-white/10 pt-4">
            <span class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-white/35 cursor-not-allowed"><span class="w-4 text-center">▤</span> Relatórios</span>
            <span class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-white/35 cursor-not-allowed"><span class="w-4 text-center">⚙</span> Configurações</span>
        </div>
    </aside>

    {{-- Cabeçalho mobile --}}
    <div class="md:hidden fixed top-0 inset-x-0 z-10 bg-grafite text-white px-4 py-3 flex items-center justify-between">
        <a href="{{ route('supermercados.index') }}" class="text-xl font-extrabold">audi<span class="text-azul">x</span></a>
    </div>

    <main class="flex-1 min-w-0 px-5 py-8 md:px-10 pt-16 md:pt-8">
        @yield('conteudo')
    </main>
</div>
</body>
</html>
