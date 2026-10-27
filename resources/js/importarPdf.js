import * as pdfjs from 'pdfjs-dist';
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjs.GlobalWorkerOptions.workerSrc = workerUrl;

const TOLERANCIA_Y = 2.5; // itens com a mesma altura (em pt) pertencem à mesma linha de texto
const IGNORAR = /PLANILHA DE MONITORAMENTO|ENCARREGADO|MÊS\/ANO|^LOCAL:|^DATA:|^SETOR:|^\d{1,2}$/i;

/** Lê os itens de texto de todas as páginas, com posição (x, y) e se estão em negrito. */
async function lerItens(arquivo) {
    const pdf = await pdfjs.getDocument({ data: await arquivo.arrayBuffer() }).promise;
    const paginas = [];
    for (let n = 1; n <= pdf.numPages; n++) {
        const pagina = await pdf.getPage(n);
        const conteudo = await pagina.getTextContent();
        paginas.push(conteudo.items
            .filter(i => i.str && i.str.trim())
            .map(i => ({
                texto: i.str.trim(),
                x: i.transform[4],
                y: pagina.view[3] - i.transform[5], // y cresce para baixo
                negrito: /bold|black|heavy/i.test(conteudo.styles[i.fontName]?.fontFamily || i.fontName || ''),
            })));
    }
    return paginas;
}

/** Agrupa os itens em linhas de texto, na ordem de cima para baixo. */
function agruparLinhas(itens) {
    const ordenados = [...itens].sort((a, b) => a.y - b.y || a.x - b.x);
    const linhas = [];
    for (const item of ordenados) {
        const atual = linhas[linhas.length - 1];
        if (atual && Math.abs(atual.y - item.y) <= TOLERANCIA_Y) {
            atual.itens.push(item);
        } else {
            linhas.push({ y: item.y, itens: [item] });
        }
    }
    return linhas.map(l => ({
        y: l.y,
        itens: l.itens.sort((a, b) => a.x - b.x),
    }));
}

/**
 * Lê a planilha de monitoramento (o mesmo layout do PDF gerado pelo sistema):
 * cada "SETOR:" começa uma seção; as linhas da grade têm o texto na primeira coluna,
 * e as faixas são as linhas com o texto em negrito.
 * Retorna [{ nome, titulo_primeira_coluna, linhas: [{ tipo, texto }] }].
 */
export async function lerPlanilhaPdf(arquivo) {
    const paginas = await lerItens(arquivo);
    const secoes = [];
    let secao = null;
    let colunaDias = null; // x do primeiro dia (1) da grade: tudo antes dele é a primeira coluna

    for (const itens of paginas) {
        for (const linha of agruparLinhas(itens)) {
            const texto = linha.itens.map(i => i.texto).join(' ');

            // Início de uma seção: "SETOR:" seguido do nome (na mesma linha ou na linha seguinte)
            const setor = linha.itens.find(i => /^SETOR:?$/i.test(i.texto));
            if (setor) {
                const depois = linha.itens.filter(i => i.x > setor.x + 2).map(i => i.texto).join(' ');
                const nome = depois.replace(/^SETOR:?\s*/i, '').trim();
                colunaDias = null;
                // Seção que continua na página seguinte repete o cabeçalho: segue na mesma seção
                const existente = nome && secoes.find(s => s.nome === nome);
                if (existente) {
                    secao = existente;
                } else {
                    secao = { nome, titulo_primeira_coluna: 'ÁREA DE ATENDIMENTO', linhas: [], _aguardaNome: !nome };
                    secoes.push(secao);
                }
                continue;
            }
            if (secao?._aguardaNome && !/LOCAL|DATA|MÊS/i.test(texto) && texto.trim()) {
                secao.nome = texto.trim();
                secao._aguardaNome = false;
                continue;
            }
            if (!secao) continue;

            // Cabeçalho da grade: a linha com o número 1 marca onde começam os dias
            const primeiroDia = linha.itens.find(i => i.texto === '1');
            if (primeiroDia && linha.itens.some(i => i.texto === '31' || i.texto === '2')) {
                colunaDias = primeiroDia.x;
                const primeira = linha.itens.filter(i => i.x < colunaDias - 1).map(i => i.texto).join(' ');
                if (primeira) secao.titulo_primeira_coluna = primeira;
                continue;
            }
            if (colunaDias === null) continue; // ainda no cabeçalho da seção

            const colunaTexto = linha.itens.filter(i => i.x < colunaDias - 1);
            const textoLinha = colunaTexto.map(i => i.texto).join(' ').trim();
            if (!textoLinha || IGNORAR.test(textoLinha)) continue;

            const negrito = colunaTexto.length > 0 && colunaTexto.every(i => i.negrito);
            secao.linhas.push({ tipo: negrito ? 'grupo' : 'linha', texto: textoLinha });
        }
    }

    return secoes
        .map(({ _aguardaNome, ...s }) => s)
        .filter(s => s.nome && s.linhas.length);
}
