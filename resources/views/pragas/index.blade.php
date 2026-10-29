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
    <title>Controle de Pragas - {{ $supermercado->nome_social ?: $supermercado->nome }}</title>
    @vite(['resources/css/app.css', 'resources/js/carregando.js', 'resources/js/toast.js'])
    <style>
        /* Folha A4 paisagem (297 x 210 mm a 96 dpi = 1123 x 794 px) */
        .folha { width: 1123px; height: 794px; box-sizing: border-box; padding: 22px 36px; background: #fff; color: #000;
                 font-family: Times, 'Times New Roman', serif; font-size: 10.5px; display: flex; flex-direction: column;
                 overflow: hidden; box-shadow: 0 2px 10px rgba(0, 0, 0, .12); }
        .folha table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .folha td, .folha th { border: 1px solid #000; padding: 0; text-align: center; vertical-align: middle; }
        .folha .sem-borda { border: 0; }
        .folha .cab td { height: 50px; }
        .folha .esq { text-align: left; width: 200px; }
        .folha .dir { text-align: right; width: 200px; }
        .folha .titulo { font-size: 16px; font-weight: bold; line-height: 1.25; }
        .folha .logo-acoop, .folha .logo-mae { height: 40px; display: inline-block; }
        .folha .logo-box { display: inline-block; width: 120px; height: 40px; line-height: 40px; border: 1px dashed #777; font-size: 7px; color: #555; }
        .folha .topo td { height: 38px; vertical-align: top; padding-top: 4px; font-size: 10.5px; }
        .folha .topo .valor { display: block; font-size: 11.5px; }
        .folha .topo.local td { height: 24px; vertical-align: middle; text-align: left; padding-left: 4px; }
        .folha .topo:not(.local) td { border-bottom: 0; }
        .folha .grade-caixa { margin-top: 12px; }
        .folha .grade th { font-weight: bold; height: 24px; font-size: 12px; }
        .folha .grade td { height: 26px; font-size: 10.5px; }
        .folha .grade td:first-child { font-weight: bold; }
        .folha .grade th, .folha .grade td:first-child { background: #d9d9d9; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .folha .legenda { margin-top: 14px; table-layout: fixed; }
        .folha .legenda td { height: 28px; font-size: 11px; }
        .folha .legenda .lateral { width: 22px; background: #c00000; color: #fff; font-weight: bold; font-size: 8px;
                                   border-color: #000; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .folha .legenda .lateral span { display: block; font-size: 10px; line-height: 11px; letter-spacing: 0; }
        .folha .legenda .rotulo { width: 90px; background: #ffff00; font-weight: bold; font-size: 11px;
                                  -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .folha .legenda .codigo { color: #1f3a5f; font-weight: bold; font-size: 13px; }
        .folha .legenda .nome { font-size: 10px; font-weight: bold; }
        .folha .legenda .imagem { height: 52px; }
        .folha .legenda td { text-align: center; vertical-align: middle; }
        .folha .legenda .imagem img { display: block; margin: 0 auto; max-height: 46px; max-width: 100%; object-fit: contain; }
        .folha .obs { margin-top: 22px; font-weight: bold; font-size: 11px; }
        .folha .rodape { margin-top: auto; padding-top: 3px; text-align: center; font-size: 9px; line-height: 11px; color: #9a9a9a; }
        .folha .rodape p { margin: 0; }
        .folha .rodape .nome { font-size: 10px; font-weight: bold; color: #7a7a7a; }

        @media print {
            @page { size: A4 landscape; margin: 8mm; }
            .nao-imprimir { display: none !important; }
            html, body, main { background: #fff !important; margin: 0 !important; padding: 0 !important; }
            main { gap: 0 !important; display: block !important; }
            .folha { zoom: 1 !important; margin: 0 !important; box-shadow: none; padding: 0; width: 100%; height: 194mm;
                     break-after: page; page-break-after: always; }
            .folha:last-child { break-after: auto; page-break-after: auto; }
            .folha .grade th, .folha .grade td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }

        @media screen {
    .cip-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        align-items: start;
        gap: 24px;
        width: 100%;
        padding: 24px 32px 40px;
    }

    .cip-painel {
        min-width: 0;
    }

    .cip-previa {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 24px;
        min-width: 0;
        width: 100%;
    }

    .cip-previa .folha {
        flex-shrink: 0;
        margin: 0;
        zoom: var(--cip-zoom, 1);
    }

    .cip-secoes {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        margin-top: 20px;
    }

    .cip-secao {
        display: flex;
        flex-direction: column;
        min-width: 0;
        padding: 16px;
        border: 1px solid #e4e9f2;
        border-radius: 14px;
        background: #f9faff;
        transition: border-color .2s, background .2s;
    }

    .cip-secao:hover {
        border-color: #a9b8ff;
        background: #f0f4ff;
    }

    .cip-secao-topo {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .cip-secao-numero {
        display: grid;
        place-items: center;
        width: 32px;
        height: 32px;
        border-radius: 10px;
        background: #4063ff12;
        color: #3152dc;
        font-size: 11px;
        font-weight: 750;
    }

    .cip-secao-tipo {
        color: #697386;
        font-size: 9px;
        font-weight: 650;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .cip-secao h3 {
        margin: 15px 0 18px;
        color: #151922;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    .cip-secao-rodape {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-top: auto;
        padding-top: 12px;
        border-top: 1px solid #e4e9f2;
    }

    .cip-secao-rodape > span {
        color: #697386;
        font-size: 10px;
    }

    .cip-remover {
        display: grid;
        place-items: center;
        width: 34px;
        height: 34px;
        padding: 0;
        border: 0;
        border-radius: 9px;
        background: transparent;
        color: #b42336;
        cursor: pointer;
    }

    .cip-remover:hover {
        background: #ffe4e8;
    }

    .cip-remover:focus-visible {
        outline: 3px solid #4063ff;
        outline-offset: 3px;
    }

    .cip-secoes-vazio {
        grid-column: 1 / -1;
        padding: 20px;
        border: 1px dashed #b9c3d5;
        border-radius: 12px;
        color: #697386;
        font-size: 13px;
        line-height: 1.6;
    }

    .dark .cip-secao {
        background: #111827;
        border-color: #2c3548;
    }

    .dark .cip-secao:hover {
        background: #19233a;
        border-color: #647ed8;
    }

    .dark .cip-secao h3 {
        color: #edf1fa;
    }

    .dark .cip-secao-numero {
        background: #4063ff25;
        color: #a5b5ff;
    }

    .dark .cip-secao-tipo,
    .dark .cip-secao-rodape > span,
    .dark .cip-secoes-vazio {
        color: #a5afc2;
    }

    .dark .cip-secao-rodape {
        border-color: #2c3548;
    }

    .dark .cip-remover {
        color: #fda4af;
    }

    .dark .cip-remover:hover {
        background: #f43f5e20;
    }

    @media (min-width: 1280px) {
        .cip-layout {
            grid-template-columns:
                clamp(340px, 28vw, 480px)
                minmax(0, 1fr);
        }
    }

    @media (max-width: 767px) {
        .cip-layout {
            padding: 20px 16px 32px;
            gap: 20px;
        }
    }

    @media (max-width: 420px) {
        .cip-secoes {
            grid-template-columns: 1fr;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .cip-secao {
            transition: none;
        }
    }
}

/* Desktop: cada coluna possui sua própria rolagem */
@media screen and (min-width: 1280px) {
    body.audix-shell {
        height: 100vh;
        height: 100dvh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .audix-topbar {
        flex-shrink: 0;
    }

    .cip-layout {
        flex: 1;
        min-height: 0;
        overflow: hidden;
        padding: 24px 32px;
        align-items: stretch;
    }

    .cip-painel,
    .cip-previa {
        height: 100%;
        min-height: 0;
        min-width: 0;
        overflow-y: auto;
        overflow-x: hidden;
        overscroll-behavior-y: contain;
        scrollbar-gutter: stable;
        scrollbar-width: thin;
        scrollbar-color: #a5b5d4 transparent;
    }

    .cip-painel {
        position: static;
        padding-right: 12px;
        padding-bottom: 24px;
    }

    .cip-previa {
        padding-right: 12px;
        padding-bottom: 24px;
    }

    .cip-painel::-webkit-scrollbar,
    .cip-previa::-webkit-scrollbar {
        width: 7px;
    }

    .cip-painel::-webkit-scrollbar-thumb,
    .cip-previa::-webkit-scrollbar-thumb {
        background: #a5b5d4;
        border-radius: 20px;
    }

    .dark .cip-painel,
    .dark .cip-previa {
        scrollbar-color: #465572 transparent;
    }

    .dark .cip-painel::-webkit-scrollbar-thumb,
    .dark .cip-previa::-webkit-scrollbar-thumb {
        background: #465572;
    }
}

@media print {
    body.audix-shell,
    .cip-layout,
    .cip-previa {
        height: auto !important;
        max-height: none !important;
        overflow: visible !important;
    }

    .cip-layout,
    .cip-previa {
        display: block !important;
        padding: 0 !important;
        scrollbar-gutter: auto;
    }
}

@media screen {
    .cip-previa { justify-self: stretch; }
    .cip-edicao { margin: 0 0 16px; }
    .cip-edicao summary { color:#3152dc; cursor:pointer; font-size:12px; font-weight:600; padding:6px 0; }
    .cip-renomear-form { margin-top:10px; }
    .cip-renomear-form label { display:block; font-size:11px; margin-bottom:6px; }
    .cip-renomear-form input { width:100%; min-width:0; padding:10px; border:1px solid #cbd5e1; border-radius:8px; background:#fff; color:#151922; font-size:13px; }
    .cip-edicao-status { font-size:12px; line-height:1.5; margin:8px 0; color:#b42336; overflow-wrap:anywhere; }
    .cip-edicao-acoes { display:flex; flex-wrap:wrap; gap:8px; }
    .cip-edicao-acoes button { border:1px solid #cbd5e1; border-radius:8px; padding:8px 10px; font-size:11px; cursor:pointer; }
    .cip-edicao-acoes button[type=submit] { background:#4063ff; color:#fff; border-color:#4063ff; }
    .cip-edicao-acoes button:disabled { opacity:.5; cursor:wait; }
    .cip-edicao :focus-visible { outline:2px solid #4063ff; outline-offset:3px; }
    .dark .cip-edicao summary { color:#a5b5ff; }
    .dark .cip-renomear-form input { background:#111827; color:#edf1fa; border-color:#46516a; }
    .dark .cip-edicao-status { color:#fda4af; }
}
    </style>
</head>

<body class="audix-shell bg-offwhite text-grafite">
    @include('partials.cabecalho')

    <div class="cip-layout">
    <div class="cip-painel nao-imprimir">
        @include('partials.breadcrumb', ['itens' => [
            ['nome' => 'Início', 'url' => route('inicio')],
            ['nome' => 'Controle de Pragas', 'url' => route('planilhas.supermercados', 'pragas')],
            ['nome' => $supermercado->nome],
        ]])

        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-azul">Controle de Pragas</p>
                <h1 class="text-2xl font-bold tracking-tight">{{ $supermercado->nome }}</h1>
                <p class="mt-1 text-sm text-grafite/60">Cadastre as seções e imprima uma folha por seção.</p>
            </div>
            <button onclick="window.print()"
                    class="rounded-xl bg-azul px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 disabled:opacity-40"
                    @disabled($secoes->isEmpty())>Imprimir / PDF</button>
        </div>


        <div class="flex flex-col gap-4">
            {{-- Seções salvas e inclusão de novas --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-grafite/5">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold">Seções</h2>
                    <span class="rounded-full bg-azul/10 px-2.5 py-0.5 text-xs font-semibold text-azul">{{ $secoes->count() }} salvas</span>
                </div>

                <div class="cip-secoes">
                    @forelse ($secoes as $secao)
                        <article class="cip-secao" data-secao-id="{{ $secao->id }}">
                            <div class="cip-secao-topo">
                                <span class="cip-secao-numero">
                                    {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                </span>

                                <span class="cip-secao-tipo">Seção</span>
                            </div>

                            <h3 data-nome-secao>{{ $secao->nome }}</h3>
                            <details class="cip-edicao">
                                <summary>Renomear seção</summary>
                                <form class="cip-renomear-form" action="{{ url('/api/secoes/'.$secao->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <label for="nome-secao-{{ $secao->id }}">Nome da seção</label>
                                    <input id="nome-secao-{{ $secao->id }}" name="nome" value="{{ $secao->nome }}" required maxlength="120" autocomplete="off">
                                    <p class="cip-edicao-status" role="status" aria-live="polite"></p>
                                    <div class="cip-edicao-acoes">
                                        <button type="button" data-cancelar>Cancelar</button>
                                        <button type="submit">Salvar</button>
                                    </div>
                                </form>
                            </details>

                            <div class="cip-secao-rodape">
                                <span>1 folha de controle</span>

                                <form
                                    method="POST"
                                    action="{{ route('pragas.secoes.destroy', $secao) }}"
                                    data-loading-texto="Removendo…"
                                    onsubmit="return confirm('Deseja remover esta seção?')"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="cip-remover"
                                        aria-label="Remover seção {{ $secao->nome }}"
                                        title="Remover seção"
                                    >
                                        <svg
                                            width="17"
                                            height="17"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.7"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            aria-hidden="true"
                                        >
                                            <path d="M3 6h18M9 6V4h6v2"/>
                                            <path d="m5 6 1 14h12l1-14M10 10v6m4-6v6"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <p class="cip-secoes-vazio">
                            Nenhuma seção cadastrada. Adicione a primeira abaixo.
                        </p>
                    @endforelse
                </div>

                <form method="POST" action="{{ route('pragas.secoes.store', $supermercado) }}" id="form-secoes" class="mt-6 rounded-xl bg-offwhite p-4">
                    @csrf
                    <label for="nova-secao" class="text-xs font-semibold uppercase tracking-wide text-grafite/50">Nova seção</label>
                    <div class="mt-2 flex gap-2">
                        <input id="nova-secao" maxlength="120" placeholder="Ex.: FLV"
                               class="min-w-0 flex-1 rounded-xl bg-white px-3 py-2 text-sm ring-1 ring-grafite/10 focus:outline-none focus:ring-2 focus:ring-azul">
                        <button type="button" id="adicionar-secao" class="rounded-xl bg-white px-4 text-sm font-semibold text-azul ring-1 ring-azul/30 transition hover:bg-azul/5">Adicionar</button>
                    </div>
                    <ul id="lista-novas" class="mt-3 space-y-2 text-sm"></ul>
                    <div id="campos-novas"></div>
                    <button id="salvar-secoes" type="submit" disabled
                            class="mt-4 w-full rounded-xl bg-azul px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 disabled:opacity-40">Salvar seções</button>
                </form>
                @error('nomes')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
                @error('nomes.*')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Resumo da folha --}}
            <aside class="flex flex-col gap-4">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-grafite/5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-grafite/50">Folha</p>
                    <ul class="mt-4 space-y-3 text-sm">
                        <li class="flex justify-between"><span class="text-grafite/60">Formato</span><span class="font-semibold">A4 paisagem</span></li>
                        <li class="flex justify-between"><span class="text-grafite/60">Folhas</span><span class="font-semibold">{{ $secoes->count() }} (uma por seção)</span></li>
                        <li class="flex justify-between"><span class="text-grafite/60">Pragas na legenda</span><span class="font-semibold">{{ $pragas->count() }}</span></li>
                        <li class="flex justify-between"><span class="text-grafite/60">Dias do mês</span><span class="font-semibold">1 a 31</span></li>
                    </ul>
                </div>
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-grafite/5">
                    <p class="text-sm text-grafite/70">Para gerar o PDF, escolha <b>Paisagem</b> e <b>A4</b> na janela de impressão.</p>
                </div>
            </aside>
        </div>
    </div>

    <script>
        function alternarTema() {
            var escuro = document.documentElement.classList.toggle('dark');
            try { localStorage.setItem('audix-tema', escuro ? 'escuro' : 'claro'); } catch (e) {}
        }
    </script>

    <main id="folhas" class="cip-previa">
        @forelse ($secoes as $secao)
            <section class="folha" data-folha-secao="{{ $secao->id }}">
                <table class="cab">
                    <tr>
                        <td class="sem-borda esq"><img src="/images/acoop.png" alt="ACCP" class="logo-acoop"></td>
                        <td class="sem-borda titulo">PLANILHA DE MONITORAMENTO<br>CIP – CONTROLE INTEGRADO DE PRAGAS</td>
                        <td class="sem-borda dir">
                            @if ($supermercado->logo_url)
                                <img src="{{ $supermercado->logo_url }}" alt="Logo" class="logo-mae">
                            @else
                                <div class="logo-box">LOGO DO SUPERMERCADO</div>
                            @endif
                        </td>
                    </tr>
                </table>

                <table class="topo">
                    <tr>
                        <td style="width:40%"><b>EMPRESA RESPONSÁVEL:</b></td>
                        <td style="width:30%"><b>SEÇÃO:</b><span class="valor" data-nome-folha>{{ $secao->nome }}</span></td>
                        <td style="width:30%"><b>ENCARREGADO:</b></td>
                    </tr>
                </table>
                <table class="topo local">
                    <tr>
                        <td style="width:50%"><b>PERÍODO:</b></td>
                        <td style="width:50%"><b>DATA:</b></td>
                    </tr>
                </table>

                <div class="grade-caixa">
                    <table class="grade">
                        <colgroup>
                            <col style="width:90px">
                            @for ($d = 1; $d <= 31; $d++)
                                <col>
                            @endfor
                        </colgroup>
                        <thead>
                            <tr>
                                <th>PRAGAS</th>
                                @for ($d = 1; $d <= 31; $d++)
                                    <th>{{ $d }}</th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pragas as $praga)
                                <tr>
                                    <td>{{ $praga->codigo }}</td>
                                    @for ($d = 1; $d <= 31; $d++)
                                        <td></td>
                                    @endfor
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <table class="legenda">
                    <colgroup>
                        <col style="width:22px">
                        <col style="width:80px">
                        @foreach ($pragas as $praga)
                            <col>
                        @endforeach
                    </colgroup>
                    <tr>
                        <td class="lateral" rowspan="3"><span>L<br>E<br>G<br>E<br>N<br>D<br>A</span></td>
                        <td class="rotulo">CÓDIGO</td>
                        @foreach ($pragas as $praga)
                            <td class="codigo">{{ $praga->codigo }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="rotulo">PRAGA</td>
                        @foreach ($pragas as $praga)
                            <td class="nome">{{ $praga->nome }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="rotulo">IMAGEM</td>
                        @foreach ($pragas as $praga)
                            <td class="imagem">
                                @if ($praga->imagem_caminho)
                                    <img src="{{ $praga->imagem_caminho }}" alt="{{ $praga->nome }}">
                                @endif
                            </td>
                        @endforeach
                    </tr>
                </table>

                <p class="obs">OBSERVAÇÕES:</p>

                <div class="rodape">
                    <p class="nome">ACCP Alimentos &amp; Tecnologia | contato@accpconsultoria.com.br</p>
                    <p>Av. Humberto Monte, 2929, Sala 501, Torre Sul – Pici, Fortaleza/CE</p>
                </div>
            </section>
        @empty
            <p class="nao-imprimir rounded-2xl bg-white p-6 text-grafite/60 shadow-sm">Adicione pelo menos uma seção para ver as folhas.</p>
        @endforelse
    </main>
    </div>
    <script>
        (() => {
            const input = document.getElementById('nova-secao');
            const lista = document.getElementById('lista-novas');
            const campos = document.getElementById('campos-novas');
            const salvar = document.getElementById('salvar-secoes');
            const nomes = [];

            const atualizar = () => { salvar.disabled = nomes.length === 0; };

            const adicionar = () => {
                const nome = input.value.trim().toLocaleUpperCase('pt-BR');
                if (!nome || nomes.includes(nome)) { input.value = ''; input.focus(); return; }
                nomes.push(nome);

                const li = document.createElement('li');
                li.className = 'flex items-center justify-between gap-3 rounded-lg bg-offwhite px-3 py-1';
                li.innerHTML = '<span></span><button type="button" class="text-red-500">Remover</button>';
                li.querySelector('span').textContent = nome;
                li.querySelector('button').addEventListener('click', () => {
                    const i = nomes.indexOf(nome);
                    nomes.splice(i, 1);
                    campos.children[i].remove();
                    li.remove();
                    atualizar();
                });
                lista.appendChild(li);

                const campo = document.createElement('input');
                campo.type = 'hidden';
                campo.name = 'nomes[]';
                campo.value = nome;
                campos.appendChild(campo);

                input.value = '';
                input.focus();
                atualizar();
            };

            document.getElementById('adicionar-secao').addEventListener('click', adicionar);
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') { e.preventDefault(); adicionar(); }
            });
            atualizar();
        })();
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
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const previa = document.getElementById('folhas');

        if (!previa) return;

        let larguraAnterior = 0;

        const ajustarPrevia = () => {
            if (window.matchMedia('print').matches) return;

            const largura = previa.clientWidth;

            if (!largura || largura === larguraAnterior) return;

            larguraAnterior = largura;

            // A folha original mede 1123px de largura.
            previa.style.setProperty('--cip-zoom', largura / 1123);
        };

        const observer = new ResizeObserver(ajustarPrevia);
        observer.observe(previa);

        window.addEventListener('afterprint', () => {
            larguraAnterior = 0;
            ajustarPrevia();
        });

        ajustarPrevia();
    });
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.cip-renomear-form').forEach(form => {
        const card = form.closest('[data-secao-id]');
        const details = form.closest('details');
        const input = form.elements.nome;
        const status = form.querySelector('.cip-edicao-status');
        const save = form.querySelector('[type="submit"]');
        const cancel = form.querySelector('[data-cancelar]');
        let original = input.value;
        let busy = false;
        cancel.addEventListener('click', () => {
            if (busy) return;
            input.value = original;
            status.textContent = '';
            details.open = false;
            details.querySelector('summary').focus();
        });
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (busy) return;
            const nome = input.value.trim().toLocaleUpperCase('pt-BR');
            if (!nome) { status.textContent = 'Informe o nome da seção.'; input.focus(); return; }
            busy = true;
            save.disabled = cancel.disabled = true;
            input.readOnly = true;
            save.textContent = 'Salvando…';
            status.textContent = '';
            try {
                const response = await fetch(form.action, {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ nome }),
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    if (response.status === 401 || response.status === 419) {
                        throw new Error('Sua sessão expirou. Recarregue a página e entre novamente.');
                    }
                    throw new Error(data.errors?.nome?.[0] || data.message || 'Não foi possível salvar. Tente novamente.');
                }
                if (typeof data.nome !== 'string') throw new Error('Resposta inesperada. Recarregue para conferir o nome salvo.');
                original = data.nome;
                input.value = original;
                card.querySelector('[data-nome-secao]').textContent = original;
                card.querySelector('.cip-remover').setAttribute('aria-label', 'Remover seção ' + original);
                document.querySelectorAll('[data-folha-secao]').forEach(folha => {
                    if (folha.dataset.folhaSecao === card.dataset.secaoId) {
                        folha.querySelector('[data-nome-folha]').textContent = original;
                    }
                });
                details.open = false;
                details.querySelector('summary').focus();
                window.toast?.('Seção renomeada.', 'sucesso');
            } catch (error) {
                status.textContent = error.message;
            } finally {
                busy = false;
                save.disabled = cancel.disabled = false;
                input.readOnly = false;
                save.textContent = 'Salvar';
            }
        });
    });
});
</script>

</body>
</html>
