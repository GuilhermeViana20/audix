<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 8mm 8mm; }
    body { font-family: Times, serif; font-size: 9px; color: #000; }
    .pagina { page-break-after: always; }
    .pagina:last-child { page-break-after: auto; }
    table { border-collapse: collapse; width: 1040px; table-layout: fixed; }
    td, th { border: 1px solid #000; padding: 0 2px; text-align: center; vertical-align: middle; overflow: hidden; }
    .sem-borda { border: 0; }
    .titulo { font-size: 14px; font-weight: bold; text-align: center; line-height: 1.25; }
    .logo-acoop { height: 34px; }
    .logo-mae { height: 34px; }
    .logo-box { border: 1px dashed #777; font-size: 7px; color: #555; text-align: center; height: 34px; width: 120px; line-height: 34px; }
    .cab td { height: 30px; vertical-align: top; padding-top: 3px; font-size: 9px; }
    .cab .esq { text-align: left; padding-left: 4px; vertical-align: middle; height: 20px; }
    .cab .valor { display: block; font-size: 10px; margin-top: 2px; }
    .largura td { border: 0; height: 0; padding: 0; line-height: 0; font-size: 0; }
    .espaco td { border: 0; height: 8px; padding: 0; }
    .grade th { font-weight: bold; height: 18px; font-size: 9px; }
    .grade td { height: 18px; font-size: 9px; }
    .grade tr.grupo td { background: #d9d9d9; font-weight: bold; height: 18px; font-size: 9px; }
</style>
</head>
<body>
@foreach ($paginas as $pagina)
    @php($secao = $pagina['secao'])
    <div class="pagina">

        <table style="margin-bottom:6px;">
            <tr>
                <td class="sem-borda" style="width:160px; text-align:left;">
                    @if (file_exists($acoopPath))
                        <img src="{{ $acoopPath }}" class="logo-acoop">
                    @endif
                </td>
                <td class="sem-borda titulo" style="width:720px;">PLANILHA DE MONITORAMENTO<br>HIGIENE E LIMPEZA AMBIENTAL</td>
                <td class="sem-borda" style="width:160px; text-align:right;">
                    @if ($logoPath)
                        <img src="{{ $logoPath }}" class="logo-mae">
                    @else
                        <div class="logo-box">LOGO DO SUPERMERCADO</div>
                    @endif
                </td>
            </tr>
        </table>

        <table class="grade" style="width:1040px;">
            {{-- larguras das colunas: 1 coluna de nomes (160px) + 31 dias --}}
            <colgroup>
                <col style="width:160px;">
                @for ($d = 1; $d <= 31; $d++)
                    <col style="width:28.39px;">
                @endfor
            </colgroup>

            {{-- Topo: 3 blocos (ENCARREGADO, SETOR, MÊS/ANO) --}}
            <tr class="cab">
                <td colspan="8"><b>ENCARREGADO:</b></td>
                <td colspan="12"><b>SETOR:</b><span class="valor">{{ $secao->nome }}</span></td>
                <td colspan="12"><b>MÊS/ANO:</b></td>
            </tr>
            {{-- Embaixo: 2 blocos (LOCAL, DATA) --}}
            <tr class="cab">
                <td colspan="13" class="esq"><b>LOCAL:</b></td>
                <td colspan="19" class="esq"><b>DATA:</b></td>
            </tr>

            <tr class="espaco"><td colspan="32"></td></tr>

            <thead>
                <tr>
                    <th>{{ $secao->titulo_primeira_coluna }}</th>
                    @for ($d = 1; $d <= 31; $d++)
                        <th>{{ $d }}</th>
                    @endfor
                </tr>
            </thead>
            <tbody>
                @foreach ($pagina['linhas'] as $linha)
                    @if ($linha->tipo === 'grupo')
                        <tr class="grupo"><td colspan="32">{{ $linha->texto }}</td></tr>
                    @else
                        <tr>
                            <td>{{ $linha->texto }}</td>
                            @for ($d = 1; $d <= 31; $d++)
                                <td></td>
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
