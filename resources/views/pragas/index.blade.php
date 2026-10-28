<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Controle de Pragas - {{ $supermercado->nome_social ?: $supermercado->nome }}</title>
    @vite(['resources/css/app.css'])
    <style>
        /* Folha A4 paisagem (297 x 210 mm a 96 dpi = 1123 x 794 px) */
        .folha { width: 1123px; height: 794px; box-sizing: border-box; padding: 22px 36px; background: #fff; color: #000;
                 font-family: Times, 'Times New Roman', serif; font-size: 9px; display: flex; flex-direction: column;
                 overflow: hidden; box-shadow: 0 2px 10px rgba(0, 0, 0, .12); }
        .folha table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .folha td, .folha th { border: 1px solid #000; padding: 0; text-align: center; vertical-align: middle; }
        .folha .sem-borda { border: 0; }
        .folha .cab td { height: 50px; }
        .folha .esq { text-align: left; width: 200px; }
        .folha .dir { text-align: right; width: 200px; }
        .folha .titulo { font-size: 14px; font-weight: bold; line-height: 1.25; }
        .folha .logo-acoop, .folha .logo-mae { height: 40px; display: inline-block; }
        .folha .logo-box { display: inline-block; width: 120px; height: 40px; line-height: 40px; border: 1px dashed #777; font-size: 7px; color: #555; }
        .folha .topo td { height: 34px; vertical-align: top; padding-top: 4px; font-size: 9px; }
        .folha .topo .valor { display: block; font-size: 10px; }
        .folha .topo.local td { height: 24px; vertical-align: middle; text-align: left; padding-left: 4px; }
        .folha .topo:not(.local) td { border-bottom: 0; }
        .folha .grade-caixa { margin-top: 12px; }
        .folha .grade th { font-weight: bold; height: 20px; font-size: 8.5px; }
        .folha .grade td { height: 26px; font-size: 9px; }
        .folha .grade td:first-child { font-weight: bold; }
        .folha .grade th, .folha .grade td:first-child { background: #d9d9d9; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .folha .legenda { margin-top: 14px; table-layout: fixed; }
        .folha .legenda td { height: 22px; font-size: 8px; }
        .folha .legenda .lateral { width: 22px; background: #c00000; color: #fff; font-weight: bold; font-size: 10px; letter-spacing: .2em;
                                   border-color: #000; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .folha .legenda .lateral span { display: inline-block; writing-mode: vertical-rl; transform: rotate(180deg); }
        .folha .legenda .rotulo { width: 80px; background: #ffff00; font-weight: bold; font-size: 8.5px;
                                  -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .folha .legenda .codigo { color: #1f3a5f; font-weight: bold; font-size: 9px; }
        .folha .legenda .nome { font-size: 7.5px; font-weight: bold; }
        .folha .legenda .imagem { height: 42px; }
        .folha .legenda .imagem img { max-height: 36px; max-width: 100%; object-fit: contain; }
        .folha .obs { margin-top: 22px; font-weight: bold; font-size: 9px; }
        .folha .rodape { margin-top: auto; padding-top: 3px; text-align: center; font-size: 8px; line-height: 10px; color: #9a9a9a; }
        .folha .rodape p { margin: 0; }
        .folha .rodape .nome { font-size: 9px; font-weight: bold; color: #7a7a7a; }

        /* Tela: folhas menores para caber no celular e no computador */
        @media (max-width: 1200px) { .folha { zoom: .62; margin-bottom: -300px; } }
        @media (max-width: 760px)  { .folha { zoom: .36; margin-bottom: -500px; } }

        @media print {
            @page { size: A4 landscape; margin: 8mm; }
            .nao-imprimir { display: none !important; }
            .folha { zoom: 1 !important; margin: 0 !important; box-shadow: none; padding: 0; width: 100%; height: 194mm;
                     break-after: page; page-break-after: always; }
            .folha:last-child { break-after: auto; page-break-after: auto; }
            .folha .grade th, .folha .grade td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body class="bg-offwhite text-grafite">
    <div class="nao-imprimir mx-auto max-w-5xl space-y-4 p-4 md:p-8">
        <a href="{{ route('planilhas.supermercados', 'pragas') }}" class="text-sm font-medium text-azul">← Trocar supermercado</a>

        @if (session('ok'))
            <p class="rounded-xl bg-white px-4 py-2 text-sm shadow-sm">{{ session('ok') }}</p>
        @endif

        <div class="rounded-2xl bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-azul">Supermercado</p>
            <p class="text-xl font-bold">{{ $supermercado->nome }}</p>
        </div>

        <div class="grid gap-4 md:grid-cols-[1fr_auto]">
            <div class="rounded-2xl bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase text-grafite/50">Seções (uma folha por seção)</p>

                <div class="mt-3 divide-y divide-grafite/10">
                    @forelse ($secoes as $secao)
                        <div class="flex items-center justify-between gap-3 py-2">
                            <span class="font-semibold">{{ $secao->nome }}</span>
                            <form method="POST" action="{{ route('pragas.secoes.destroy', $secao) }}"
                                  onsubmit="return confirm('Remover a seção {{ addslashes($secao->nome) }}?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-sm font-medium text-red-500">Remover</button>
                            </form>
                        </div>
                    @empty
                        <p class="py-2 text-sm text-grafite/60">Nenhuma seção ainda. Adicione abaixo, por exemplo AÇOUGUE.</p>
                    @endforelse
                </div>

                <form method="POST" action="{{ route('pragas.secoes.store', $supermercado) }}" class="mt-4 flex gap-2">
                    @csrf
                    <input name="nome" value="{{ old('nome') }}" maxlength="120" placeholder="Nova seção (ex.: FLV)"
                           class="min-w-0 flex-1 rounded-xl bg-offwhite px-3 py-2 text-sm">
                    <button class="rounded-xl bg-azul px-4 text-sm font-semibold text-white">Adicionar</button>
                </form>
                @error('nome')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-start">
                <button onclick="window.print()" class="w-full rounded-xl bg-azul px-5 py-3 text-sm font-semibold text-white disabled:opacity-40 md:w-auto"
                        @disabled($secoes->isEmpty())>Imprimir / PDF</button>
            </div>
        </div>
    </div>

    <main id="folhas" class="flex flex-col items-center gap-6 px-2 pb-10 md:px-8">
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
                            <col style="width:110px">
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
                        <td class="lateral" rowspan="3"><span>LEGENDA</span></td>
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
</body>
</html>
