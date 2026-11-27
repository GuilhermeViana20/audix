// Interpreta os itens de texto (com posição) de uma planilha de monitoramento em PDF.
// Layout: cabeçalho com SETOR (nome na linha de baixo), grade com título na primeira coluna
// e os dias 1 a 31; linhas têm o texto na primeira coluna; faixas são textos centralizados
// sobre a grade, sem nada na primeira coluna.

const TOLERANCIA_Y = 2.5; // itens na mesma altura (pt) pertencem à mesma linha
const SOMENTE_DIA = /^\d{1,2}$/;
const TEXTO_LONGO = 28; // caracteres a partir dos quais o texto da primeira coluna pode quebrar
const RUIDO = /^(PLANILHA DE MONITORAMENTO|HIGIENE E LIMPEZA AMBIENTAL|ENCARREGADO|MÊS\/ANO|LOCAL|DATA)/i;
// Rodapé da ACCP abaixo da grade (nome/e-mail e endereço). Centralizado e sem nada na primeira
// coluna, seria lido como faixa; ao encontrá-lo, a grade da página terminou.
const RODAPE = /ACCP\s+ALIMENTOS|ACCPCONSULTORIA|HUMBERTO\s+MONTE/i;
const SECAO_SEM_SETOR = 'TEMPERATURA'; // nome da seção quando o PDF não tem SETOR no topo

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
    const obterSecao = nome => {
        let s = secoes.find(x => x.nome === nome);
        if (!s) {
            s = { nome, titulo_primeira_coluna: 'ÁREA DE ATENDIMENTO', linhas: [] };
            secoes.push(s);
        }
        return s;
    };

    for (const itens of paginas) {
        // Cada página começa com o cabeçalho; o que vem antes do SETOR não pertence à seção anterior
        secao = null;
        colunaDias = null;
        const linhas = agruparLinhas(itens);
        const paginaDeTemperatura = linhas.some(l => /CONTROLE DE TEMPERATURA/i.test(texto(l.itens)));

        for (let k = 0; k < linhas.length; k++) {
            const linha = linhas[k];

            // Rótulo SETOR: o nome está na mesma linha (à direita) ou na linha de baixo
            const rotulo = linha.itens.find(i => /^SETOR:?$/i.test(i.texto));
            if (rotulo) {
                // Limites da coluna SETOR: meio do caminho até os rótulos vizinhos (CLIENTE, MÊS/ANO...),
                // para não misturar o valor de outras colunas (ex.: nome do cliente) ao nome da seção
                const rotulos = linha.itens.filter(i => /:$/.test(i.texto));
                const pos = rotulos.indexOf(rotulo);
                const anterior = rotulos[pos - 1];
                const proximo = rotulos[pos + 1];
                const esquerda = anterior ? (anterior.x + rotulo.x) / 2 : -Infinity;
                // Sem rótulo à direita (temperatura: topo 2x2 com a legenda ao lado), a coluna tem a mesma
                // largura da anterior; assim a legenda não entra no nome
                const direita = proximo ? (rotulo.x + proximo.x) / 2
                    : anterior ? rotulo.x + (rotulo.x - anterior.x) / 2 : Infinity;
                const naColuna = i => i.x >= esquerda && i.x < direita;

                let nome = texto(semRotulo(linha.itens.filter(i => i.x > rotulo.x && i.x < direita)));
                // Na temperatura a legenda ao lado do topo intercala linhas: vale a primeira com texto na coluna
                for (let j = k + 1; !nome && j <= k + 3 && linhas[j]; j++) {
                    nome = texto(semRotulo(linhas[j].itens.filter(naColuna)));
                    if (nome) k = j;
                }
                colunaDias = null;
                secao = obterSecao(nome);
                continue;
            }

            // Cabeçalho da grade: linha com o dia 1 e o 31 (ou o 16, na temperatura dividida em duas
            // folhas). O que está à esquerda é o título.
            const dia = n => linha.itens.find(i => i.texto === String(n));
            const dia1 = dia(1);
            // Folha com os dias 17 a 31: repete as linhas da folha anterior (dias 1 a 16), então é ignorada
            if (!dia1 && dia(17) && dia(31)) {
                colunaDias = null;
                continue;
            }
            const cabecalhoDaGrade = dia1 && (dia(31) || dia(16));
            // Topo sem SETOR (temperatura: os setores vão nas faixas): a grade entra numa seção só,
            // que o usuário renomeia na importação
            if (!secao && cabecalhoDaGrade && paginaDeTemperatura) {
                secao = obterSecao(SECAO_SEM_SETOR);
            }
            if (!secao) continue;
            // Com a grade já começada, uma linha com os dias é faixa (temperatura: a faixa repete o
            // cabeçalho, com o texto na primeira coluna)
            if (cabecalhoDaGrade && colunaDias !== null && paginaDeTemperatura) {
                const textoFaixa = texto(linha.itens.filter(i => i.x < colunaDias - 1));
                if (textoFaixa) secao.linhas.push({ tipo: 'grupo', texto: textoFaixa });
                continue;
            }
            if (cabecalhoDaGrade) {
                colunaDias = dia1.x;
                const titulo = texto(linha.itens.filter(i => i.x < colunaDias - 1));
                if (paginaDeTemperatura && secao.linhas.length) {
                    // Temperatura: a folha seguinte da seção pode começar numa faixa nova, que fica no
                    // cabeçalho da grade; se não for a mesma faixa da folha anterior, volta a ser faixa
                    const ultimaFaixa = secao.linhas.filter(l => l.tipo === 'grupo').at(-1);
                    const repetido = titulo === (ultimaFaixa?.texto ?? secao.titulo_primeira_coluna);
                    if (titulo && !repetido) secao.linhas.push({ tipo: 'grupo', texto: titulo });
                } else if (titulo) {
                    secao.titulo_primeira_coluna = titulo;
                }
                continue;
            }
            if (colunaDias === null) continue;

            if (RODAPE.test(texto(linha.itens))) {
                colunaDias = null; // o que vier depois na página não é linha da grade
                continue;
            }

            const primeira = linha.itens.filter(i => i.x < colunaDias - 1);
            const centro = linha.itens.filter(i => i.x >= colunaDias - 1 && !SOMENTE_DIA.test(i.texto));
            const textoPrimeira = texto(primeira);

            let item = null;
            if (textoPrimeira) {
                if (RUIDO.test(textoPrimeira)) continue;
                const negrito = primeira.every(i => i.negrito);
                item = { tipo: negrito ? 'grupo' : 'linha', texto: textoPrimeira };
            } else if (centro.length) {
                item = { tipo: 'grupo', texto: texto(centro) };
            }
            if (!item) continue;

            // Texto longo na primeira coluna: pode ter quebrado em duas linhas, vale conferir
            if (textoPrimeira.length >= TEXTO_LONGO) {
                item.aviso = 'Texto longo, pode estar quebrado em duas linhas';
            }
            secao.linhas.push(item);
        }
    }

    // Faixa sem nenhuma linha abaixo dela (ou seguida de outra faixa): marca para revisão
    for (const s of secoes) {
        s.linhas.forEach((l, i) => {
            const proxima = s.linhas[i + 1];
            if (l.tipo === 'grupo' && (!proxima || proxima.tipo === 'grupo')) {
                l.aviso = l.aviso || 'Faixa sem linhas abaixo';
            }
        });
    }

    return secoes.filter(s => s.linhas.length);
}
