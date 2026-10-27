// Interpreta os itens de texto (com posição) de uma planilha de monitoramento em PDF.
// Layout: cabeçalho com SETOR (nome na linha de baixo), grade com título na primeira coluna
// e os dias 1 a 31; linhas têm o texto na primeira coluna; faixas são textos centralizados
// sobre a grade, sem nada na primeira coluna.

const TOLERANCIA_Y = 2.5; // itens na mesma altura (pt) pertencem à mesma linha
const SOMENTE_DIA = /^\d{1,2}$/;
const RUIDO = /^(PLANILHA DE MONITORAMENTO|HIGIENE E LIMPEZA AMBIENTAL|ENCARREGADO|MÊS\/ANO|LOCAL|DATA)/i;

export function agruparLinhas(itens) {
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
    return linhas.map(l => ({ y: l.y, itens: l.itens.sort((a, b) => a.x - b.x) }));
}

const texto = itens => itens.map(i => i.texto).join(' ').replace(/\s+/g, ' ').trim();
const semRotulo = itens => itens.filter(i => !/:$/.test(i.texto));

/** paginas: array (uma por página) de itens { texto, x, y, negrito }. */
export function interpretarPlanilha(paginas) {
    const secoes = [];
    let secao = null;
    let colunaDias = null; // x do dia 1: tudo à esquerda dele é a primeira coluna

    for (const itens of paginas) {
        // Cada página começa com o cabeçalho; o que vem antes do SETOR não pertence à seção anterior
        secao = null;
        colunaDias = null;
        const linhas = agruparLinhas(itens);

        for (let k = 0; k < linhas.length; k++) {
            const linha = linhas[k];

            // Rótulo SETOR: o nome está na mesma linha (à direita) ou na linha de baixo
            const rotulo = linha.itens.find(i => /^SETOR:?$/i.test(i.texto));
            if (rotulo) {
                let nome = texto(semRotulo(linha.itens.filter(i => i.x > rotulo.x)));
                if (!nome && linhas[k + 1]) {
                    nome = texto(semRotulo(linhas[k + 1].itens));
                    k++;
                }
                colunaDias = null;
                secao = secoes.find(s => s.nome === nome) || null;
                if (!secao) {
                    secao = { nome, titulo_primeira_coluna: 'ÁREA DE ATENDIMENTO', linhas: [] };
                    secoes.push(secao);
                }
                continue;
            }
            if (!secao) continue;

            // Cabeçalho da grade: linha com o dia 1 (e o 31). O que está à esquerda é o título.
            const dia1 = linha.itens.find(i => i.texto === '1');
            if (dia1 && linha.itens.some(i => i.texto === '31')) {
                colunaDias = dia1.x;
                const titulo = texto(linha.itens.filter(i => i.x < colunaDias - 1));
                if (titulo) secao.titulo_primeira_coluna = titulo;
                continue;
            }
            if (colunaDias === null) continue;

            const primeira = linha.itens.filter(i => i.x < colunaDias - 1);
            const centro = linha.itens.filter(i => i.x >= colunaDias - 1 && !SOMENTE_DIA.test(i.texto));
            const textoPrimeira = texto(primeira);

            if (textoPrimeira) {
                if (RUIDO.test(textoPrimeira)) continue;
                const negrito = primeira.every(i => i.negrito);
                secao.linhas.push({ tipo: negrito ? 'grupo' : 'linha', texto: textoPrimeira });
            } else if (centro.length) {
                secao.linhas.push({ tipo: 'grupo', texto: texto(centro) });
            }
        }
    }

    return secoes.filter(s => s.linhas.length);
}
