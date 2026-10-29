@extends('layouts.app')

@section('titulo', 'Escolha o supermercado')

@section('breadcrumb')
    @include('partials.breadcrumb', ['itens' => [
        ['nome' => 'Início', 'url' => route('inicio')],
        ['nome' => $nomeTipo],
    ]])
@endsection

@section('conteudo')
    @php
        $rotaControle = match ($tipo) {
            'pragas' => 'pragas.index',
            'temperatura' => 'temperatura.index',
            default => 'higiene.index',
        };

        $total = $supermercados->count();
    @endphp

    <style>
        .selecao-mercados {
            --sm-panel: #ffffff;
            --sm-text: #151922;
            --sm-muted: #697386;
            --sm-border: #e4e9f2;
            --sm-soft: #f5f7fa;
            --sm-blue: #4063ff;
            --sm-accent: #3152dc;

            color: var(--sm-text);
        }

        .dark .selecao-mercados {
            --sm-panel: #181e2b;
            --sm-text: #edf1fa;
            --sm-muted: #a5afc2;
            --sm-border: #2c3548;
            --sm-soft: #111722;
            --sm-accent: #a5b5ff;
        }

        .selecao-mercados [hidden] {
            display: none !important;
        }

        .sm-cabecalho {
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 24px;
            padding: 30px;
            border: 1px solid var(--sm-border);
            border-radius: 22px;
            background:
                radial-gradient(
                    ellipse at top right,
                    #4063ff12,
                    transparent 60%
                ),
                var(--sm-panel);
        }

        .sm-cabecalho::before {
            content: "";
            position: absolute;
            inset: 24px auto 24px 0;
            width: 3px;
            border-radius: 0 4px 4px 0;
            background: var(--sm-blue);
        }

        .sm-etiqueta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--sm-accent);
            font-size: 10px;
            font-weight: 750;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .sm-etiqueta svg {
            width: 16px;
            height: 16px;
        }

        .sm-cabecalho h1 {
            margin: 12px 0 8px;
            font-size: clamp(24px, 2.5vw, 34px);
            line-height: 1.2;
            font-weight: 750;
            letter-spacing: -.045em;
        }

        .sm-cabecalho p {
            margin: 0;
            color: var(--sm-muted);
            font-size: 13px;
            line-height: 1.7;
        }

        .sm-gerenciar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 44px;
            padding: 11px 16px;
            border: 1px solid var(--sm-border);
            border-radius: 12px;
            background: var(--sm-panel);
            color: var(--sm-text);
            text-decoration: none;
            font-size: 12px;
            font-weight: 650;
            transition: border-color .2s, color .2s;
        }

        .sm-gerenciar:hover {
            color: var(--sm-accent);
            border-color: var(--sm-blue);
        }

        .sm-gerenciar svg {
            width: 17px;
            height: 17px;
        }

        .sm-ferramentas {
            display: flex;
            align-items: end;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 18px;
            margin: 28px 0 20px;
        }

        .sm-ferramentas h2 {
            margin: 0 0 5px;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -.025em;
        }

        .sm-contador {
            margin: 0;
            color: var(--sm-muted);
            font-size: 12px;
        }

        .sm-busca {
            width: min(100%, 380px);
        }

        .sm-busca label {
            display: block;
            margin-bottom: 7px;
            color: var(--sm-muted);
            font-size: 11px;
            font-weight: 600;
        }

        .sm-busca-campo {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 46px;
            padding: 0 14px;
            border: 1px solid var(--sm-border);
            border-radius: 12px;
            background: var(--sm-panel);
            transition: border-color .2s, box-shadow .2s;
        }

        .sm-busca-campo:focus-within {
            border-color: var(--sm-blue);
            box-shadow: 0 0 0 3px #4063ff15;
        }

        .sm-busca-campo svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            color: var(--sm-muted);
        }

        .selecao-mercados .sm-busca-campo input {
            width: 100%;
            min-width: 0;
            padding: 12px 0;
            border: 0;
            outline: none;
            background: transparent;
            color: var(--sm-text);
            box-shadow: none;
            font: inherit;
            font-size: 13px;
        }

        .sm-busca-campo input::placeholder {
            color: var(--sm-muted);
        }

        .sm-grade {
            display: grid;
            grid-template-columns: repeat(
                auto-fit,
                minmax(min(100%, 245px), 1fr)
            );
            gap: 18px;
        }

        .sm-cartao {
            position: relative;
            display: flex;
            flex-direction: column;
            min-width: 0;
            overflow: hidden;
            border: 1px solid var(--sm-border);
            border-radius: 18px;
            background: var(--sm-panel);
            color: var(--sm-text);
            text-decoration: none;
            transition:
                transform .2s,
                border-color .2s,
                box-shadow .2s;
        }

        .sm-cartao:hover {
            transform: translateY(-4px);
            border-color: var(--sm-blue);
            box-shadow: 0 16px 36px #1519220d;
        }

        .sm-cartao:focus-visible,
        .sm-gerenciar:focus-visible,
        .sm-botao:focus-visible {
            outline: 3px solid #7e97ff;
            outline-offset: 4px;
        }

        .sm-cartao-topo {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 17px 19px 0;
        }

        .sm-cartao-tipo {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .13em;
            text-transform: uppercase;
            color: var(--sm-muted);
        }

        .sm-seta {
            display: grid;
            place-items: center;
            width: 28px;
            height: 28px;
            border: 1px solid var(--sm-border);
            border-radius: 50%;
            color: var(--sm-muted);
            transition: background .2s, color .2s, border-color .2s;
        }

        .sm-seta svg {
            width: 14px;
            height: 14px;
        }

        .sm-cartao:hover .sm-seta {
            border-color: var(--sm-blue);
            background: var(--sm-blue);
            color: white;
        }

        .sm-logo {
            display: grid;
            place-items: center;
            height: 140px;
            margin: 16px 19px 0;
            padding: 24px;
            border: 1px solid #edf0f5;
            border-radius: 13px;
            background: #fff;
        }

        .sm-logo img {
            display: block;
            width: auto;
            height: auto;
            max-width: 100%;
            max-height: 88px;
            object-fit: contain;
        }

        .sm-iniciais {
            display: grid;
            place-items: center;
            width: 64px;
            height: 64px;
            border-radius: 18px;
            background: #eef2ff;
            color: #4063ff;
            font-size: 27px;
            font-weight: 750;
            letter-spacing: -.04em;
        }

        .sm-cartao-conteudo {
            display: flex;
            flex: 1;
            flex-direction: column;
            padding: 21px 19px 18px;
        }

        .sm-cartao h3 {
            margin: 0 0 7px;
            font-size: 17px;
            line-height: 1.4;
            font-weight: 700;
            letter-spacing: -.025em;
            overflow-wrap: anywhere;
        }

        .sm-cartao-descricao {
            margin: 0 0 22px;
            color: var(--sm-muted);
            font-size: 12px;
            line-height: 1.6;
        }

        .sm-cartao-acao {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: auto;
            padding-top: 16px;
            border-top: 1px solid var(--sm-border);
            color: var(--sm-accent);
            font-size: 12px;
            font-weight: 650;
        }

        .sm-cartao-acao svg {
            width: 17px;
            height: 17px;
            transition: transform .2s;
        }

        .sm-cartao:hover .sm-cartao-acao svg {
            transform: translateX(3px);
        }

        .sm-vazio {
            padding: 48px 24px;
            border: 1px dashed var(--sm-border);
            border-radius: 18px;
            background: var(--sm-panel);
            text-align: center;
        }

        .sm-vazio h3 {
            margin: 0 0 10px;
            font-size: 19px;
            font-weight: 700;
        }

        .sm-vazio p {
            margin: 0;
            color: var(--sm-muted);
            font-size: 13px;
            line-height: 1.7;
        }

        .sm-botao {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            margin-top: 20px;
            padding: 11px 18px;
            border: 0;
            border-radius: 11px;
            background: var(--sm-blue);
            color: white;
            text-decoration: none;
            font: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        @media (max-width: 640px) {
            .sm-cabecalho {
                padding: 24px;
                border-radius: 18px;
            }

            .sm-gerenciar,
            .sm-busca {
                width: 100%;
            }

            .sm-ferramentas {
                align-items: stretch;
                flex-direction: column;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .selecao-mercados *,
            .selecao-mercados *::before {
                transition: none !important;
            }

            .sm-cartao:hover {
                transform: none;
            }
        }
    </style>

    <section class="selecao-mercados" id="selecao-mercados">
        <header class="sm-cabecalho">
            <div>
                <span class="sm-etiqueta">
                    <svg viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.7"
                         stroke-linecap="round" stroke-linejoin="round"
                         aria-hidden="true">
                        <rect x="5" y="4" width="14" height="17" rx="2"/>
                        <path d="M9 4V2h6v2M9 10h6M9 14h6"/>
                    </svg>

                    {{ $nomeTipo }}
                </span>

                <h1>Onde vamos trabalhar hoje?</h1>

                <p>
                    Selecione um supermercado para abrir e preparar sua planilha.
                </p>
            </div>

            <a href="{{ route('supermercados.index') }}"
               class="sm-gerenciar">
                <svg viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.7"
                     stroke-linecap="round" stroke-linejoin="round"
                     aria-hidden="true">
                    <path d="M3 10h18l-2-7H5l-2 7Z"/>
                    <path d="M4 10v11h16V10M9 21v-7h6v7"/>
                </svg>

                Gerenciar supermercados
            </a>
        </header>

        @if ($total > 0)
            <div class="sm-ferramentas">
                <div>
                    <h2>Seus supermercados</h2>

                    <p class="sm-contador"
                       id="sm-contador"
                       role="status"
                       aria-live="polite"
                       aria-atomic="true">
                        {{ $total }}
                        {{ $total === 1 ? 'supermercado disponível' : 'supermercados disponíveis' }}
                    </p>
                </div>

                <div class="sm-busca">
                    <label for="sm-pesquisa">Buscar supermercado</label>

                    <div class="sm-busca-campo">
                        <svg viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="1.8"
                             stroke-linecap="round" aria-hidden="true">
                            <circle cx="10.5" cy="10.5" r="6.5"/>
                            <path d="m16 16 4.5 4.5"/>
                        </svg>

                        <input
                            id="sm-pesquisa"
                            type="search"
                            placeholder="Digite o nome do supermercado..."
                            autocomplete="off"
                            aria-controls="sm-grade"
                        >
                    </div>
                </div>
            </div>

            <div class="sm-grade" id="sm-grade">
                @foreach ($supermercados as $supermercado)
                    <a
                        href="{{ route($rotaControle, $supermercado) }}"
                        class="sm-cartao"
                        data-supermercado="{{ $supermercado->nome }}"
                    >
                        <div class="sm-cartao-topo">
                            <span class="sm-cartao-tipo">Supermercado</span>

                            <span class="sm-seta">
                                <svg viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="1.8"
                                     stroke-linecap="round" stroke-linejoin="round"
                                     aria-hidden="true">
                                    <path d="M7 17 17 7M7 7h10v10"/>
                                </svg>
                            </span>
                        </div>

                        <div class="sm-logo">
                            @if ($supermercado->logo_url)
                                <img
                                    src="{{ $supermercado->logo_url }}"
                                    alt=""
                                    loading="lazy"
                                    decoding="async"
                                    referrerpolicy="no-referrer"
                                >
                            @endif

                            <span
                                class="sm-iniciais"
                                aria-hidden="true"
                                @if ($supermercado->logo_url) hidden @endif
                            >
                                {{ \Illuminate\Support\Str::upper(
                                    \Illuminate\Support\Str::substr(
                                        $supermercado->nome, 0, 2
                                    )
                                ) }}
                            </span>
                        </div>

                        <div class="sm-cartao-conteudo">
                            <h3>{{ $supermercado->nome }}</h3>

                            <p class="sm-cartao-descricao">
                                {{ $nomeTipo }}
                            </p>

                            <div class="sm-cartao-acao">
                                <span>Abrir controle</span>

                                <svg viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="1.8"
                                     stroke-linecap="round" stroke-linejoin="round"
                                     aria-hidden="true">
                                    <path d="M4 12h16m-6-6 6 6-6 6"/>
                                </svg>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="sm-vazio" id="sm-sem-resultados" hidden>
                <h3>Nenhum supermercado encontrado</h3>
                <p>Tente outro nome ou limpe a busca para ver todos.</p>

                <button class="sm-botao" type="button" id="sm-limpar">
                    Limpar busca
                </button>
            </div>
        @else
            <div class="sm-vazio" style="margin-top: 24px;">
                <h3>Seu primeiro supermercado começa aqui.</h3>

                <p>
                    Cadastre um supermercado para começar a organizar
                    suas planilhas de controle.
                </p>

                <a href="{{ route('supermercados.criar') }}"
                   class="sm-botao">
                    + Cadastrar supermercado
                </a>
            </div>
        @endif
    </section>

    <script>
        (() => {
            const raiz = document.getElementById('selecao-mercados');

            if (!raiz) return;

            // Exibe as iniciais caso uma logo não consiga carregar.
            raiz.querySelectorAll('.sm-logo img').forEach((imagem) => {
                const mostrarIniciais = () => {
                    imagem.hidden = true;

                    const iniciais = imagem.parentElement
                        .querySelector('.sm-iniciais');

                    if (iniciais) iniciais.hidden = false;
                };

                imagem.addEventListener('error', mostrarIniciais);

                if (imagem.complete && imagem.naturalWidth === 0) {
                    mostrarIniciais();
                }
            });

            const pesquisa = raiz.querySelector('#sm-pesquisa');

            if (!pesquisa) return;

            const grade = raiz.querySelector('#sm-grade');
            const contador = raiz.querySelector('#sm-contador');
            const vazio = raiz.querySelector('#sm-sem-resultados');
            const limpar = raiz.querySelector('#sm-limpar');

            const normalizar = (texto) => texto
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLocaleLowerCase('pt-BR')
                .trim();

            const mercados = Array.from(
                raiz.querySelectorAll('[data-supermercado]')
            ).map((elemento) => ({
                elemento,
                nome: normalizar(elemento.dataset.supermercado || ''),
            }));

            function filtrar() {
                const termos = normalizar(pesquisa.value)
                    .split(/\s+/)
                    .filter(Boolean);

                let encontrados = 0;

                mercados.forEach(({ elemento, nome }) => {
                    const corresponde = termos.every(
                        (termo) => nome.includes(termo)
                    );

                    elemento.hidden = !corresponde;

                    if (corresponde) encontrados++;
                });

                grade.hidden = encontrados === 0;
                vazio.hidden = encontrados !== 0;

                contador.textContent = termos.length
                    ? `${encontrados} de ${mercados.length} supermercados`
                    : `${mercados.length} ${
                        mercados.length === 1
                            ? 'supermercado disponível'
                            : 'supermercados disponíveis'
                    }`;
            }

            pesquisa.addEventListener('input', filtrar);

            limpar.addEventListener('click', () => {
                pesquisa.value = '';
                filtrar();
                pesquisa.focus();
            });

            filtrar();
        })();
    </script>
@endsection