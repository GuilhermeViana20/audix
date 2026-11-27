// Exportação das planilhas para Word (.docx), com o mesmo layout da folha A4 paisagem da pré-visualização.
// O arquivo serve para ajustes pontuais no Word; o layout segue as medidas em px das folhas HTML.
import {
    AlignmentType,
    BorderStyle,
    Document,
    Footer,
    HeightRule,
    ImageRun,
    LineRuleType,
    Packer,
    PageOrientation,
    Paragraph,
    ShadingType,
    Table,
    TableCell,
    TableLayoutType,
    TableRow,
    TextRun,
    VerticalAlign,
    WidthType,
} from 'docx';

// ---------- Medidas ----------
// A folha HTML mede 1123px de largura com 36px de margem de cada lado: 1051px de conteúdo.
// No Word: A4 paisagem (16838 twips) com margens de 8mm (454 twips) => 15930 twips de conteúdo.
const MARGEM = 454;
const LARGURA = 16838 - 2 * MARGEM;
const LARGURA_FOLHA_PX = 1051;
const twips = px => Math.round(px * 15); // 1px = 0,75pt = 15 twips
const larguraPx = px => Math.round((px / LARGURA_FOLHA_PX) * LARGURA);
const meioPonto = px => Math.round(px * 1.5); // tamanho da fonte em meio-pontos (1px = 0,75pt)

