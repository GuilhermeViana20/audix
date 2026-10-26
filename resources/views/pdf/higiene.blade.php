<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 8mm 8mm; }
    body { font-family: Times, serif; font-size: 9px; color: #000; }
    .pagina { page-break-after: always; }
    .pagina:last-child { page-break-after: auto; }
    table.planilha { border-collapse: collapse; table-layout: auto; }
    .planilha td, .planilha th { border: 1px solid #000; padding: 0; text-align: center; vertical-align: middle; }
    .planilha .sem-borda { border: 0; }
    .planilha .titulo { font-size: 14px; font-weight: bold; line-height: 1.25; }
    .planilha .topo td { height: 30px; vertical-align: top; padding-top: 3px; font-size: 9px; }
    .planilha .esq { text-align: left; padding-left: 4px; vertical-align: middle; height: 24px; }
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
@foreach ($paginas as $pagina)
    @php($secao = $pagina['secao'])
    <div class="pagina">
        {{-- Uma única tabela de 32 colunas (nome + 31 dias): cabeçalho, topo e grade ficam sempre alinhados --}}
        <table class="planilha" style="width:{{ $LARG_TOTAL }}px;">

            {{-- Cabeçalho: logo ACCP | título | logo do supermercado --}}
            <tr>
                <td class="sem-borda" colspan="5" style="text-align:left; height:50px;">
                    @if (file_exists($acoopPath))
                        <img src="{{ $acoopPath }}" style="height:40px;">
                    @endif
                </td>
                <td class="sem-borda titulo" colspan="22">PLANILHA DE MONITORAMENTO<br>HIGIENE E LIMPEZA AMBIENTAL</td>
                <td class="sem-borda" colspan="5" style="text-align:right; height:50px;">
                    @if ($logoPath)
                        <img src="{{ $logoPath }}" style="height:40px;">
                    @else
                        <div style="border:1px dashed #777; font-size:7px; color:#555; height:40px; line-height:40px; text-align:center;">LOGO DO SUPERMERCADO</div>
                    @endif
                </td>
            </tr>

            {{-- Topo: ENCARREGADO | SETOR | MÊS/ANO --}}
            <tr class="topo">
                <td colspan="11"><b>ENCARREGADO:</b></td>
                <td colspan="11"><b>SETOR:</b><span class="valor">{{ $secao->nome }}</span></td>
                <td colspan="10"><b>MÊS/ANO:</b></td>
            </tr>
            {{-- LOCAL | DATA --}}
            <tr class="topo">
                <td class="esq" colspan="16"><b>LOCAL:</b></td>
                <td class="esq" colspan="16"><b>DATA:</b></td>
            </tr>

            <tr><td colspan="32" class="sem-borda" style="height:8px; padding:0;"></td></tr>

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
