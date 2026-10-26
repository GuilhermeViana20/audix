<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 8mm 8mm; }
    body { font-family: Times, serif; font-size: 9px; color: #000; }
    .pagina { page-break-after: always; }
    .pagina:last-child { page-break-after: auto; }
    table.planilha { border-collapse: collapse; table-layout: fixed; }
    .planilha td, .planilha th { border: 1px solid #000; padding: 0; text-align: center; vertical-align: middle; }
    .planilha .sem-borda { border: 0; }
    .planilha .titulo { font-size: 14px; font-weight: bold; line-height: 1.25; }
    .planilha .topo td { height: 36px; vertical-align: top; padding-top: 4px; font-size: 9px; }
    .planilha .local td { height: 26px; vertical-align: middle; text-align: left; padding-left: 4px; font-size: 9px; }
    .planilha .valor { display: block; font-size: 10px; }
    .planilha thead th { font-weight: bold; height: 18px; font-size: 9px; }
    .planilha tbody td { height: 18px; font-size: 9px; }
    .planilha .faixa td { background: #d9d9d9; font-weight: bold; height: 18px; font-size: 9px; }
</style>
</head>
<body>
@php($LARG_NOME = 160)
@php($LARG_DIA = 28.39)
@php($LARG_TOTAL = $LARG_NOME + 31 * $LARG_DIA)
@php($TERCO = round($LARG_TOTAL / 3, 2))
@php($METADE = round($LARG_TOTAL / 2, 2))
@foreach ($paginas as $pagina)
    @php($secao = $pagina['secao'])
    <div class="pagina">

        {{-- Cabeçalho: logo ACCP | título | logo do supermercado --}}
        <table class="planilha" style="width:{{ $LARG_TOTAL }}px; margin-bottom:4px;">
            <colgroup>
                <col style="width:200px;">
                <col style="width:{{ $LARG_TOTAL - 400 }}px;">
                <col style="width:200px;">
            </colgroup>
            <tr>
                <td class="sem-borda" style="text-align:left; height:50px;">
                    @if (file_exists($acoopPath))
                        <img src="{{ $acoopPath }}" style="height:40px;">
                    @endif
                </td>
                <td class="sem-borda titulo">PLANILHA DE MONITORAMENTO<br>HIGIENE E LIMPEZA AMBIENTAL</td>
                <td class="sem-borda" style="text-align:right; height:50px;">
                    @if ($logoPath)
                        <img src="{{ $logoPath }}" style="height:40px;">
                    @else
                        <div style="display:inline-block; width:120px; border:1px dashed #777; font-size:7px; color:#555; height:40px; line-height:40px; text-align:center;">LOGO DO SUPERMERCADO</div>
                    @endif
                </td>
            </tr>
        </table>

        {{-- Topo: ENCARREGADO | SETOR | MÊS/ANO (33,3% cada) --}}
        <table class="planilha topo" style="width:{{ $LARG_TOTAL }}px;">
            <colgroup>
                <col style="width:{{ $TERCO }}px;">
                <col style="width:{{ $TERCO }}px;">
                <col style="width:{{ $LARG_TOTAL - 2 * $TERCO }}px;">
            </colgroup>
            <tr>
                <td><b>ENCARREGADO:</b></td>
                <td><b>SETOR:</b><span class="valor">{{ $secao->nome }}</span></td>
                <td><b>MÊS/ANO:</b></td>
            </tr>
        </table>

        {{-- LOCAL | DATA (50% cada), centralizados na vertical --}}
        <table class="planilha local" style="width:{{ $LARG_TOTAL }}px; margin-top:-1px;">
            <colgroup>
                <col style="width:{{ $METADE }}px;">
                <col style="width:{{ $LARG_TOTAL - $METADE }}px;">
            </colgroup>
            <tr>
                <td><b>LOCAL:</b></td>
                <td><b>DATA:</b></td>
            </tr>
        </table>

        <div style="height:8px;"></div>

        {{-- Grade: nome + 31 dias --}}
        <table class="planilha" style="width:{{ $LARG_TOTAL }}px;">
            <thead>
                <tr>
                    <th style="width:{{ $LARG_NOME }}px;">{{ $secao->titulo_primeira_coluna }}</th>
                    @for ($d = 1; $d <= 31; $d++)
                        <th style="width:{{ $LARG_DIA - 1 }}px;">{{ $d }}</th>
                    @endfor
                </tr>
            </thead>
            <tbody>
                @foreach ($pagina['linhas'] as $linha)
                    @if ($linha->tipo === 'grupo')
                        <tr class="faixa"><td colspan="32">{{ $linha->texto }}</td></tr>
                    @else
                        <tr>
                            <td style="width:{{ $LARG_NOME }}px;">{{ $linha->texto }}</td>
                            @for ($d = 1; $d <= 31; $d++)
                                <td style="width:{{ $LARG_DIA - 1 }}px;"></td>
                            @endfor
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
@endforeach
</body>
</html>
