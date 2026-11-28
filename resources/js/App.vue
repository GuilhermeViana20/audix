<script setup>
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { lerPlanilhaPdf } from './importarPdf.js';

const TOKEN = document.querySelector('meta[name="csrf-token"]').content;
const JSON_HEADERS = { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': TOKEN };

const TIPO = window.AUDIX.tipo || 'higiene'; // tipo da planilha: higiene ou temperatura
const ehTemperatura = TIPO === 'temperatura';
const embutido = !!window.AUDIX.embutido; // sem seletor de supermercado nem logo (fluxo e edição)
const semPrevia = !!window.AUDIX.semPrevia;
const nomeSupermercado = window.AUDIX.nomeSupermercado || null; // nome exibido no card fixo da planilha // tela de edição do supermercado: só o editor
const supermercados = ref(window.AUDIX.supermercados || []);
const supermercadoId = ref(window.AUDIX.supermercadoId);
const carregando = ref(false);
const secoes = ref([]);
const selecionadas = ref({});
const atual = ref(null);
const titulo = ref('');
const novaSecao = ref('');
const aviso = ref('');
const status = ref('Tudo salvo');
const logoUrl = ref(null);
const nomeSocial = ref(null); // nome social do supermercado (campo CLIENTE da planilha de higiene)
const planilha = ref(null); // { id, tipo, nome } da planilha que está sendo editada
const versaoPreview = ref(0);

// Contador de requisições em andamento: enquanto houver alguma, a tela mostra o loading.
const pendentes = ref(0);
const carregandoRede = computed(() => pendentes.value > 0);
async function rede(url, opts = {}) {
    pendentes.value++;
    try {
        return await fetch(url, opts);
    } finally {
        pendentes.value--;
    }
}

const ordenadas = computed(() => [...secoes.value].sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR', { numeric: true, sensitivity: 'base' })));
const idsSelecionados = computed(() => ordenadas.value.filter(s => selecionadas.value[s.id]).map(s => s.id));
// Folhas da pré-visualização HTML: uma seção pode gerar várias folhas se tiver muitas linhas.
const ALTURA_LINHA_PX = 20; // altura fixa de toda linha/faixa da grade (px)
const LIMITE_TEMPERATURA_PX = 546; // 21 linhas de 26px: o topo da temperatura tem uma linha só (sem LOCAL/DATA)
const LEGENDA_TEMPERATURA = [
    ['Carnes e Aves', '0° a 7°C'],
    ['Pescados', '0° a 3°C'],
    ['Resfriados', '0° a 10°C'],
    ['Congelados', '-18°C a -23°C ou conforme orientação do fornecedor'],
    ['Leite e Derivados', '0° a 10°C'],
    ['Frutas, Legumes ou Vegetais "in natura"', 'Temperatura ambiente'],
    ['Folhosos ou Frutas "sensíveis"', 'Até 10°C'],
    ['Massas Frescas', 'Até 6°C c/ tolerância até 7°C ou conforme fabricante'],
    ['Margarinas', '0° a 16°C'],
    ['Salgados, recheios, sopas e pratos quentes', 'Acima de 60°C'],
    ['Sobremesas', 'Até 6°C'],
    ['Carnes preparadas', 'Acima de 60°C'],
    ['Alimentos Frios', 'Abaixo de 5°C'],
];
const LIMITE_LINHAS_PX = 560; // espaço das linhas por folha: 28 linhas de 20px (deixa lugar para o espaço e o rodapé)
const ALTURA_LINHA_EXTRA_PX = 11; // cada linha extra de texto quebrado soma esta altura
const CARACTERES_POR_LINHA = 30; // aprox. o que cabe na primeira coluna
const CARACTERES_POR_LINHA_FAIXA = 150; // a faixa ocupa a grade inteira
// Temperatura: os dias ficam em duas folhas (1 a 16 e 17 a 31), com células e fonte maiores
// para dar espaço de anotar as temperaturas à mão
const intervalo = (de, ate) => Array.from({ length: ate - de + 1 }, (_, i) => de + i);
const DIAS_POR_FOLHA = ehTemperatura ? [intervalo(1, 16), intervalo(17, 31)] : [intervalo(1, 31)];
const ALTURA_LINHA_TEMPERATURA_PX = 26; // cabem duas linhas de texto de 13px (fonte de 11px)
const ALTURA_TEXTO_TEMPERATURA_PX = 13;
const LARGURA_PRIMEIRA_COLUNA_TEMPERATURA_PX = 130; // estreita, para sobrar espaço nos dias
const CARACTERES_POR_LINHA_TEMPERATURA = 18; // o que cabe nos 130px com fonte de 11px
// Altura da linha: 21px se couber em uma linha; cada quebra de palavra soma 11px
function alturaDaLinha(linha) {
    const faixa = linha.tipo === 'grupo';
    // Na temperatura a faixa também fica na primeira coluna (os dias ocupam o resto)
    const cabe = ehTemperatura
        ? CARACTERES_POR_LINHA_TEMPERATURA
        : (faixa ? CARACTERES_POR_LINHA_FAIXA : CARACTERES_POR_LINHA);
    const n = Math.max(1, Math.ceil((linha.texto || '').length / cabe));
    if (ehTemperatura) return Math.max(ALTURA_LINHA_TEMPERATURA_PX, n * ALTURA_TEXTO_TEMPERATURA_PX);
    return ALTURA_LINHA_PX + (n - 1) * ALTURA_LINHA_EXTRA_PX;
}
function paginar(linhas) {
    const paginas = [];
    let atual = [];
    let altura = 0;
    for (const linha of linhas) {
        if (altura + alturaDaLinha(linha) > (ehTemperatura ? LIMITE_TEMPERATURA_PX : LIMITE_LINHAS_PX) && atual.length) {
            paginas.push(atual);
            atual = [];
            altura = 0;
        }
        atual.push(linha);
        altura += alturaDaLinha(linha);
    }
    paginas.push(atual);
    return paginas;
}
// Título no cabeçalho da grade: a faixa em que a folha começa (ou a última faixa anterior);
// sem nenhuma faixa, o título da primeira coluna da seção
function tituloDaFolha(secao, inicio) {
    for (let j = inicio; j >= 0; j--) {
        if (secao.linhas[j]?.tipo === 'grupo') return secao.linhas[j].texto;
    }
    return secao.titulo_primeira_coluna;
}
// Temperatura: cada supermercado escolhe o modelo da folha (Planilha::MODELOS_TEMPERATURA)
const modelos = ref({}); // código => nome, para o seletor
const modeloEquipamentos = computed(() => ehTemperatura && planilha.value?.modelo === 'equipamentos');
const METADE_TEMPERATURA_PX = 260; // 10 linhas de 26px: as grades 1 a 16 e 17 a 31 cabem na mesma folha
const EQUIPAMENTOS_POR_FOLHA = 8; // modelo equipamentos: colunas de equipamento por folha
// Modelo equipamentos: legenda menor, repetida em todas as folhas
const LEGENDA_TEMPERATURA_CURTA = [
    ['Carnes e Aves', '0° a 7°C'],
    ['Leites e Derivados', '0° a 10°C'],
    ['Resfriados', '0° a 10°C'],
    ['Pescados', '0° a 3°C'],
    ['Congelados', '-18°C a -23°C ou conforme orientação do fornecedor'],
];
// Setores com legenda própria (pelo nome do setor); os demais usam a legenda curta
const LEGENDAS_POR_SETOR = [
    {
        setor: /PEIXARIA|PESCADO/i,
        legenda: [
            ['Pescados', '0° a 3°C'],
            ['Congelados', '-18°C a -23°C ou conforme orientação do fornecedor'],
        ],
    },
    {
        setor: /\bFLV\b|HORTIFRUTI|FRUTAS/i,
        legenda: [
            ['"In Natura"', 'Temperatura ambiente'],
            ['Folhosos e frutas sensíveis', 'Até 10°C'],
            ['Pré-processados resfriados', 'Até 10°C ou conforme fabricante'],
            ['Massas frescas', 'Até 6°C c/ tolerância até 7°C ou conforme fabricante'],
        ],
    },
    {
        setor: /PADARIA/i,
        legenda: [
            ['Sobremesas', 'Até 6°C'],
            ['Salgados, recheios, sopas e pratos quentes', 'Não inferior a 65°C'],
            ['Carnes preparadas', 'Não inferior a 65°C'],
            ['Resfriados', '0°C a 10°C'],
            ['Congelados', '-18°C a -23°C ou conforme orientação do fornecedor'],
        ],
    },
];
const legendaPadraoDoSetor = secao => LEGENDAS_POR_SETOR.find(({ setor }) => setor.test(secao.nome))?.legenda
    ?? LEGENDA_TEMPERATURA_CURTA;
// A tabela editada na seção (salva no banco) vale mais que a padrão do setor
function legendaDoSetor(secao) {
    const editada = (secao.legenda || []).filter(([produto, temperatura]) => (produto || '').trim() || (temperatura || '').trim());
    return editada.length ? editada : legendaPadraoDoSetor(secao);
}
function personalizarLegenda() {
    atual.value.legenda = legendaPadraoDoSetor(atual.value).map(([produto, temperatura]) => [produto, temperatura]);
}
function restaurarLegenda() {
    if (!window.confirm('Voltar à tabela padrão deste setor? As linhas editadas serão descartadas ao salvar.')) return;
    atual.value.legenda = null;
}
const somaAlturas =linhas => linhas.reduce((total, l) => total + alturaDaLinha(l), 0);

// Folhas da temperatura: cada seção é um setor e começa numa folha nova (o nome vai no campo SETOR)
function folhasDeTemperatura(secao) {
    if (modeloEquipamentos.value) {
        // As linhas da seção são os equipamentos: viram colunas (os dias são as linhas da grade).
        // Faixas não entram neste modelo; muitos equipamentos continuam em outra folha.
        const equipamentos = secao.linhas.filter(l => l.tipo !== 'grupo');
        const grupos = [];
        for (let i = 0; i < equipamentos.length; i += EQUIPAMENTOS_POR_FOLHA) {
            grupos.push(equipamentos.slice(i, i + EQUIPAMENTOS_POR_FOLHA));
        }
        // Cada grupo de equipamentos ocupa duas folhas (dias 1 a 16 e 17 a 31), com mais espaço para escrever
        return (grupos.length ? grupos : [[]]).flatMap(grupo => DIAS_POR_FOLHA.map(dias => ({
            secao, titulo: secao.nome, equipamentos: grupo, dias, legendaCurta: legendaDoSetor(secao),
        })));
    }
    // Modelo padrão: se as linhas couberem em meia folha, os dias 1 a 16 e 17 a 31 ficam na mesma
    // folha, um embaixo do outro; senão, cada metade dos dias vai numa folha
    let inicio = 0;
    return paginar(secao.linhas).flatMap(linhas => {
        const titulo = tituloDaFolha(secao, inicio);
        // Se a folha começa numa faixa, ela já está no título (ao lado dos dias): não repete no corpo
        const corpo = linhas[0]?.tipo === 'grupo' ? linhas.slice(1) : linhas;
        inicio += linhas.length;
        return somaAlturas(linhas) <= METADE_TEMPERATURA_PX
            ? [{ secao, titulo, linhas: corpo, grades: DIAS_POR_FOLHA }]
            : DIAS_POR_FOLHA.map(dias => ({ secao, titulo, linhas: corpo, grades: [dias] }));
    });
}

// Cada folha: { secao, titulo, linhas, grades: [dias de cada grade] } ou, no modelo equipamentos,
// { secao, titulo, equipamentos }
const folhas = computed(() => ordenadas.value
    .filter(s => selecionadas.value[s.id])
    .flatMap(s => {
        if (ehTemperatura) return folhasDeTemperatura(s);
        let inicio = 0; // posição, na seção, da primeira linha desta folha
        return paginar(s.linhas).map(linhas => {
            const titulo = tituloDaFolha(s, inicio);
            // Se a folha começa numa faixa, ela já está no título (ao lado dos números): não repete no corpo
            const corpo = linhas[0]?.tipo === 'grupo' ? linhas.slice(1) : linhas;
            inicio += linhas.length;
            return { secao: s, linhas: corpo, titulo, grades: DIAS_POR_FOLHA };
        });
    }));
// Temperatura (modelo padrão): a impressão começa por uma folha só com a legenda dos produtos
const folhasDaPrevia = computed(() => (ehTemperatura && !modeloEquipamentos.value && folhas.value.length
    ? [{ legenda: true }, ...folhas.value]
    : folhas.value));

async function trocarModelo(codigo) {
    if (!planilha.value || codigo === planilha.value.modelo) return;
    try {
        const dados = await api(`/api/planilhas/${planilha.value.id}/modelo`, { method: 'PUT', body: JSON.stringify({ modelo: codigo }) });
        planilha.value = { ...planilha.value, modelo: dados.modelo };
        atualizarPreview();
        msg('Modelo da folha atualizado');
    } catch (e) { msg(e.message); }
}

// Ajusta o zoom das folhas para caber na coluna de pré-visualização (só na tela)
const zoomPreview = ref(1);
let observadorPreview = null;
function ajustarZoom() {
    const caixa = document.getElementById('sheets-box');
    if (!caixa) return;
    if (ehTemperatura) {
        if (window.matchMedia('print').matches) return;
        const estilo = getComputedStyle(caixa);
        const largura = caixa.clientWidth - (parseFloat(estilo.paddingLeft) || 0) - (parseFloat(estilo.paddingRight) || 0);
        if (largura > 0) zoomPreview.value = largura / 1123;
    } else {
        zoomPreview.value = Math.max(0.3, Math.min(1, (caixa.clientWidth - 24) / 1123));
    }
}
// Registra o relatório (planilha impressa ou exportada) com as seções selecionadas
async function registrarRelatorio() {
    if (!planilha.value || !idsSelecionados.value.length) return;
    try {
        await rede(`/api/planilhas/${planilha.value.id}/relatorios`, {
            method: 'POST',
            headers: JSON_HEADERS,
            body: JSON.stringify({ secoes: idsSelecionados.value }),
        });
    } catch (e) { /* o registro não impede a impressão nem a exportação */ }
}

// Registra o relatório e abre a impressão do navegador
async function imprimir() {
    await registrarRelatorio();
    // O nome sugerido do arquivo, ao salvar como PDF, é o título da página no momento da impressão
    const tituloAnterior = document.title;
    if (window.AUDIX.nomeArquivo) document.title = window.AUDIX.nomeArquivo;
    window.print();
    document.title = tituloAnterior;
}

// Gera o .docx com as mesmas folhas da pré-visualização, para ajustes pontuais no Word
const exportandoWord = ref(false);
async function exportarWord() {
    if (!folhas.value.length || exportandoWord.value) return;
    exportandoWord.value = true;
    pendentes.value++;
    try {
        await registrarRelatorio();
        // A biblioteca do Word só é baixada quando o usuário exporta
        const { exportarPlanilhaDocx } = await import('./exportarDocx.js');
        await exportarPlanilhaDocx({
            tipo: TIPO,
            nomePlanilha: planilha.value?.nome,
            nomeSocial: nomeSocial.value,
            logoUrl: logoUrl.value,
            folhas: folhas.value,
            alturaDaLinha,
            legenda: LEGENDA_TEMPERATURA,
            modelo: modeloEquipamentos.value ? 'equipamentos' : 'padrao',
            legendaCurta: LEGENDA_TEMPERATURA_CURTA,
            nomeArquivo: window.AUDIX.nomeArquivo || planilha.value?.nome || 'Planilha',
        });
    } catch (e) {
        msg('Não consegui gerar o arquivo Word');
    } finally {
        pendentes.value--;
        exportandoWord.value = false;
    }
}

// Assinatura do que é salvo no banco para uma seção (título + linhas).
function assinatura(s, tit) {
    return JSON.stringify({ t: tit, l: s.linhas.map(l => [l.tipo, l.texto]), g: s.legenda ?? null });
}
const estaSujo = computed(() => !!atual.value && assinatura(atual.value, titulo.value) !== atual.value._salvo);
const textoStatus = computed(() => ['Salvando…', 'Erro ao salvar'].includes(status.value)
    ? status.value
    : (estaSujo.value ? 'Alterações não salvas' : 'Tudo salvo'));

function msg(texto) {
    const erro = /erro|não|Já |Informe|Clique|Adicione|Escolha|Há |Nenhum/i.test(texto);
    window.toast?.(texto, erro ? 'erro' : 'sucesso');
}
function atualizarPreview() { versaoPreview.value++; }

async function api(url, opts = {}) {
    const r = await rede(url, { headers: JSON_HEADERS, ...opts });
    const dados = await r.json().catch(() => ({}));
    if (!r.ok) throw new Error(dados.message || Object.values(dados.errors || {})[0]?.[0] || 'Erro ao salvar');
    return dados;
}

function mapearSecoes(lista) {
    return lista.map(s => ({ ...s, _salvo: assinatura(s, s.titulo_primeira_coluna) }));
}

// Carrega seções + linhas do supermercado de uma vez.
async function recarregarSecoes() {
    const dados = await api(`/api/supermercados/${supermercadoId.value}/planilhas/${TIPO}`);
    planilha.value = dados.planilha;
    modelos.value = dados.modelos || {};
    logoUrl.value = dados.logo_url;
    nomeSocial.value = dados.nome_social;
    secoes.value = mapearSecoes(dados.secoes);
    dados.secoes.forEach(s => { if (selecionadas.value[s.id] === undefined) selecionadas.value[s.id] = true; });
    if (atual.value) atual.value = secoes.value.find(s => s.id === atual.value.id) || null;
    atualizarPreview();
}

async function carregarSupermercado(id) {
    if (!id || !podeDescartar()) return;
    carregando.value = true;
    try {
        supermercadoId.value = id;
        atual.value = null;
        titulo.value = '';
        selecionadas.value = {};
        await recarregarSecoes();
        if (!embutido) history.replaceState(null, '', `/${TIPO}/${id}`);
    } catch (e) { msg(e.message); }
    finally { carregando.value = false; }
}

// Pede confirmação se houver alterações não salvas; ao confirmar, descarta-as.
async function podeDescartar() {
    if (!estaSujo.value) return true;
    if (!window.confirm('Há alterações não salvas. Descartar?')) return false;
    clearTimeout(timer);
    await recarregarSecoes();
    return true;
}

async function fechar() {
    if (!await podeDescartar()) return;
    atual.value = null;
    titulo.value = '';
}

async function abrir(secao) {
    if (atual.value?.id !== secao.id && !await podeDescartar()) return;
    const atualizada = secoes.value.find(s => s.id === secao.id) || secao;
    atual.value = atualizada;
    titulo.value = atualizada.titulo_primeira_coluna;
}

// Salva a seção aberta quando o usuário clica em "Salvar"
async function salvar() {
    clearTimeout(timer);
    await salvarAgora();
    if (status.value === 'Tudo salvo') msg('Seção salva');
}

// ---------- Desfazer (Ctrl+Z) na edição da seção ----------
// Guarda o estado anterior de cada alteração; alterações seguidas em menos de 0,8s contam como uma só.
const historico = ref([]);
let ultimaAlteracao = 0;
let restaurando = false;
const estadoDaSecao = () => atual.value ? JSON.stringify({
    id: atual.value.id,
    t: titulo.value,
    l: atual.value.linhas.map(l => [l.tipo, l.texto]),
}) : null;
watch(estadoDaSecao, (novo, antigo) => {
    if (restaurando || !novo || !antigo) return;
    const a = JSON.parse(antigo);
    const n = JSON.parse(novo);
    if (a.id !== n.id) return;
    if (Date.now() - ultimaAlteracao > 800) {
        historico.value.push(a);
        if (historico.value.length > 100) historico.value.shift();
    }
    ultimaAlteracao = Date.now();
});
watch(() => atual.value?.id, () => { historico.value = []; ultimaAlteracao = 0; });
function desfazer() {
    const anterior = historico.value.pop();
    if (!anterior || !atual.value || anterior.id !== atual.value.id) { msg('Nada para desfazer'); return; }
    restaurando = true;
    titulo.value = anterior.t;
    atual.value.linhas = anterior.l.map(([tipo, texto]) => ({ tipo, texto }));
    nextTick(() => { restaurando = false; });
}
function aoTeclar(evento) {
    if ((evento.ctrlKey || evento.metaKey) && !evento.shiftKey && evento.key.toLowerCase() === 'z' && !rascunho.value && !copiaPendente.value) {
        evento.preventDefault();
        desfazer();
    }
}

// Copiar a seção inteira (título e linhas) para outro supermercado
async function copiarSecaoPara(evento) {
    const destinoId = Number(evento.target.value);
    evento.target.value = '';
    const destino = supermercados.value.find(m => m.id === destinoId);
    if (!destino || !atual.value) return;
    if (estaSujo.value) { msg('Salve as alterações desta seção antes de copiar'); return; }
    if (!window.confirm(`Copiar a seção ${atual.value.nome} com todas as linhas para ${destino.nome}?`)) return;
    try {
        const r = await api(`/api/secoes/${atual.value.id}/copiar-para-supermercado`, {
            method: 'POST',
            body: JSON.stringify({ supermercado_id: destino.id }),
        });
        msg(`Seção copiada para ${r.supermercado}`);
    } catch (e) { msg(e.message); }
}

// ---------- Salvamento automático ----------
let timer = null;
let fila = Promise.resolve();


// Enfileira o salvamento para não haver duas gravações ao mesmo tempo.
function salvarAgora() {
    clearTimeout(timer);
    fila = fila.then(salvarSecao);
    return fila;
}

async function salvarSecao() {
    const s = atual.value;
    if (!s) return;
    const tit = titulo.value;
    const assin = assinatura(s, tit);
    if (assin === s._salvo) return;

    status.value = 'Salvando…';
    try {
        await api(`/api/secoes/${s.id}/linhas`, {
            method: 'PUT',
            body: JSON.stringify({
                linhas: s.linhas.map(l => ({ tipo: l.tipo, texto: l.texto })),
                titulo_primeira_coluna: tit,
                legenda: s.legenda ?? null,
            }),
        });
        s.titulo_primeira_coluna = tit;
        s._salvo = assin;
        status.value = estaSujo.value ? 'Alterações não salvas' : 'Tudo salvo';
        atualizarPreview();
    } catch (e) {
        status.value = 'Erro ao salvar';
        msg(e.message);
    }
}

// Aviso ao fechar a aba com alterações ainda não gravadas
function avisarAntesDeSair(evento) {
    if (estaSujo.value || status.value === 'Salvando…') { evento.preventDefault(); evento.returnValue = ''; }
}

// ---------- Ações de edição ----------
async function copiarParaTodas() {
    if (!atual.value) return;
    if (estaSujo.value) { msg('Clique em Salvar antes de copiar para as outras seções'); return; }
    try {
        await api(`/api/secoes/${atual.value.id}/copiar-para-todas`, { method: 'POST' });
        await recarregarSecoes();
        msg('Copiado para todas as seções');
    } catch (e) { msg(e.message); }
}

function moverLinha(i, d) {
    const l = atual.value.linhas;
    const j = i + d;
    if (j < 0 || j >= l.length) return;
    [l[i], l[j]] = [l[j], l[i]];
}
function adicionarLinha() { atual.value.linhas.push({ tipo: 'linha', texto: '' }); }
function adicionarGrupo() { atual.value.linhas.push({ tipo: 'grupo', texto: 'NOVA FAIXA' }); }
function removerLinha(i) { atual.value.linhas.splice(i, 1); }
// Insere uma linha vazia logo acima (d = 0) ou logo abaixo (d = 1) da linha i
function inserirLinha(i, d) {
    atual.value.linhas.splice(i + d, 0, { tipo: 'linha', texto: '' });
}

// Copia um bloco (faixa + linhas) para o fim de outra seção e grava na hora
async function copiarParaSecao(destinoId, copia) {
    const destino = secoes.value.find(s => s.id === destinoId);
    if (!destino || !atual.value) return;
    if (estaSujo.value) { msg('Salve as alterações desta seção antes de copiar'); return; }
    const existentes = destino.linhas.map(l => ({ tipo: l.tipo, texto: l.texto }));
    try {
        await api(`/api/secoes/${destino.id}/linhas`, {
            method: 'PUT',
            body: JSON.stringify({ linhas: [...existentes, ...copia] }),
        });
        await recarregarSecoes();
        msg(`${copia.length} item(ns) copiado(s) para ${destino.nome}`);
    } catch (e) { msg(e.message); }
}

// Monta o bloco a copiar: a faixa (ou o título) e as linhas abaixo dela, até a próxima faixa
function montarCopia(tipo, i) {
    const linhas = atual.value.linhas;
    const copiar = l => ({ tipo: l.tipo, texto: l.texto });
    if (tipo === 'titulo') {
        let fim = 0;
        while (fim < linhas.length && linhas[fim].tipo !== 'grupo') fim++;
        return {
            resumo: titulo.value.trim(),
            copia: [{ tipo: 'grupo', texto: titulo.value.trim() }, ...linhas.slice(0, fim).map(copiar)],
        };
    }
    let fim = i + 1;
    while (fim < linhas.length && linhas[fim].tipo !== 'grupo') fim++;
    return { resumo: linhas[i].texto, copia: linhas.slice(i, fim).map(copiar) };
}

// Popup de cópia: o usuário escolhe a seção de destino e confirma
const copiaPendente = ref(null);
function abrirCopia(tipo, i) {
    if (estaSujo.value) { msg('Salve as alterações desta seção antes de copiar'); return; }
    const { resumo, copia } = montarCopia(tipo, i);
    copiaPendente.value = {
        resumo,
        copia,
        destinos: ordenadas.value.filter(o => o.id !== atual.value.id),
        escolhido: null,
    };
}
async function confirmarCopia() {
    const c = copiaPendente.value;
    if (!c || !c.escolhido) return;
    copiaPendente.value = null;
    await copiarParaSecao(c.escolhido, c.copia);
}

async function adicionarSecao() {
    if (!await podeDescartar()) return;
    const nome = novaSecao.value.trim();
    if (!nome) return;
    try {
        const nova = await api(`/api/planilhas/${planilha.value.id}/secoes`, { method: 'POST', body: JSON.stringify({ nome }) });
        novaSecao.value = '';
        selecionadas.value[nova.id] = true;
        await recarregarSecoes();
        await abrir(secoes.value.find(s => s.id === nova.id));
    } catch (e) { msg(e.message); }
}

async function renomear(secao) {
    if (!await podeDescartar()) return;
    const nome = window.prompt('Novo nome da seção:', secao.nome);
    if (!nome || nome.trim().toUpperCase() === secao.nome) return;
    try {
        await api(`/api/secoes/${secao.id}`, {
            method: 'PUT',
            body: JSON.stringify({ nome, titulo_primeira_coluna: secao.titulo_primeira_coluna }),
        });
        await recarregarSecoes();
    } catch (e) { msg(e.message); }
}

async function excluirSecao(secao) {
    if (!await podeDescartar()) return;
    if (!window.confirm(`Remover a seção ${secao.nome} e suas linhas?`)) return;
    try {
        if (atual.value?.id === secao.id) { clearTimeout(timer); atual.value = null; titulo.value = ''; }
        await api(`/api/secoes/${secao.id}`, { method: 'DELETE' });
        delete selecionadas.value[secao.id];
        await recarregarSecoes();
    } catch (e) { msg(e.message); }
}

// ---------- Importação de PDF ----------
// As seções lidas ficam em "rascunho" no popup; só são gravadas ao confirmar.
const rascunho = ref(null);
const lendoPdf = ref(false);
const totalAvisos = computed(() => rascunho.value
    ? rascunho.value.reduce((n, s) => n + s.linhas.filter(l => l.aviso).length, 0)
    : 0);
let contadorChave = 0;
const novaChave = () => ++contadorChave;

async function escolherPdf(evento) {
    const arquivo = evento.target.files[0];
    evento.target.value = '';
    if (!arquivo) return;
    if (!await podeDescartar()) return;
    lendoPdf.value = true;
    try {
        const lidas = await lerPlanilhaPdf(arquivo);
        if (!lidas.length) { msg('Não encontrei seções nesse PDF'); return; }
        rascunho.value = lidas.map(s => ({
            _k: novaChave(),
            nome: s.nome,
            titulo_primeira_coluna: s.titulo_primeira_coluna,
            linhas: s.linhas.map(l => ({ _k: novaChave(), tipo: l.tipo, texto: l.texto, aviso: l.aviso || null })),
        }));
    } catch (e) {
        // A causa real fica no console; o usuário vê só a mensagem amigável
        console.error('[Audix] Falha ao ler o PDF', arquivo.name, e);
        msg('Não consegui ler esse PDF');
    } finally {
        lendoPdf.value = false;
    }
}

function moverItem(lista, i, d) {
    const j = i + d;
    if (j < 0 || j >= lista.length) return;
    [lista[i], lista[j]] = [lista[j], lista[i]];
}

// Ordena as linhas A–Z dentro de cada bloco; as faixas continuam no lugar e separam os blocos
function ordenarBlocos(lista) {
    const resultado = [];
    let bloco = [];
    const fecharBloco = () => {
        bloco.sort((a, b) => a.texto.localeCompare(b.texto, 'pt-BR', { sensitivity: 'base', numeric: true }));
        resultado.push(...bloco);
        bloco = [];
    };
    for (const item of lista) {
        if (item.tipo === 'grupo') { fecharBloco(); resultado.push(item); } else { bloco.push(item); }
    }
    fecharBloco();
    return resultado;
}
function ordenarEditor() {
    if (atual.value) atual.value.linhas = ordenarBlocos(atual.value.linhas);
}

async function confirmarImportacao() {
    const secoesParaGravar = rascunho.value.map(s => ({
        nome: s.nome.trim(),
        titulo_primeira_coluna: s.titulo_primeira_coluna,
        linhas: s.linhas.map(l => ({ tipo: l.tipo, texto: l.texto })),
    }));
    if (secoesParaGravar.some(s => !s.nome)) { msg('Todas as seções precisam de nome'); return; }
    try {
        await api(`/api/planilhas/${planilha.value.id}/importacao`, {
            method: 'POST',
            body: JSON.stringify({ secoes: secoesParaGravar }),
        });
        rascunho.value = null;
        atual.value = null;
        titulo.value = '';
        await recarregarSecoes();
        msg('Planilha importada e salva');
    } catch (e) { msg(e.message); }
}

// Autosave: qualquer mudança nas linhas ou no título das seções agenda a gravação
// Atualiza a pré-visualização quando muda a seleção
watch(selecionadas, atualizarPreview, { deep: true });

onMounted(async () => {
    window.addEventListener('beforeunload', avisarAntesDeSair);
    window.addEventListener('resize', ajustarZoom);
    window.addEventListener('keydown', aoTeclar);
    await nextTick();
    if (ehTemperatura) {
        const caixa = document.getElementById('sheets-box');
        if (caixa) {
            observadorPreview = new ResizeObserver(ajustarZoom);
            observadorPreview.observe(caixa);
        }
        window.addEventListener('afterprint', ajustarZoom);
    }
    ajustarZoom();
    if (supermercadoId.value) await carregarSupermercado(supermercadoId.value);
});
onBeforeUnmount(() => {
    observadorPreview?.disconnect();
    window.removeEventListener('resize', ajustarZoom);
    window.removeEventListener('afterprint', ajustarZoom);
    window.removeEventListener('beforeunload', avisarAntesDeSair);
    window.removeEventListener('keydown', aoTeclar);
});
</script>

<template>
    <!-- Loading enquanto houver requisições em andamento (salvar, carregar, PDF) -->
    <div v-if="carregandoRede"
        class="fixed inset-0 z-50 flex items-center justify-center bg-grafite/20 backdrop-blur-[1px]" role="status"
        aria-live="polite">
        <div class="flex items-center gap-3 rounded-2xl bg-white px-5 py-3 text-sm font-semibold shadow-lg">
            <span class="h-4 w-4 animate-spin rounded-full border-2 border-azul border-t-transparent"></span>
            Carregando…
        </div>
    </div>

    <!-- Popup de conferência da importação: nada é gravado antes de confirmar -->
    <div v-if="rascunho" class="fixed inset-0 z-40 flex items-end justify-center bg-grafite/50 sm:items-center sm:p-4"
        role="dialog" aria-modal="true">
        <div class="flex max-h-[92vh] w-full max-w-3xl flex-col rounded-t-2xl bg-white shadow-xl sm:rounded-2xl">
            <div class="border-b border-grafite/10 p-4 md:p-5">
                <h2 class="text-lg font-bold">Confirmar importação</h2>
                <p class="text-xs text-grafite/60">Revise seções, linhas e faixas. Nada é salvo no banco até você clicar
                    em "Confirmar e salvar".</p>
                <p v-if="totalAvisos"
                    class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800">{{ totalAvisos }}
                    item(ns) para revisar antes de salvar</p>
            </div>

            <div class="flex-1 space-y-5 overflow-y-auto p-4 md:p-5">
                <div v-for="(s, i) in rascunho" :key="s._k" class="rounded-xl border border-grafite/10 p-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <input v-model="s.nome" data-maiusculas placeholder="Nome da seção"
                            class="min-w-0 flex-1 rounded-lg bg-offwhite px-3 py-2 text-sm font-bold uppercase">
                        <button class="px-1 text-azul disabled:opacity-30" :disabled="i === 0"
                            @click="moverItem(rascunho, i, -1)">↑</button>
                        <button class="px-1 text-azul disabled:opacity-30" :disabled="i === rascunho.length - 1"
                            @click="moverItem(rascunho, i, 1)">↓</button>
                        <button class="text-sm text-red-500" @click="rascunho.splice(i, 1)">Remover seção</button>
                    </div>
                    <label class="mt-3 block text-xs text-grafite/60">Título da primeira coluna</label>
                    <input v-model="s.titulo_primeira_coluna" data-maiusculas
                        class="mt-1 w-full rounded-lg bg-offwhite px-3 py-2 text-sm">

                    <div class="mt-3 space-y-2">
                        <div v-for="(l, j) in s.linhas" :key="l._k" :data-linha="j" :data-grupo="s._k"
                            class="flex flex-wrap items-center gap-2 rounded-lg transition">
                            <select v-model="l.tipo" class="w-20 shrink-0 rounded-lg bg-offwhite px-2 py-2 text-xs">
                                <option value="linha">Linha</option>
                                <option value="grupo">Faixa</option>
                            </select>
                            <input v-model="l.texto" data-maiusculas class="min-w-0 flex-1 rounded-lg px-3 py-2 text-sm"
                                :class="l.tipo === 'grupo' ? 'bg-grafite/10 font-bold uppercase' : 'bg-white'">
                            <button class="px-1 text-red-500" @click="s.linhas.splice(j, 1)">×</button>
                            <p v-if="l.aviso" class="order-last basis-full pl-2 text-xs text-amber-700">⚠ {{ l.aviso }}
                                <button class="font-semibold underline" @click="l.aviso = null">ok</button>
                            </p>
                        </div>
                    </div>

                    <div class="mt-3 flex gap-2 text-sm">
                        <button class="rounded-lg bg-offwhite px-3 py-2 font-medium text-azul"
                            @click="s.linhas = ordenarBlocos(s.linhas)">Ordenar A–Z</button>
                        <button class="rounded-lg bg-offwhite px-3 py-2 font-medium text-azul"
                            @click="s.linhas.push({ _k: novaChave(), tipo: 'linha', texto: '' })">+ Linha</button>
                        <button class="rounded-lg bg-offwhite px-3 py-2 font-medium text-azul"
                            @click="s.linhas.push({ _k: novaChave(), tipo: 'grupo', texto: 'NOVA FAIXA' })">+
                            Faixa</button>
                    </div>
                </div>

                <button class="w-full rounded-xl border border-dashed border-azul py-3 text-sm font-semibold text-azul"
                    @click="rascunho.push({ _k: novaChave(), nome: '', titulo_primeira_coluna: 'ÁREA DE ATENDIMENTO', linhas: [] })">+
                    Nova seção</button>
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-grafite/10 p-4 md:p-5">
                <button class="text-sm font-medium text-grafite/70" @click="rascunho = null">Cancelar</button>
                <button class="rounded-xl bg-azul px-5 py-2.5 text-sm font-semibold text-white"
                    @click="confirmarImportacao">Confirmar e salvar</button>
            </div>
        </div>
    </div>

    <!-- Popup para copiar faixa (ou título) e linhas para outra seção -->
    <div v-if="copiaPendente"
        class="fixed inset-0 z-40 flex items-end justify-center bg-grafite/50 sm:items-center sm:p-4" role="dialog"
        aria-modal="true">
        <div class="flex max-h-[85vh] w-full max-w-md flex-col rounded-t-2xl bg-white shadow-xl sm:rounded-2xl">
            <div class="border-b border-grafite/10 p-4">
                <h2 class="text-lg font-bold">Copiar para outra seção</h2>
                <p class="text-sm text-grafite/70">“{{ copiaPendente.resumo }}” com {{ copiaPendente.copia.length - 1 }}
                    linha(s) abaixo. Será adicionada ao fim da seção escolhida.</p>
            </div>
            <div class="flex-1 space-y-2 overflow-y-auto p-4">
                <p v-if="!copiaPendente.destinos.length" class="text-sm text-grafite/60">Não há outras seções para
                    copiar.</p>
                <label v-for="d in copiaPendente.destinos" :key="d.id"
                    class="flex cursor-pointer items-center gap-3 rounded-xl bg-offwhite px-4 py-3 text-sm font-medium"
                    :class="copiaPendente.escolhido === d.id && 'ring-2 ring-azul'">
                    <input type="radio" name="destino-copia" :value="d.id" v-model="copiaPendente.escolhido"
                        class="accent-azul h-4 w-4">
                    {{ d.nome }}
                </label>
            </div>
            <div class="flex items-center justify-between gap-3 border-t border-grafite/10 p-4">
                <button class="text-sm font-medium text-grafite/70" @click="copiaPendente = null">Cancelar</button>
                <button class="rounded-xl bg-azul px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-40"
                    :disabled="!copiaPendente.escolhido" @click="confirmarCopia">Confirmar cópia</button>
            </div>
        </div>
    </div>

    <div class="planilha-workspace flex min-h-screen xl:min-h-0 xl:flex-1">
        <div class="planilha-colunas xl:h-full" :class="semPrevia ? 'grid w-full gap-6' : 'grid w-full gap-6 xl:grid-cols-[2fr_3fr]'">
            <!-- Coluna dos controles: no celular fica em cima; no computador, à esquerda -->
            <section class="planilha-controles min-w-0 flex min-h-0 flex-col gap-5 xl:h-full">
                <!-- Supermercado em edição: fixo no topo da coluna, acima das seções -->
                
                <!-- Conteúdo abaixo do card: rola por dentro e é cortado na borda do card -->
                <div class="planilha-controles-conteudo flex min-h-0 flex-1 flex-col gap-5 xl:overflow-y-auto xl:pr-1">
                    <div v-if="!embutido" class="rounded-2xl bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase text-grafite/50">Supermercado</p>
                        <select class="mt-2 w-full rounded-xl bg-offwhite px-3 py-2 text-sm font-medium"
                            :value="supermercadoId ?? ''" @change="carregarSupermercado(Number($event.target.value))">
                            <option value="" disabled>Escolha o supermercado</option>
                            <option v-for="m in supermercados" :key="m.id" :value="m.id">{{ m.nome }}</option>
                        </select>
                    </div>

                    <template v-if="supermercadoId">
                        <p v-if="aviso" class="rounded-xl bg-white px-4 py-2 text-sm shadow-sm">{{ aviso }}</p>

                        <div class="rounded-2xl bg-white p-4 shadow-sm" :class="carregando && 'opacity-60'">
                            <p class="text-xs font-semibold uppercase text-grafite/50">Seções no arquivo</p>
                            <div class="mt-3 flex gap-2 text-sm">
                                <button class="text-azul font-medium"
                                    @click="ordenadas.forEach(s => selecionadas[s.id] = true)">Marcar todas</button>
                                <button class="text-azul font-medium"
                                    @click="ordenadas.forEach(s => selecionadas[s.id] = false)">Nenhuma</button>
                                <Teleport to="#topo-acoes" :disabled="!embutido">
                                <label
                                    class="ml-auto inline-flex cursor-pointer items-center gap-2 rounded-full bg-azul px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-azul/30 transition hover:bg-[#3152e8]"
                                    :class="lendoPdf && 'pointer-events-none opacity-50'">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4m0 0l-4 4m4-4l4 4M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"/></svg>
                                    {{ lendoPdf ? 'Lendo PDF…' : 'Importar PDF' }}
                                    <input type="file" accept="application/pdf" class="hidden" @change="escolherPdf">
                                </label>
                                </Teleport>
                            </div>
                            <p v-if="planilha" class="mt-2 text-xs text-grafite/60">Tipo de planilha: <b
                                    class="text-grafite">{{ planilha.nome }}</b></p>
                            <!-- Temperatura: cada supermercado tem o seu modelo de folha -->
                            <label v-if="planilha && Object.keys(modelos).length" class="mt-3 block text-xs text-grafite/60">
                                Modelo da folha
                                <select :value="planilha.modelo" @change="trocarModelo($event.target.value)"
                                    class="mt-1 block w-full rounded-xl bg-offwhite px-3 py-2 text-sm text-grafite">
                                    <option v-for="(nome, codigo) in modelos" :key="codigo" :value="codigo">{{ nome }}</option>
                                </select>
                            </label>

                            <div class="mt-3 flex flex-col gap-3">
                                <article v-for="(s, idx) in ordenadas" :key="s.id"
                                    class="flex min-w-0 flex-col rounded-xl border p-3 transition"
                                    :class="atual?.id === s.id ? 'col-span-full border-azul bg-azul/5' : 'border-grafite/10 bg-offwhite'">
                                    <div class="flex cursor-pointer items-center gap-2" @click="atual?.id === s.id ? fechar() : abrir(s)">
                                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-azul/10 text-[11px] font-bold text-azul">{{ String(idx + 1).padStart(2, '0') }}</span>
                                        <label class="flex min-w-0 items-center gap-2 text-sm font-bold" @click.stop>
                                            <input type="checkbox" v-model="selecionadas[s.id]" class="accent-azul h-4 w-4 shrink-0">
                                            <span class="truncate">{{ s.nome }}</span>
                                        </label>
                                        <button class="ml-auto shrink-0 px-1 text-red-500" title="Remover seção" @click.stop="excluirSecao(s)">×</button>
                                    </div>
                                    <div class="mt-2 flex cursor-pointer flex-wrap items-center gap-3 text-xs text-grafite/50" @click="atual?.id === s.id ? fechar() : abrir(s)">
                                        <span>{{ s.linhas.length }} linhas</span>
                                        <button class="text-azul font-medium" @click.stop="renomear(s)">Renomear</button>
                                        <button class="ml-auto font-semibold text-azul" @click.stop="atual?.id === s.id ? fechar() : abrir(s)">{{ atual?.id === s.id ? 'Fechar ▲' : 'Editar ▼' }}</button>
                                    </div>

                                    

                                    <template v-if="atual?.id === s.id">
                                        <div class="p-1">
                                        <div class="flex items-center justify-end">
                                            <span class="text-xs font-semibold"
                                                :class="status === 'Erro ao salvar' ? 'text-red-500' : (estaSujo ? 'text-amber-600' : 'text-grafite/50')">{{
                                                    textoStatus }}</span>
                                        </div>
                                        <label class="block text-sm text-grafite/70">Título da primeira coluna</label>
                                        <div class="mt-1 flex items-center gap-2">
                                            <button class="shrink-0 rounded-lg bg-white px-2 py-2 text-azul"
                                                title="Copiar título e linhas para outra seção"
                                                @click="abrirCopia('titulo')"><svg viewBox="0 0 24 24" class="h-4 w-4"
                                                    fill="none" stroke="currentColor" stroke-width="2"
                                                    aria-hidden="true">
                                                    <rect x="9" y="9" width="13" height="13" rx="2" />
                                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                                                </svg></button>
                                            <input v-model="titulo" data-maiusculas
                                                class="min-w-0 flex-1 rounded-xl bg-grafite/10 px-3 py-2 text-sm font-bold uppercase">
                                        </div>

                                        <div class="mt-4 space-y-2">
                                            <div v-for="(l, i) in atual.linhas" :key="l.id ?? i" :data-linha="i"
                                                :data-grupo="atual.id"
                                                class="flex flex-wrap items-center gap-2 rounded-lg transition">
                                                <select v-model="l.tipo"
                                                    class="w-20 shrink-0 rounded-lg bg-offwhite px-2 py-2 text-xs">
                                                    <option value="linha">Linha</option>
                                                    <option value="grupo">Faixa</option>
                                                </select>
                                                <button v-if="l.tipo === 'grupo'"
                                                    class="shrink-0 rounded-lg bg-offwhite px-2 py-2 text-azul"
                                                    title="Copiar faixa e linhas para outra seção"
                                                    @click="abrirCopia('faixa', i)"><svg viewBox="0 0 24 24"
                                                        class="h-4 w-4" fill="none" stroke="currentColor"
                                                        stroke-width="2" aria-hidden="true">
                                                        <rect x="9" y="9" width="13" height="13" rx="2" />
                                                        <path
                                                            d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                                                    </svg></button>
                                                <input v-model="l.texto" data-maiusculas class="flex-1 rounded-xl px-3 py-2 text-sm"
                                                    :class="l.tipo === 'grupo' ? 'bg-grafite/10 font-bold uppercase' : 'bg-white'">
                                                <button class="px-1 text-xs font-semibold text-azul"
                                                    title="Inserir linha acima" @click="inserirLinha(i, 0)">+↑</button>
                                                <button class="px-1 text-xs font-semibold text-azul"
                                                    title="Inserir linha abaixo" @click="inserirLinha(i, 1)">+↓</button>
                                                <button class="px-1 text-red-500" @click="removerLinha(i)">×</button>
                                            </div>
                                        </div>

                                        <!-- Temperatura, modelo equipamentos: tabela de produtos e temperatura do setor -->
                                        <div v-if="modeloEquipamentos" class="mt-4 rounded-xl bg-white p-3">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-sm font-bold">Tabela de produtos e temperatura</span>
                                                <span v-if="!atual.legenda" class="text-xs text-grafite/50">(padrão do setor)</span>
                                                <button v-if="atual.legenda" class="ml-auto text-xs font-medium text-azul"
                                                    @click="restaurarLegenda">Voltar à tabela padrão</button>
                                            </div>
                                            <table v-if="!atual.legenda" class="mt-2 w-full text-xs">
                                                <tr v-for="([produto, temperatura], i) in legendaDoSetor(atual)" :key="i">
                                                    <td class="py-1 pr-2 font-medium">{{ produto }}</td>
                                                    <td class="py-1 text-grafite/70">{{ temperatura }}</td>
                                                </tr>
                                            </table>
                                            <button v-if="!atual.legenda"
                                                class="mt-2 rounded-xl bg-offwhite px-3 py-2 text-sm font-medium text-azul"
                                                @click="personalizarLegenda">Editar tabela</button>
                                            <template v-else>
                                                <div v-for="(item, i) in atual.legenda" :key="i" class="mt-2 flex items-center gap-2">
                                                    <input v-model="item[0]" placeholder="Produto"
                                                        class="min-w-0 flex-1 rounded-xl bg-offwhite px-3 py-2 text-sm">
                                                    <input v-model="item[1]" placeholder="Temperatura"
                                                        class="min-w-0 flex-[1.4] rounded-xl bg-offwhite px-3 py-2 text-sm">
                                                    <button class="px-1 text-red-500" title="Remover linha"
                                                        @click="atual.legenda.splice(i, 1)">×</button>
                                                </div>
                                                <button class="mt-2 rounded-xl bg-offwhite px-3 py-2 text-sm font-medium text-azul"
                                                    @click="atual.legenda.push(['', ''])">+ Produto</button>
                                            </template>
                                        </div>

                                        <div class="mt-4 flex flex-wrap gap-2 text-sm">
                                            <button class="rounded-xl bg-white px-3 py-2 font-medium text-azul"
                                                @click="ordenarEditor">Ordenar A–Z</button>
                                            <button class="rounded-xl bg-white px-3 py-2 font-medium text-azul"
                                                @click="adicionarLinha">+ Linha</button>
                                            <button class="rounded-xl bg-white px-3 py-2 font-medium text-azul"
                                                @click="adicionarGrupo">+ Faixa</button>
                                            <button class="ml-auto rounded-xl bg-white px-3 py-2 font-medium"
                                                @click="copiarParaTodas">Copiar para todas</button>
                                            <select class="rounded-xl bg-white px-3 py-2 text-xs text-azul"
                                                @change="copiarSecaoPara($event)">
                                                <option value="">Copiar seção para…</option>
                                                <option v-for="m in supermercados.filter(x => x.id !== supermercadoId)"
                                                    :key="m.id" :value="m.id">{{ m.nome }}</option>
                                            </select>
                                            <button
                                                class="rounded-xl bg-azul px-4 py-2 font-semibold text-white disabled:opacity-40"
                                                :disabled="!estaSujo || status === 'Salvando…'"
                                                @click="salvar">Salvar</button>
                                        </div>
                                    </div>
                                    </template>
                                </article>
                            </div>

                            <form class="mt-3 flex gap-2" @submit.prevent="adicionarSecao">
                                <input v-model="novaSecao" data-maiusculas placeholder="Nova seção (ex.: PADARIA)"
                                    class="flex-1 rounded-xl bg-offwhite px-3 py-2 text-sm">
                                <button
                                    class="rounded-xl bg-azul px-4 text-sm font-semibold text-white">Adicionar</button>
                            </form>
                        </div>
                    </template>

                    <p v-else class="rounded-2xl bg-white p-6 text-grafite/60 shadow-sm">Escolha um supermercado para
                        carregar a planilha.
                    </p>
                </div>
            </section>

            <!-- Coluna da direita: pré-visualização em HTML (mesmo layout da folha A4); imprime pelo navegador -->
            <aside v-if="!semPrevia"
                class="planilha-previa no-print flex min-h-0 min-w-0 flex-col gap-4 xl:h-full xl:overflow-hidden">
                <Teleport to="#topo-titulo" :disabled="!embutido">
                    <p class="truncate text-base font-bold">{{ planilha?.nome || 'Planilha' }}</p>
                </Teleport>
                <Teleport to="#topo-acoes" :disabled="!embutido">
                    <button
                        class="inline-flex items-center gap-2 rounded-full bg-[#2b579a] px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-[#2b579a]/30 transition hover:bg-[#1e3f73] disabled:opacity-40"
                        :disabled="!folhas.length || exportandoWord" @click="exportarWord"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8z"/><path d="M14 3v5h5M8.5 12l1.5 6 2-4.5 2 4.5 1.5-6"/></svg>{{ exportandoWord ? 'Gerando…' : 'Exportar Word' }}</button>
                    <button
                        class="inline-flex items-center gap-2 rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-emerald-600/30 transition hover:bg-emerald-700 disabled:opacity-40"
                        :disabled="!folhas.length" @click="imprimir"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V3h12v6M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v7H6z"/></svg>Imprimir / PDF</button>
                </Teleport>
                <div id="sheets-box" class="min-h-0 flex-1 xl:overflow-y-auto">
                    <div v-if="folhas.length" id="sheets" class="flex flex-col items-center gap-6 pb-6">
                        <div v-for="(f, i) in folhasDaPrevia" :key="i" class="sheet" :class="{ temperatura: ehTemperatura }" :style="{ zoom: zoomPreview }">
                            <table class="cab-logos">
                                <tr>
                                    <td class="sem-borda esq"><img src="/images/acoop.png" alt="ACCP"
                                            class="logo-acoop"></td>
                                    <td v-if="ehTemperatura" class="sem-borda titulo">CONTROLE DE TEMPERATURA</td>
                                    <td v-else class="sem-borda titulo">PLANILHA DE MONITORAMENTO<br>{{
                                        planilha?.nome?.toUpperCase() || 'HIGIENE E LIMPEZA AMBIENTAL' }}</td>
                                    <td class="sem-borda dir">
                                        <img v-if="logoUrl" :src="logoUrl" alt="Logo" class="logo-mae">
                                        <div v-else class="logo-box">LOGO DO SUPERMERCADO</div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Temperatura: a legenda dos produtos tem uma folha só, no começo -->
                            <table v-if="f.legenda" class="legenda-temp">
                                <colgroup>
                                    <col style="width:50%">
                                    <col style="width:50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>PRODUTOS</th>
                                        <th>TEMPERATURA</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="[produto, temperatura] in LEGENDA_TEMPERATURA" :key="produto">
                                        <td>{{ produto }}</td>
                                        <td>{{ temperatura }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <!-- Temperatura, modelo equipamentos: topo 2x2 com o setor e a legenda menor ao lado -->
                            <div v-else-if="modeloEquipamentos" class="equip-topo">
                                <table class="topo temp equip">
                                    <tr>
                                        <td style="width:50%"><b>CLIENTE:</b><span class="valor">{{ (nomeSocial || '').toUpperCase() }}</span></td>
                                        <td style="width:50%"><b>SETOR:</b><span class="valor">{{ f.titulo }}</span></td>
                                    </tr>
                                    <tr>
                                        <td><b>MÊS/ANO:</b></td>
                                        <td><b>ENCARREGADO:</b></td>
                                    </tr>
                                </table>
                                <table class="legenda-curta">
                                    <colgroup>
                                        <col style="width:45%">
                                        <col style="width:55%">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th>PRODUTOS</th>
                                            <th>TEMPERATURA</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="([produto, temperatura], i) in f.legendaCurta" :key="i">
                                            <td>{{ produto }}</td>
                                            <td>{{ temperatura }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <table v-else-if="ehTemperatura" class="topo temp">
                                <tr>
                                    <td style="width:25%"><b>CLIENTE:</b><span class="valor">{{ (nomeSocial || '').toUpperCase() }}</span>
                                    </td>
                                    <td style="width:25%"><b>SETOR:</b><span class="valor">{{ f.secao.nome }}</span></td>
                                    <td style="width:25%"><b>RESPONSÁVEL:</b></td>
                                    <td style="width:25%"><b>PERÍODO:</b></td>
                                </tr>
                            </table>

                            <table v-if="!ehTemperatura" class="topo">
                                <tr>
                                    <td style="width:25%"><b>CLIENTE:</b><span class="valor">{{ (nomeSocial || '').toUpperCase() }}</span>
                                    </td>
                                    <td style="width:25%"><b>ENCARREGADO:</b></td>
                                    <td style="width:25%"><b>SETOR:</b><span class="valor">{{ f.secao.nome }}</span>
                                    </td>
                                    <td style="width:25%"><b>MÊS/ANO:</b></td>
                                </tr>
                            </table>
                            <table v-if="!ehTemperatura" class="topo local">
                                <tr>
                                    <td style="width:50%"><b>LOCAL:</b></td>
                                    <td style="width:50%"><b>DATA:</b></td>
                                </tr>
                            </table>

                            <!-- Modelo equipamentos: dias nas linhas, um equipamento por coluna -->
                            <div v-if="!f.legenda && modeloEquipamentos" class="grade-caixa">
                                <table class="grade equip">
                                    <colgroup>
                                        <col style="width:50px">
                                        <col style="width:70px">
                                        <col v-for="(e, k) in f.equipamentos" :key="k">
                                        <col v-if="!f.equipamentos.length">
                                        <col style="width:170px">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th rowspan="2" class="faixa-cabecalho">DATA</th>
                                            <th rowspan="2" class="faixa-cabecalho">HORA</th>
                                            <th :colspan="Math.max(1, f.equipamentos.length)" class="faixa-cabecalho">TEMPERATURA DOS EQUIPAMENTOS</th>
                                            <th rowspan="2" class="faixa-cabecalho">ASSINATURA</th>
                                        </tr>
                                        <tr>
                                            <th v-for="(e, k) in f.equipamentos" :key="k" class="equipamento">{{ e.texto }}</th>
                                            <th v-if="!f.equipamentos.length" class="equipamento"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="d in f.dias" :key="d">
                                            <td>{{ String(d).padStart(2, '0') }}</td>
                                            <td></td>
                                            <td v-for="(e, k) in f.equipamentos" :key="k"></td>
                                            <td v-if="!f.equipamentos.length"></td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Demais: uma grade por intervalo de dias (na temperatura, 1 a 16 e 17 a 31 podem dividir a folha) -->
                            <template v-else-if="!f.legenda">
                            <div v-for="(dias, g) in f.grades" :key="g" class="grade-caixa">
                                <table class="grade">
                                    <colgroup>
                                        <col :style="{ width: (ehTemperatura ? LARGURA_PRIMEIRA_COLUNA_TEMPERATURA_PX : 160) + 'px' }">
                                        <col v-for="d in dias" :key="d">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th class="faixa-cabecalho">{{ f.titulo }}</th>
                                            <th v-for="d in dias" :key="d" class="faixa-cabecalho">{{ d }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template v-for="(l, j) in f.linhas" :key="j">
                                            <!-- Temperatura: a faixa repete o cabeçalho, com o texto à esquerda e os dias -->
                                            <tr v-if="l.tipo === 'grupo' && ehTemperatura" class="faixa"
                                                :style="{ height: alturaDaLinha(l) + 'px' }">
                                                <td>{{ l.texto }}</td>
                                                <td v-for="d in dias" :key="d">{{ d }}</td>
                                            </tr>
                                            <tr v-else-if="l.tipo === 'grupo'" class="faixa"
                                                :style="{ height: alturaDaLinha(l) + 'px' }">
                                                <td :colspan="dias.length + 1">{{ l.texto }}</td>
                                            </tr>
                                            <tr v-else :style="{ height: alturaDaLinha(l) + 'px' }">
                                                <td>{{ l.texto }}</td>
                                                <td v-for="d in dias" :key="d"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                            </template>
                            <div class="rodape">
                                <p class="rodape-nome">ACCP Alimentos &amp; Tecnologia | contato@accpconsultoria.com.br
                                </p>
                                <p>Av. Humberto Monte, 2929, Sala 501, Torre Sul – Pici, Fortaleza/CE</p>
                            </div>
                        </div>
                    </div>
                    <p v-else class="rounded-2xl bg-white p-6 text-grafite/60 shadow-sm">Selecione um supermercado e
                        pelo menos uma
                        seção para ver a pré-visualização.</p>
                </div>
            </aside>
        </div>
    </div>
</template>

<style>
/* Folha A4 paisagem em HTML (mesmo layout do PDF). Largura de referência: 1123px (297mm a 96 dpi). */
.sheet {
    width: 1123px;
    display: flex;
    flex-direction: column;
    background: #fff;
    color: #000;
    font-family: Times, 'Times New Roman', serif;
    font-size: 9px;
    box-sizing: border-box;
    height: 794px;
    /* A4 paisagem (210mm) sempre, mesmo com poucas linhas */
    overflow: hidden;
    padding: 22px 36px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, .12);
}

.sheet table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.sheet td,
.sheet th {
    border: 1px solid #000;
    padding: 0;
    text-align: center;
    vertical-align: middle;
}

.sheet .sem-borda,
.sheet .sem-borda td {
    border: 0;
}

.sheet .cab-logos td {
    height: 50px;
}

.sheet .cab-logos .esq {
    text-align: left;
    width: 200px;
}

.sheet .cab-logos .dir {
    text-align: right;
    width: 200px;
}

.sheet .titulo {
    font-size: 14px;
    font-weight: bold;
    line-height: 1.25;
}

.sheet .logo-acoop,
.sheet .logo-mae {
    height: 40px;
    display: inline-block;
}

.sheet .logo-box {
    display: inline-block;
    width: 120px;
    height: 40px;
    line-height: 40px;
    border: 1px dashed #777;
    font-size: 7px;
    color: #555;
    text-align: center;
}

.sheet .topo {
    margin-bottom: 0;
}

.sheet .topo td {
    height: 36px;
    vertical-align: top;
    padding-top: 4px;
    font-size: 9px;
}

.sheet .topo:not(.local) td {
    border-bottom: 0;
}

/* a linha de baixo é a do bloco LOCAL/DATA, evita borda dupla */
.sheet .topo .valor {
    display: block;
    font-size: 10px;
}

.sheet .topo.local {
    margin-top: -1px;
}

.sheet .topo.local td {
    height: 26px;
    vertical-align: middle;
    text-align: left;
    padding-left: 4px;
}

.sheet .grade {
    margin-top: 0;
}

.sheet .grade-caixa {
    margin-top: 12px;
}

/* espaço entre o bloco LOCAL/DATA e a grade */
.sheet .rodape {
    margin-top: auto;
    padding-top: 3px;
    text-align: center;
    font-size: 8px;
    line-height: 10px;
    color: #9a9a9a;
}

.sheet .rodape p {
    margin: 0;
}

.sheet .rodape .rodape-nome {
    font-size: 9px;
    font-weight: bold;
    color: #7a7a7a;
}

.sheet .grade th {
    font-weight: bold;
    height: 20px;
    font-size: 8.5px;
    line-height: 11px;
}

.sheet .grade td {
    height: 20px;
    font-size: 8.5px;
    line-height: 11px;
}

.sheet .grade .faixa td {
    background: #d9d9d9;
    font-weight: bold;
}

/* Temperatura: 16 dias por folha, células e fonte maiores para anotar à mão */
.sheet.temperatura .grade th,
.sheet.temperatura .grade td {
    height: 26px;
    font-size: 11px;
    line-height: 13px;
}

.sheet .grade th.faixa-cabecalho {
    background: #d9d9d9;
}

/* Impressão: só a folha, em A4 paisagem */
@media print {
    @page {
        size: A4 landscape;
        margin: 8mm;
    }

    body * {
        visibility: hidden;
    }

    #sheets,
    #sheets * {
        visibility: visible;
    }

    #sheets {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        display: block;
        gap: 0;
        padding: 0;
    }

    #sheets-box {
        overflow: visible !important;
        height: auto !important;
    }

    .sheet {
        zoom: 1 !important;
        box-shadow: none;
        padding: 0;
        width: 100%;
        height: 194mm;
        overflow: hidden;
        break-after: page;
        page-break-after: always;
    }

    .sheet:last-child {
        break-after: auto;
        page-break-after: auto;
    }

    .sheet .grade td,
    .sheet .grade th,
    .sheet .topo td,
    .sheet .cab-logos td {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .sheet .grade .faixa td {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .sheet .grade th.faixa-cabecalho {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}

/* Temperatura: topo com CLIENTE | SETOR | RESPONSÁVEL | PERÍODO (25% cada), sem o bloco LOCAL/DATA embaixo */
.sheet .topo.temp td {
    border-bottom: 1px solid #000;
}

/* Temperatura, modelo equipamentos: topo 2x2 (60%) e legenda menor (40%) lado a lado */
.sheet .equip-topo {
    display: grid;
    grid-template-columns: minmax(0, 6fr) minmax(0, 4fr);
    align-items: stretch;
    gap: 8px;
}

.sheet .topo.temp.equip {
    height: 100%;
}

/* Altura fixa para preencher MÊS/ANO e ENCARREGADO, mesmo quando a legenda do setor é curta (peixaria) */
.sheet .topo.temp.equip td {
    height: 44px;
}

/* A legenda fica com as linhas compactas, sem esticar até a altura do topo */
.sheet .legenda-curta {
    align-self: start;
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.sheet .legenda-curta th,
.sheet .legenda-curta td {
    border: 1px solid #000;
    padding: 0 4px;
    height: 13px;
    text-align: left;
    font-size: 9px;
    line-height: 10px;
}

.sheet .legenda-curta th {
    background: #d9d9d9;
    font-weight: bold;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* Dias nas linhas (16 ou 15 por folha) e um equipamento por coluna; linhas altas para escrever */
.sheet.temperatura .grade.equip th,
.sheet.temperatura .grade.equip td {
    height: 26px;
    font-size: 13px;
    line-height: 14px;
}

.sheet.temperatura .grade.equip thead th {
    height: 20px;
}

.sheet.temperatura .grade.equip th.equipamento {
    height: 32px;
    padding: 2px;
    font-size: 11px;
    line-height: 12px;
    background: #fff;
}

/* Folha da legenda dos produtos (uma só, no começo da impressão) */
.sheet .legenda-temp {
    width: 100%;
    margin-top: 16px;
    border-collapse: collapse;
    table-layout: fixed;
}

.sheet .legenda-temp th,
.sheet .legenda-temp td {
    border: 1px solid #000;
    padding: 2px 8px;
    height: 30px;
    text-align: center;
    font-size: 13px;
    line-height: 16px;
}

.sheet .legenda-temp th {
    background: #d9d9d9;
    font-weight: bold;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* Temperatura: controles e folhas com rolagens independentes. */
@media screen and (min-width: 1280px) {
    body.tela-temperatura {
        height: 100vh;
        height: 100dvh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .tela-temperatura .audix-topbar,
    .tela-temperatura .planilha-breadcrumb { flex-shrink: 0; }
    .tela-temperatura #app {
        flex: 1;
        min-height: 0;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        padding-bottom: 24px;
    }
    .tela-temperatura .planilha-workspace {
        flex: 1;
        min-height: 0;
        overflow: hidden;
    }
    .tela-temperatura .planilha-colunas {
        min-height: 0;
        height: 100%;
        align-items: stretch;
    }
    .tela-temperatura .planilha-controles,
    .tela-temperatura .planilha-previa {
        position: static;
        height: 100%;
        min-height: 0;
        min-width: 0;
        align-self: stretch;
    }
    .tela-temperatura .planilha-controles {
        overflow-y: auto;
        overflow-x: hidden;
        padding-right: 12px;
        padding-bottom: 20px;
    }
    .tela-temperatura .planilha-controles-conteudo {
        flex: 0 0 auto;
        min-height: auto;
        overflow: visible;
        padding-right: 0;
    }
    .tela-temperatura .planilha-previa { overflow: hidden; }
    .tela-temperatura .planilha-previa > :first-child { flex-shrink: 0; }
    .tela-temperatura #sheets-box {
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        padding-right: 12px;
    }
    .tela-temperatura .planilha-controles,
    .tela-temperatura #sheets-box {
        overscroll-behavior-y: contain;
        scrollbar-gutter: stable;
        scrollbar-width: thin;
        scrollbar-color: #a5b5d4 transparent;
    }
    .dark .tela-temperatura .planilha-controles,
    .dark .tela-temperatura #sheets-box { scrollbar-color: #465572 transparent; }
}
@media print {
    body.tela-temperatura,
    .tela-temperatura #app,
    .tela-temperatura .planilha-workspace,
    .tela-temperatura .planilha-colunas,
    .tela-temperatura .planilha-previa,
    .tela-temperatura #sheets-box {
        display: block !important;
        position: static !important;
        height: auto !important;
        min-height: 0 !important;
        max-height: none !important;
        overflow: visible !important;
        padding: 0 !important;
        margin: 0 !important;
        scrollbar-gutter: auto;
    }
    .tela-temperatura .planilha-controles,
    .tela-temperatura .planilha-breadcrumb,
    .tela-temperatura .planilha-previa > :first-child { display: none !important; }
}

</style>
