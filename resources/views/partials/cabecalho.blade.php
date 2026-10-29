@php
    $tipoAtual = request()->route('tipo') ?? ($tipo ?? null);
    $menuAudix = [
        ['inicio', 'Visão geral', route('inicio'), request()->routeIs('inicio')],
        ['temperatura', 'Temperatura', route('planilhas.supermercados', 'temperatura'), request()->routeIs('temperatura.*') || $tipoAtual === 'temperatura'],
        ['higiene', 'Higiene e limpeza', route('planilhas.supermercados', 'higiene'), request()->routeIs('higiene.*') || $tipoAtual === 'higiene'],
        ['pragas', 'Controle de pragas', route('planilhas.supermercados', 'pragas'), request()->routeIs('pragas.*') || $tipoAtual === 'pragas'],
        ['loja', 'Supermercados', route('supermercados.index'), request()->routeIs('supermercados.*')],
    ];
@endphp
<aside class="audix-sidebar" aria-label="Navegação principal">
    <a class="audix-brand" href="{{ route('inicio') }}" aria-label="Audix — início">
        <img src="/images/audix-logo-branco.png" alt="audix">
    </a>
    <p class="audix-nav-label">ÁREA DE TRABALHO</p>
    <nav class="audix-nav">
        @foreach ($menuAudix as [$icone, $rotulo, $url, $ativo])
            <a href="{{ $url }}" class="audix-nav-link {{ $ativo ? 'is-active' : '' }}" @if($ativo) aria-current="page" @endif>
                @include('partials.icone', ['icone' => $icone])<span>{{ $rotulo }}</span>
            </a>
        @endforeach
    </nav>
    <div class="audix-sidebar-bottom"><span class="audix-small-mark">a<span>×</span></span><p>Inteligência em<br>cada controle.</p></div>
</aside>
<header class="audix-topbar">
    <div>
        <span class="audix-topbar-label">
            AUDIX / GESTÃO DE CONTROLES
        </span>

        <p>Seu padrão de qualidade começa aqui.</p>
    </div>

    <div class="ml-auto flex shrink-0 items-center gap-3">
        @auth
            <span class="hidden text-sm font-semibold sm:block">
                {{ auth()->user()->name }}
            </span>
        @endauth

        <button
            type="button"
            onclick="alternarTema()"
            class="audix-theme"
            aria-label="Alternar tema claro e escuro"
            title="Alternar tema"
        >
            <svg
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.7"
                aria-hidden="true"
            >
                <path d="M21 13A9 9 0 1 1 11 3a7 7 0 0 0 10 10Z"/>
            </svg>
        </button>

        @auth
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button
                    type="submit"
                    class="rounded-lg border border-grafite/15 px-3 py-2 text-sm font-semibold transition hover:bg-azul/10 hover:text-azul"
                >
                    Sair
                </button>
            </form>
        @endauth
    </div>
</header>
