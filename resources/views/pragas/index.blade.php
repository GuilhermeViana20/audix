<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
    @vite(['resources/css/app.css', 'resources/js/carregando.js'])
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

        /* Tela: folhas menores para caber no celular e no computador */
        @media (max-width: 1600px) { .folha { zoom: .62; margin-bottom: -300px; } }
        @media (max-width: 760px)  { .folha { zoom: .36; margin-bottom: -500px; } }

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
    </style>
</head>
<body class="bg-offwhite text-grafite">
    @include('partials.cabecalho')

    <div class="print:!p-0 px-4 pt-6 pb-10 md:px-8 xl:grid xl:grid-cols-[3fr_7fr] xl:items-start xl:gap-6">
    <div class="nao-imprimir xl:sticky xl:top-28 xl:self-start">
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

                <div class="mt-4 divide-y divide-grafite/10">
                    @forelse ($secoes as $secao)
                        <div class="flex items-center justify-between gap-3 py-3">
                            <div class="flex items-center gap-3">
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-offwhite text-xs font-bold text-grafite/60">{{ $loop->iteration }}</span>
                                <span class="font-semibold">{{ $secao->nome }}</span>
                            </div>
                            <form method="POST" action="{{ route('pragas.secoes.destroy', $secao) }}"
                                  onsubmit="return confirm('Remover a seção {{ addslashes($secao->nome) }}?')">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-lg px-2.5 py-1.5 text-sm font-semibold text-red-500 transition hover:bg-red-50">Remover</button>
                            </form>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-grafite/60">Nenhuma seção ainda. Adicione abaixo, por exemplo AÇOUGUE.</p>
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

    <main id="folhas" class="print:!p-0 flex min-w-0 flex-col items-center gap-6 pb-10">
        @forelse ($secoes as $secao)
            <section class="folha">
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
                        <td style="width:30%"><b>SEÇÃO:</b><span class="valor">{{ $secao->nome }}</span></td>
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
    @if (session('ok'))
        <script>alert(@json(session('ok')));</script>
    @endif
    @if ($errors->any())
        <script>alert(@json($errors->first()));</script>
    @endif
</body>
</html>