const CINZA = 'D9D9D9';
const FONTE = 'Times New Roman';
const LINHA = { style: BorderStyle.SINGLE, size: 4, color: '000000' };
const SEM_LINHA = { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' };
const BORDAS = { top: LINHA, bottom: LINHA, left: LINHA, right: LINHA, insideHorizontal: LINHA, insideVertical: LINHA };
const SEM_BORDAS = {
    top: SEM_LINHA, bottom: SEM_LINHA, left: SEM_LINHA, right: SEM_LINHA,
    insideHorizontal: SEM_LINHA, insideVertical: SEM_LINHA,
};

// ---------- Blocos básicos ----------
function texto(conteudo, { tamanho = 9, negrito = false, cor, alinhamento = AlignmentType.CENTER, quebras } = {}) {
    // `quebras`: lista de trechos separados por quebra de linha
    const trechos = quebras || [conteudo ?? ''];
    return new Paragraph({
        alignment: alinhamento,
        children: trechos.map((t, i) => new TextRun({
            text: String(t ?? ''),
            bold: negrito,
            size: meioPonto(tamanho),
            color: cor,
            font: FONTE,
            break: i > 0 ? 1 : 0,
        })),
    });
}

// Parágrafo vazio de altura exata: separa tabelas (no Word, tabelas coladas se fundem) e reproduz os espaços da folha
function espaco(px) {
    return new Paragraph({
        spacing: { before: 0, after: 0, line: Math.max(twips(px), 20), lineRule: LineRuleType.EXACT },
        children: [new TextRun({ text: '', size: 2 })],
    });
}

function celula(filhos, { largura, fundo, alinhamentoVertical = VerticalAlign.CENTER, colunas, linhas, bordas, margemEsquerda = 0, margemTopo = 0 } = {}) {
    return new TableCell({
        children: Array.isArray(filhos) ? filhos : [filhos],
        width: largura ? { size: largura, type: WidthType.DXA } : undefined,
        shading: fundo ? { fill: fundo, type: ShadingType.CLEAR, color: 'auto' } : undefined,
        verticalAlign: alinhamentoVertical,
        columnSpan: colunas,
        rowSpan: linhas,
        borders: bordas,
        margins: { top: margemTopo, bottom: 0, left: margemEsquerda, right: margemEsquerda ? 40 : 0 },
    });
}

// No Word a borda (0,5pt = 10 twips) soma à altura da linha; no HTML (border-collapse) não.
// Sem descontá-la, uma folha cheia (28 linhas) passaria de uma página.
const ESPESSURA_BORDA = 10;

function linhaTabela(celulas, alturaPx, { exata = false, cabecalho = false } = {}) {
    return new TableRow({
        children: celulas,
        height: { value: Math.max(twips(alturaPx) - ESPESSURA_BORDA, 0), rule: exata ? HeightRule.EXACT : HeightRule.ATLEAST },
        cantSplit: true,
        tableHeader: cabecalho,
    });
}

function tabela(linhas, larguras, { bordas = BORDAS } = {}) {
    return new Table({
        rows: linhas,
        columnWidths: larguras,
        width: { size: larguras.reduce((a, b) => a + b, 0), type: WidthType.DXA },
        layout: TableLayoutType.FIXED,
        borders: bordas,
    });
}

// Divide `total` em colunas: a primeira com `primeira`, as demais iguais (a última absorve o arredondamento)
function colunasDaGrade(total, primeira, quantidade) {
    const resto = total - primeira;
    const cada = Math.floor(resto / quantidade);
    const lista = [primeira, ...Array(quantidade).fill(cada)];
    lista[lista.length - 1] += resto - cada * quantidade;
    return lista;
}

// ---------- Imagens ----------
const TIPOS_IMAGEM = { 'image/png': 'png', 'image/jpeg': 'jpg', 'image/gif': 'gif', 'image/bmp': 'bmp' };
const cacheImagens = new Map();

// Na folha as imagens têm no máximo ~50px de altura; 200px dão boa nitidez na impressão sem inchar o arquivo
const ALTURA_MAX_IMAGEM_PX = 200;

// Redesenha a imagem em PNG (reduzida, se preciso); devolve null se o canvas não estiver disponível
async function redesenharEmPng(bitmap, largura, altura) {
    const canvas = document.createElement('canvas');
    const contexto = canvas.getContext?.('2d');
    if (!contexto) return null;
    canvas.width = largura;
    canvas.height = altura;
    contexto.drawImage(bitmap, 0, 0, largura, altura);
    const png = await new Promise(ok => canvas.toBlob(ok, 'image/png'));
    return png ? png.arrayBuffer() : null;
}

// Baixa a imagem e devolve { data, type, largura, altura }. Imagens grandes são reduzidas e
// formatos que o Word não aceita (webp, svg…) viram PNG. Sem imagem, devolve null.
function carregarImagem(url) {
    if (!url) return Promise.resolve(null);
    if (!cacheImagens.has(url)) {
        cacheImagens.set(url, (async () => {
            try {
                const resposta = await fetch(url, { credentials: 'same-origin' });
                if (!resposta.ok) return null;
                const blob = await resposta.blob();
                const bitmap = await createImageBitmap(blob);
                const { width: largura, height: altura } = bitmap;
                const escala = Math.min(1, ALTURA_MAX_IMAGEM_PX / altura);
                const tipoOriginal = TIPOS_IMAGEM[blob.type];
                let imagem = { data: await blob.arrayBuffer(), type: tipoOriginal, largura, altura };
                if (escala < 1 || !tipoOriginal) {
                    const novaLargura = Math.max(1, Math.round(largura * escala));
                    const novaAltura = Math.max(1, Math.round(altura * escala));
                    const png = await redesenharEmPng(bitmap, novaLargura, novaAltura);
                    if (png) imagem = { data: png, type: 'png', largura: novaLargura, altura: novaAltura };
                }
                bitmap.close?.();
                return imagem.type ? imagem : null;
            } catch {
                return null;
            }
        })());
    }
    return cacheImagens.get(url);
}

// Imagem com altura máxima (e largura máxima), mantendo a proporção
function imagem(img, alturaMax, larguraMax) {
    const escala = Math.min(alturaMax / img.altura, larguraMax / img.largura);
    return new ImageRun({
        type: img.type,
        data: img.data,
        transformation: { width: Math.round(img.largura * escala), height: Math.round(img.altura * escala) },
    });
}

// ---------- Partes comuns das folhas ----------
// Logo da ACCP à esquerda, título no centro e logo do supermercado à direita (linha de 50px)
function cabecalhoLogos({ logoAccp, logoSupermercado, titulo, tamanhoTitulo = 14 }) {
    const lateral = larguraPx(200);
    const centro = LARGURA - 2 * lateral;
    const logoDireita = logoSupermercado
        ? new Paragraph({ alignment: AlignmentType.RIGHT, children: [imagem(logoSupermercado, 40, 195)] })
        : texto('LOGO DO SUPERMERCADO', { tamanho: 7, cor: '555555', alinhamento: AlignmentType.RIGHT });
    const logoEsquerda = logoAccp
        ? new Paragraph({ alignment: AlignmentType.LEFT, children: [imagem(logoAccp, 40, 195)] })
        : new Paragraph({});
    return tabela([
        linhaTabela([
            celula(logoEsquerda, { largura: lateral }),
            celula(texto(null, { tamanho: tamanhoTitulo, negrito: true, quebras: titulo }), { largura: centro }),
            celula(logoDireita, { largura: lateral }),
        ], 50),
    ], [lateral, centro, lateral], { bordas: SEM_BORDAS });
}

// Campo do topo: rótulo em negrito e, abaixo, o valor (como CLIENTE e SETOR na folha)
function campo(rotulo, valor, { tamanho = 9, tamanhoValor = 10 } = {}) {
    const filhos = [texto(rotulo, { tamanho, negrito: true })];
    if (valor !== undefined) filhos.push(texto(valor, { tamanho: tamanhoValor }));
    return filhos;
}

function rodape() {
    return new Footer({
        children: [
            texto('ACCP Alimentos & Tecnologia | contato@accpconsultoria.com.br', { tamanho: 9, negrito: true, cor: '7A7A7A' }),
            texto('Av. Humberto Monte, 2929, Sala 501, Torre Sul – Pici, Fortaleza/CE', { tamanho: 8, cor: '9A9A9A' }),
        ],
    });
}

// Cada folha vira uma seção do Word: começa em página nova, em A4 paisagem, com o rodapé da ACCP
function secaoDaFolha(filhos) {
    return {
        properties: {
            page: {
                size: { orientation: PageOrientation.LANDSCAPE, width: 11906, height: 16838 },
                // O rodapé (2 linhas) fica entre 220 e ~560 twips da borda de baixo
                margin: { top: MARGEM, bottom: 600, left: MARGEM, right: MARGEM, header: 0, footer: 220 },
            },
        },
        footers: { default: rodape() },
        // O Word sempre termina a seção num parágrafo: ele fica com 1px para não empurrar a grade para outra página
        children: [...filhos, espaco(1)],
    };
}

async function baixarDocx(secoes, nomeArquivo) {
    const documento = new Document({
        creator: 'Audix',
        title: nomeArquivo,
        styles: {
            default: {
                document: {
                    run: { font: FONTE, size: meioPonto(9) },
                    paragraph: { spacing: { before: 0, after: 0, line: 240 } },
                },
            },
        },
        sections: secoes,
    });
    const blob = await Packer.toBlob(documento);
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `${(nomeArquivo || 'Planilha').replace(/[\\/:*?"<>|]+/g, '-')}.docx`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}

// ---------- Higiene e temperatura ----------
// Medidas da grade, as mesmas da folha HTML. Na temperatura (16 dias por folha) as células e a fonte são maiores.
const GRADE = {
    higiene: { primeiraColuna: 160, fonte: 8.5, cabecalho: 20 },
    // Na temperatura cada faixa repete o cabeçalho: texto na primeira coluna e os dias
    temperatura: { primeiraColuna: 130, fonte: 11, cabecalho: 26, faixaComDias: true },
};

function gradeDeLinhas(folha, dias, alturaDaLinha, medidas) {
    const larguras = colunasDaGrade(LARGURA, larguraPx(medidas.primeiraColuna), dias.length);
    const fonte = { tamanho: medidas.fonte };
    const celulasComDias = titulo => [
        celula(texto(titulo, { ...fonte, negrito: true }), { largura: larguras[0], fundo: CINZA }),
        ...dias.map((d, i) => celula(texto(d, { ...fonte, negrito: true }), { largura: larguras[i + 1], fundo: CINZA })),
    ];
    const cabecalho = linhaTabela(celulasComDias(folha.titulo), medidas.cabecalho, { cabecalho: true });
    const faixa = l => (medidas.faixaComDias
        ? linhaTabela(celulasComDias(l.texto), alturaDaLinha(l))
        : linhaTabela([celula(texto(l.texto, { ...fonte, negrito: true }), { fundo: CINZA, colunas: dias.length + 1 })], alturaDaLinha(l)));
    const corpo = folha.linhas.map(l => (l.tipo === 'grupo'
        ? faixa(l)
        : linhaTabela([
            celula(texto(l.texto, fonte), { largura: larguras[0] }),
            ...dias.map((d, i) => celula(new Paragraph({}), { largura: larguras[i + 1] })),
        ], alturaDaLinha(l))));
    return tabela([cabecalho, ...corpo], larguras);
}

// Higiene: CLIENTE | ENCARREGADO | SETOR | MÊS/ANO e, abaixo, LOCAL | DATA
function topoHigiene(folha, nomeSocial) {
    const quarto = Math.floor(LARGURA / 4);
    const larguras = [quarto, quarto, quarto, LARGURA - 3 * quarto];
    const topo = { alinhamentoVertical: VerticalAlign.TOP, margemTopo: twips(4) };
    const local = { alinhamentoVertical: VerticalAlign.CENTER, margemEsquerda: twips(4) };
    const esquerda = { tamanho: 9, negrito: true, alinhamento: AlignmentType.LEFT };
    return tabela([
        linhaTabela([
            celula(campo('CLIENTE:', (nomeSocial || '').toUpperCase()), { largura: larguras[0], ...topo }),
            celula(campo('ENCARREGADO:'), { largura: larguras[1], ...topo }),
            celula(campo('SETOR:', folha.secao.nome), { largura: larguras[2], ...topo }),
            celula(campo('MÊS/ANO:'), { largura: larguras[3], ...topo }),
        ], 36),
        linhaTabela([
            celula(texto('LOCAL:', esquerda), { colunas: 2, largura: larguras[0] + larguras[1], ...local }),
            celula(texto('DATA:', esquerda), { colunas: 2, largura: larguras[2] + larguras[3], ...local }),
        ], 26),
    ], larguras);
}

// Temperatura: CLIENTE | SETOR | RESPONSÁVEL | PERÍODO, 25% cada, numa linha só
function topoTemperatura(folha, nomeSocial) {
    const quarto = Math.floor(LARGURA / 4);
    const larguras = [quarto, quarto, quarto, LARGURA - 3 * quarto];
    const topo = { alinhamentoVertical: VerticalAlign.TOP, margemTopo: twips(4) };
    return tabela([
        linhaTabela([
            celula(campo('CLIENTE:', (nomeSocial || '').toUpperCase()), { largura: larguras[0], ...topo }),
            celula(campo('SETOR:', folha.secao.nome), { largura: larguras[1], ...topo }),
            celula(campo('RESPONSÁVEL:'), { largura: larguras[2], ...topo }),
            celula(campo('PERÍODO:'), { largura: larguras[3], ...topo }),
        ], 36),
    ], larguras);
}

// Temperatura: a legenda dos produtos tem uma folha só, no começo do arquivo
function tabelaLegenda(legenda) {
    const metade = Math.floor(LARGURA / 2);
    const larguras = [metade, LARGURA - metade];
    const item = (conteudo, largura, negrito, fundo) => celula(
        texto(conteudo, { tamanho: 13, negrito }),
        { largura, fundo, margemEsquerda: twips(8) },
    );
    return tabela([
        linhaTabela([item('PRODUTOS', larguras[0], true, CINZA), item('TEMPERATURA', larguras[1], true, CINZA)], 30, { cabecalho: true }),
        ...legenda.map(([produto, temperatura]) => linhaTabela([
            item(produto, larguras[0], false),
            item(temperatura, larguras[1], false),
        ], 30)),
    ], larguras);
}

// ---------- Temperatura, modelo equipamentos ----------
// Topo 2x2 (CLIENTE | SETOR / MÊS/ANO | ENCARREGADO) com 60% e a legenda menor com 40%, lado a lado
function topoEquipamentos(folha, nomeSocial, legenda) {
    const vao = larguraPx(8);
    const larguraTopo = Math.round((LARGURA - vao) * 0.6);
    const larguraLegenda = LARGURA - vao - larguraTopo;

    const meio = Math.floor(larguraTopo / 2);
    const largurasTopo = [meio, larguraTopo - meio];
    const noTopo = { alinhamentoVertical: VerticalAlign.TOP, margemTopo: twips(4) };
    // Cada linha do topo tem metade da altura da legenda (cabeçalho + 5 itens, o último em duas linhas)
    const topo = tabela([
        linhaTabela([
            celula(campo('CLIENTE:', (nomeSocial || '').toUpperCase()), { largura: largurasTopo[0], ...noTopo }),
            celula(campo('SETOR:', folha.titulo), { largura: largurasTopo[1], ...noTopo }),
        ], 44),
        linhaTabela([
            celula(campo('MÊS/ANO:'), { largura: largurasTopo[0], ...noTopo }),
            celula(campo('ENCARREGADO:'), { largura: largurasTopo[1], ...noTopo }),
        ], 44),
    ], largurasTopo);

    const produto = Math.round(larguraLegenda * 0.45);
    const largurasLegenda = [produto, larguraLegenda - produto];
    const item = (conteudo, largura, negrito, fundo) => celula(
        texto(conteudo, { tamanho: 9, negrito, alinhamento: AlignmentType.LEFT }),
        { largura, fundo, margemEsquerda: twips(4) },
    );
    const tabelaLegenda = tabela([
        linhaTabela([item('PRODUTOS', largurasLegenda[0], true, CINZA), item('TEMPERATURA', largurasLegenda[1], true, CINZA)], 13),
        ...legenda.map(([nome, temperatura]) => linhaTabela([
            item(nome, largurasLegenda[0], false),
            item(temperatura, largurasLegenda[1], false),
        ], 13)),
    ], largurasLegenda);

    // Tabela externa sem bordas só para posicionar os blocos; a célula precisa terminar em parágrafo
    const fimDaCelula = () => new Paragraph({ spacing: { line: 20, lineRule: LineRuleType.EXACT }, children: [] });
    return tabela([
        linhaTabela([
            celula([topo, fimDaCelula()], { largura: larguraTopo, alinhamentoVertical: VerticalAlign.TOP }),
            celula(new Paragraph({}), { largura: vao }),
            celula([tabelaLegenda, fimDaCelula()], { largura: larguraLegenda, alinhamentoVertical: VerticalAlign.TOP }),
        ], 0),
    ], [larguraTopo, vao, larguraLegenda], { bordas: SEM_BORDAS });
}

// DATA | HORA | um equipamento por coluna | ASSINATURA, com os dias da folha (1 a 16 ou 17 a 31) nas linhas
function gradeEquipamentos(folha) {
    const quantidade = Math.max(1, folha.equipamentos.length);
    const [data, hora, assinatura] = [larguraPx(50), larguraPx(70), larguraPx(170)];
    const resto = LARGURA - data - hora - assinatura;
    const cada = Math.floor(resto / quantidade);
    const largurasEquip = Array(quantidade).fill(cada);
    largurasEquip[quantidade - 1] += resto - cada * quantidade;
    const larguras = [data, hora, ...largurasEquip, assinatura];

    const fonte = { tamanho: 13 };
    const cinza = (conteudo, largura, extra = {}) => celula(texto(conteudo, { ...fonte, negrito: true }), { largura, fundo: CINZA, ...extra });
    const nomes = folha.equipamentos.length ? folha.equipamentos.map(e => e.texto) : [''];
    const cabecalho1 = linhaTabela([
        cinza('DATA', data, { linhas: 2 }),
        cinza('HORA', hora, { linhas: 2 }),
        cinza('TEMPERATURA DOS EQUIPAMENTOS', resto, { colunas: quantidade }),
        cinza('ASSINATURA', assinatura, { linhas: 2 }),
    ], 20, { cabecalho: true });
    // Na segunda linha do cabeçalho, as células mescladas na vertical continuam (vMerge) pelo docx
    const cabecalho2 = linhaTabela(
        nomes.map((nome, i) => celula(texto(nome, { tamanho: 11 }), { largura: largurasEquip[i] })),
        32, { cabecalho: true },
    );
    const dias = (folha.dias ?? Array.from({ length: 31 }, (_, i) => i + 1)).map(dia => linhaTabela([
        celula(texto(String(dia).padStart(2, '0'), fonte), { largura: data }),
        celula(new Paragraph({}), { largura: hora }),
        ...largurasEquip.map(l => celula(new Paragraph({}), { largura: l })),
        celula(new Paragraph({}), { largura: assinatura }),
    ], 26, { exata: true }));
    return tabela([cabecalho1, cabecalho2, ...dias], larguras);
}

/**
 * Exporta as folhas de higiene ou temperatura.
 * @param {object} opcoes
 * @param {'higiene'|'temperatura'} opcoes.tipo
 * @param {string} opcoes.nomePlanilha  nome da planilha (título da higiene)
 * @param {string|null} opcoes.nomeSocial
 * @param {string|null} opcoes.logoUrl
 * @param {Array} opcoes.folhas          folhas já paginadas: { secao, titulo, linhas, grades } ou,
 *                                       no modelo equipamentos, { secao, titulo, equipamentos }
 * @param {Function} opcoes.alturaDaLinha  altura (px) de cada linha/faixa, a mesma da pré-visualização
 * @param {Array} [opcoes.legenda]       legenda de temperaturas (folha própria no modelo padrão)
 * @param {'padrao'|'equipamentos'} [opcoes.modelo]  modelo da folha de temperatura
 * @param {Array} [opcoes.legendaCurta]  legenda menor, em todas as folhas do modelo equipamentos
 * @param {string} opcoes.nomeArquivo
 */
export async function exportarPlanilhaDocx({ tipo, nomePlanilha, nomeSocial, logoUrl, folhas, alturaDaLinha, legenda = [], modelo = 'padrao', legendaCurta = [], nomeArquivo }) {
    const temperatura = tipo === 'temperatura';
    const equipamentos = temperatura && modelo === 'equipamentos';
    const [logoAccp, logoSupermercado] = await Promise.all([carregarImagem('/images/acoop.png'), carregarImagem(logoUrl)]);
    const titulo = temperatura
        ? ['CONTROLE DE TEMPERATURA']
        : ['PLANILHA DE MONITORAMENTO', (nomePlanilha || 'HIGIENE E LIMPEZA AMBIENTAL').toUpperCase()];

    const secoes = folhas.map(folha => secaoDaFolha(equipamentos
        ? [
            cabecalhoLogos({ logoAccp, logoSupermercado, titulo }),
            espaco(1),
            topoEquipamentos(folha, nomeSocial, folha.legendaCurta ?? legendaCurta),
            espaco(10),
            gradeEquipamentos(folha),
        ]
        : [
            cabecalhoLogos({ logoAccp, logoSupermercado, titulo }),
            espaco(1),
            temperatura ? topoTemperatura(folha, nomeSocial) : topoHigiene(folha, nomeSocial),
            // Uma grade por intervalo de dias (na temperatura, 1 a 16 e 17 a 31 podem dividir a folha)
            ...folha.grades.flatMap(dias => [
                espaco(12),
                gradeDeLinhas(folha, dias, alturaDaLinha, temperatura ? GRADE.temperatura : GRADE.higiene),
            ]),
        ]));
    // Temperatura, modelo padrão: a primeira folha é só a legenda dos produtos
    if (temperatura && !equipamentos && legenda.length) {
        secoes.unshift(secaoDaFolha([
            cabecalhoLogos({ logoAccp, logoSupermercado, titulo }),
            espaco(16),
            tabelaLegenda(legenda),
        ]));
    }
    await baixarDocx(secoes, nomeArquivo);
}

// ---------- Controle de pragas (CIP) ----------
/**
 * Exporta as folhas do controle de pragas: uma por seção, com a grade e a legenda das pragas.
 * @param {object} opcoes
 * @param {string[]} opcoes.secoes      nomes das seções (uma folha cada)
 * @param {Array} opcoes.pragas         { codigo, nome, imagem }
 * @param {string|null} opcoes.nomeSocial
 * @param {string|null} opcoes.logoUrl
 * @param {string} opcoes.nomeArquivo
 */
export async function exportarPragasDocx({ secoes, pragas, nomeSocial, logoUrl, nomeArquivo }) {
    const [logoAccp, logoSupermercado, ...imagens] = await Promise.all([
        carregarImagem('/images/acoop.png'),
        carregarImagem(logoUrl),
        ...pragas.map(p => carregarImagem(p.imagem)),
    ]);
    const titulo = ['PLANILHA DE MONITORAMENTO', 'CIP – CONTROLE INTEGRADO DE PRAGAS'];
    const dias = Array.from({ length: 31 }, (_, i) => i + 1);

    const folha = nomeSecao => {
        const quarto = Math.floor(LARGURA / 4);
        const largurasTopo = [quarto, quarto, quarto, LARGURA - 3 * quarto];
        const topo = { alinhamentoVertical: VerticalAlign.TOP, margemTopo: twips(4) };
        const local = { alinhamentoVertical: VerticalAlign.CENTER, margemEsquerda: twips(4) };
        const fonteTopo = { tamanho: 10.5, tamanhoValor: 11.5 };
        const esquerda = { tamanho: 10.5, negrito: true, alinhamento: AlignmentType.LEFT };
        const tabelaTopo = tabela([
            linhaTabela([
                celula(campo('CLIENTE:', (nomeSocial || '').toUpperCase(), fonteTopo), { largura: largurasTopo[0], ...topo }),
                celula(campo('EMPRESA RESPONSÁVEL:', undefined, fonteTopo), { largura: largurasTopo[1], ...topo }),
                celula(campo('SEÇÃO:', nomeSecao, fonteTopo), { largura: largurasTopo[2], ...topo }),
                celula(campo('ENCARREGADO:', undefined, fonteTopo), { largura: largurasTopo[3], ...topo }),
            ], 38),
            linhaTabela([
                celula(texto('PERÍODO:', esquerda), { colunas: 2, largura: largurasTopo[0] + largurasTopo[1], ...local }),
                celula(texto('DATA:', esquerda), { colunas: 2, largura: largurasTopo[2] + largurasTopo[3], ...local }),
            ], 24),
        ], largurasTopo);

        const largurasGrade = colunasDaGrade(LARGURA, larguraPx(90), 31);
        const grade = tabela([
            linhaTabela([
                celula(texto('PRAGAS', { tamanho: 12, negrito: true }), { largura: largurasGrade[0], fundo: CINZA }),
                ...dias.map(d => celula(texto(d, { tamanho: 12, negrito: true }), { largura: largurasGrade[d], fundo: CINZA })),
            ], 24, { cabecalho: true }),
            ...pragas.map(p => linhaTabela([
                celula(texto(p.codigo, { tamanho: 10.5, negrito: true }), { largura: largurasGrade[0], fundo: CINZA }),
                ...dias.map(d => celula(new Paragraph({}), { largura: largurasGrade[d] })),
            ], 26)),
        ], largurasGrade);

        // Legenda: faixa vermelha "LEGENDA", rótulos em amarelo e uma coluna por praga
        const lateral = larguraPx(22);
        const rotulo = larguraPx(80);
        const largurasLegenda = colunasDaGrade(LARGURA - lateral, rotulo, Math.max(pragas.length, 1));
        largurasLegenda.unshift(lateral);
        const larguraImagemPx = Math.floor((LARGURA_FOLHA_PX - 22 - 80) / Math.max(pragas.length, 1)) - 4;
        const celulaRotulo = t => celula(texto(t, { tamanho: 11, negrito: true }), { largura: rotulo, fundo: 'FFFF00' });
        const legenda = tabela([
            linhaTabela([
                celula(texto(null, { tamanho: 10, negrito: true, cor: 'FFFFFF', quebras: [...'LEGENDA'] }),
                    { largura: lateral, fundo: 'C00000', linhas: 3 }),
                celulaRotulo('CÓDIGO'),
                ...pragas.map((p, i) => celula(texto(p.codigo, { tamanho: 13, negrito: true, cor: '1F3A5F' }), { largura: largurasLegenda[i + 2] })),
            ], 28),
            linhaTabela([
                celulaRotulo('PRAGA'),
                ...pragas.map((p, i) => celula(texto(p.nome, { tamanho: 10, negrito: true }), { largura: largurasLegenda[i + 2] })),
            ], 28),
            linhaTabela([
                celulaRotulo('IMAGEM'),
                ...pragas.map((p, i) => celula(imagens[i]
                    ? new Paragraph({ alignment: AlignmentType.CENTER, children: [imagem(imagens[i], 46, larguraImagemPx)] })
                    : new Paragraph({}), { largura: largurasLegenda[i + 2] })),
            ], 52),
        ], largurasLegenda);

        return secaoDaFolha([
            cabecalhoLogos({ logoAccp, logoSupermercado, titulo, tamanhoTitulo: 16 }),
            espaco(1),
            tabelaTopo,
            espaco(12),
            grade,
            espaco(14),
            legenda,
            espaco(22),
            texto('OBSERVAÇÕES:', { tamanho: 11, negrito: true, alinhamento: AlignmentType.LEFT }),
        ]);
    };

    await baixarDocx(secoes.map(folha), nomeArquivo);
}
