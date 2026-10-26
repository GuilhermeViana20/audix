<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 8mm 8mm; }
    body { font-family: Times, serif; font-size: 9px; color: #000; }
    .pagina { page-break-after: always; }
    .pagina:last-child { page-break-after: auto; }
    td, th { border: 1px solid #000; padding: 0 2px; text-align: center; vertical-align: middle; }
    .sem-borda { border: 0; }
    .titulo { font-size: 14px; font-weight: bold; text-align: center; line-height: 1.25; }
    .logo-acoop { height: 40px; }
    .logo-mae { height: 40px; }
    .logo-box { border: 1px dashed #777; font-size: 7px; color: #555; text-align: center; height: 40px; width: 120px; line-height: 40px; }
    .cab td { height: 30px; vertical-align: top; padding-top: 3px; font-size: 9px; }
    .cab .esq { text-align: left; padding-left: 4px; vertical-align: middle; height: 20px; }
    .cab .valor { display: block; font-size: 10px; margin-top: 2px; }
    .espaco td { border: 0; height: 8px; padding: 0; }
    .grade th { font-weight: bold; height: 18px; font-size: 9px; }
    .grade td { height: 18px; font-size: 9px; }
    .grade td.nome { text-align: center; }
    .grade tr.grupo td { background: #d9d9d9; font-weight: bold; height: 18px; font-size: 9px; }
</style>
</head>
<body>
@php($LARG_NOME = 160)
@php($LARG_DIA = 28.39)
@php($LARG_TOTAL = $LARG_NOME + 31 * $LARG_DIA)
@foreach ($paginas as $pagina)
    @php($secao = $pagina['secao'])
    <div class="pagina">

        <table style="width:{{ $LARG_TOTAL }}px; margin-bottom:6px; border-collapse:collapse;">
            <tr>
                <td class="sem-borda" style="width:200px; text-align:left;">
                    @if (file_exists($acoopPath))
                        <img src="{{ $acoopPath }}" class="logo-acoop">
                    @endif
                </td>
                <td class="sem-borda titulo" style="width:{{ $LARG_TOTAL - 400 }}px;">PLANILHA DE MONITORAMENTO<br>HIGIENE E LIMPEZA AMBIENTAL</td>
                <td class="sem-borda" style="width:200px; text-align:right;">
                    @if ($logoPath)
                        <img src="{{ $logoPath }}" class="logo-mae">
                    @else
                        <div class="logo-box">LOGO DO SUPERMERCADO</div>
                    @endif
                </td>
            </tr>
        </table>

        <table class="grade" style="width:{{ $LARG_TOTAL }}px; border-collapse:collapse; table-layout:auto;">
            {{-- Topo: ENCARREGADO | SETOR | MÊS/ANO (cada um ~1/3) --}}
            <tr class="cab">
                <td style="width:{{ round($LARG_TOTAL / 3, 2) }}px;"><b>ENCARREGADO:</b></td>
                <td style="width:{{ round($LARG_TOTAL / 3, 2) }}px;"><b>SETOR:</b><span class="valor">{{ $secao->nome }}</span></td>
                <td style="width:{{ $LARG_TOTAL - 2 * round($LARG_TOTAL / 3, 2) }}px;"><b>MÊS/ANO:</b></td>
            </tr>
            {{-- Embaixo: LOCAL | DATA (cada um ~1/2) --}}
            <tr class="cab">
                <td class="esq" colspan="1" style="width:{{ round($LARG_TOTAL / 2, 2) }}px;"><b>LOCAL:</b></td>
                <td class="esq" colspan="2" style="width:{{ $LARG_TOTAL - round($LARG_TOTAL / 2, 2) }}px;"><b>DATA:</b></td>
            </tr>

            <tr class="espaco"><td colspan="3"></td></tr>
        </table>

        <table class="grade" style="width:{{ $LARG_TOTAL }}px; border-collapse:collapse; table-layout:auto; margin-top:0;">
            <thead>
                <tr>
                    <th style="width:{{ $LARG_NOME }}px;">{{ $secao->titulo_primeira_coluna }}</th>
                    @for ($d = 1; $d <= 31; $d++)
                        <th style="width:{{ $LARG_DIA }}px;">{{ $d }}</th>
                    @endfor
                </tr>
            </thead>
            <tbody>
                @foreach ($pagina['linhas'] as $linha)
                    @if ($linha->tipo === 'grupo')
                        <tr class="grupo"><td colspan="32">{{ $linha->texto }}</td></tr>
                    @else
                        <tr>
                            <td class="nome" style="width:{{ $LARG_NOME }}px;">{{ $linha->texto }}</td>
                            @for ($d = 1; $d <= 31; $d++)
                                <td style="width:{{ $LARG_DIA }}px;"></td>
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
