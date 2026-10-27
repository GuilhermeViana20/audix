<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { lerPlanilhaPdf } from './importarPdf.js';

const TOKEN = document.querySelector('meta[name="csrf-token"]').content;
const JSON_HEADERS = { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': TOKEN };

const embutido = !!window.AUDIX.embutido; // sem seletor de supermercado nem logo (fluxo e edição)
const semPrevia = !!window.AUDIX.semPrevia; // tela de edição do supermercado: só o editor
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

const ordenadas = computed(() => [...secoes.value].sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR')));
const idsSelecionados = computed(() => ordenadas.value.filter(s => selecionadas.value[s.id]).map(s => s.id));
// Folhas da pré-visualização HTML: uma seção pode gerar várias folhas se tiver muitas linhas.
const LIMITE_LINHAS_PX = 500; // espaço vertical da folha A4 reservado às linhas (cabeçalho ocupa o resto)
const ALTURA_LINHA_PX = 18;
function paginar(linhas) {
    const paginas = [];
    let atual = [];
    let altura = 0;
    for (const linha of linhas) {
        if (altura + ALTURA_LINHA_PX > LIMITE_LINHAS_PX && atual.length) {
            paginas.push(atual);
            atual = [];
            altura = 0;
        }
        atual.push(linha);
        altura += ALTURA_LINHA_PX;
    }
    paginas.push(atual);
    return paginas;
}
const folhas = computed(() => ordenadas.value
    .filter(s => selecionadas.value[s.id])
    .flatMap(s => paginar(s.linhas).map((linhas, i) => ({ secao: s, linhas, parte: i + 1 }))));

// Ajusta o zoom das folhas para caber na coluna de pré-visualização (só na tela)
const zoomPreview = ref(1);
function ajustarZoom() {
    const caixa = document.getElementById('sheets-box');
    if (!caixa) return;
    zoomPreview.value = Math.max(0.3, Math.min(1, (caixa.clientWidth - 24) / 1040));
}
// Registra o relatório (planilha impressa) e abre a impressão do navegador
async function imprimir() {
    if (planilha.value && idsSelecionados.value.length) {
        try {
            await rede(`/api/planilhas/${planilha.value.id}/relatorios`, {
                method: 'POST',
                headers: JSON_HEADERS,
                body: JSON.stringify({ secoes: idsSelecionados.value }),
            });
        } catch (e) { /* o registro não impede a impressão */ }
    }
    window.print();
}

// Assinatura do que é salvo no banco para uma seção (título + linhas).
function assinatura(s, tit) {
    return JSON.stringify({ t: tit, l: s.linhas.map(l => [l.tipo, l.texto]) });
}
const estaSujo = computed(() => !!atual.value && assinatura(atual.value, titulo.value) !== atual.value._salvo);
const textoStatus = computed(() => ['Salvando…', 'Erro ao salvar'].includes(status.value)
    ? status.value
    : (estaSujo.value ? 'Alterações não salvas' : 'Tudo salvo'));

function msg(texto) { aviso.value = texto; setTimeout(() => (aviso.value = ''), 3500); }
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
    const dados = await api(`/api/supermercados/${supermercadoId.value}/planilhas/higiene`);
    planilha.value = dados.planilha;
    secoes.value = mapearSecoes(dados.secoes);
    dados.secoes.forEach(s => { if (selecionadas.value[s.id] === undefined) selecionadas.value[s.id] = true; });
    if (atual.value) atual.value = secoes.value.find(s => s.id === atual.value.id) || null;
    atualizarPreview();
}

async function carregarLogo() {
    const r = await rede(`/api/supermercados/${supermercadoId.value}/logo`, { headers: { Accept: 'application/json' } });
    logoUrl.value = (await r.json()).logo_url;
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
        await carregarLogo();
        if (!embutido) history.replaceState(null, '', `/higiene/${id}`);
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
            body: JSON.stringify({ linhas: s.linhas.map(l => ({ tipo: l.tipo, texto: l.texto })) }),
        });
        if (tit !== s.titulo_primeira_coluna) {
            await api(`/api/secoes/${s.id}`, {
                method: 'PUT',
                body: JSON.stringify({ nome: s.nome, titulo_primeira_coluna: tit }),
            });
            s.titulo_primeira_coluna = tit;
        }
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

async function enviarLogo(evento) {
    const arquivo = evento.target.files[0];
    if (!arquivo) return;
    const corpo = new FormData();
    corpo.append('logo', arquivo);
    try {
        const r = await rede(`/api/supermercados/${supermercadoId.value}/logo`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': TOKEN, Accept: 'application/json' },
            body: corpo,
        });
        const dados = await r.json();
        if (!r.ok) throw new Error(dados.message || 'Não foi possível enviar a logo');
        logoUrl.value = dados.logo_url;
        msg('Logo atualizada');
        atualizarPreview();
    } catch (e) { msg(e.message); }
    finally { evento.target.value = ''; }
}

async function voltarLogoPadrao() {
    try {
        await api(`/api/supermercados/${supermercadoId.value}/logo`, { method: 'DELETE' });
        logoUrl.value = null;
        msg('Logo padrão restaurada');
        atualizarPreview();
    } catch (e) { msg(e.message); }
}

// ---------- Importação de PDF ----------
// As seções lidas ficam em "rascunho" no popup; só são gravadas ao confirmar.
const rascunho = ref(null);
const lendoPdf = ref(false);
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
            linhas: s.linhas.map(l => ({ _k: novaChave(), tipo: l.tipo, texto: l.texto })),
        }));
    } catch (e) {
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

// Arrastar linhas no popup (pointer events: funciona com toque e mouse).
// Enquanto arrasta, a linha de destino fica destacada; ao soltar, a linha é movida.
const arrasto = ref(null); // { grupo, lista, de, para }
function iniciarArrasto(evento, grupo, lista, i) {
    evento.preventDefault();
    evento.currentTarget.setPointerCapture(evento.pointerId);
    arrasto.value = { grupo, lista, de: i, para: i };
}
function moverArrasto(evento) {
    if (!arrasto.value) return;
    const alvo = document.elementFromPoint(evento.clientX, evento.clientY)?.closest('[data-linha]');
    if (alvo && alvo.dataset.grupo === String(arrasto.value.grupo)) {
        arrasto.value.para = Number(alvo.dataset.linha);
    }
}
function soltarArrasto() {
    if (!arrasto.value) return;
    const { lista, de, para } = arrasto.value;
    if (de !== para) {
        const [item] = lista.splice(de, 1);
        lista.splice(para, 0, item);
    }
    arrasto.value = null;
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
    ajustarZoom();
    if (supermercadoId.value) await carregarSupermercado(supermercadoId.value);
});
onBeforeUnmount(() => window.removeEventListener('beforeunload', avisarAntesDeSair));
</script>

<template>
    <!-- Loading enquanto houver requisições em andamento (salvar, carregar, PDF) -->
    <div v-if="carregandoRede" class="fixed inset-0 z-50 flex items-center justify-center bg-grafite/20 backdrop-blur-[1px]" role="status" aria-live="polite">
        <div class="flex items-center gap-3 rounded-2xl bg-white px-5 py-3 text-sm font-semibold shadow-lg">
            <span class="h-4 w-4 animate-spin rounded-full border-2 border-azul border-t-transparent"></span>
            Carregando…
        </div>
    </div>

    <!-- Popup de conferência da importação: nada é gravado antes de confirmar -->
    <div v-if="rascunho" class="fixed inset-0 z-40 flex items-end justify-center bg-grafite/50 sm:items-center sm:p-4" role="dialog" aria-modal="true">
        <div class="flex max-h-[92vh] w-full max-w-3xl flex-col rounded-t-2xl bg-white shadow-xl sm:rounded-2xl">
            <div class="border-b border-grafite/10 p-4 md:p-5">
                <h2 class="text-lg font-bold">Confirmar importação</h2>
                <p class="text-xs text-grafite/60">Revise seções, linhas e faixas. Nada é salvo no banco até você clicar em "Confirmar e salvar".</p>
            </div>

            <div class="flex-1 space-y-5 overflow-y-auto p-4 md:p-5">
                <div v-for="(s, i) in rascunho" :key="s._k" class="rounded-xl border border-grafite/10 p-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <input v-model="s.nome" placeholder="Nome da seção" class="min-w-0 flex-1 rounded-lg bg-offwhite px-3 py-2 text-sm font-bold uppercase">
                        <button class="px-1 text-azul disabled:opacity-30" :disabled="i === 0" @click="moverItem(rascunho, i, -1)">↑</button>
                        <button class="px-1 text-azul disabled:opacity-30" :disabled="i === rascunho.length - 1" @click="moverItem(rascunho, i, 1)">↓</button>
                        <button class="text-sm text-red-500" @click="rascunho.splice(i, 1)">Remover seção</button>
                    </div>
                    <label class="mt-3 block text-xs text-grafite/60">Título da primeira coluna</label>
                    <input v-model="s.titulo_primeira_coluna" class="mt-1 w-full rounded-lg bg-offwhite px-3 py-2 text-sm">

                    <div class="mt-3 space-y-2">
                        <div v-for="(l, j) in s.linhas" :key="l._k" :data-linha="j" :data-grupo="s._k"
                             class="flex items-center gap-2 rounded-lg transition"
                             :class="arrasto && arrasto.grupo === s._k && arrasto.para === j && arrasto.de !== j ? 'ring-2 ring-azul' : ''">
                            <select v-model="l.tipo" class="w-20 shrink-0 rounded-lg bg-offwhite px-2 py-2 text-xs">
                                <option value="linha">Linha</option>
                                <option value="grupo">Faixa</option>
                            </select>
                            <input v-model="l.texto" class="min-w-0 flex-1 rounded-lg px-3 py-2 text-sm"
                                   :class="l.tipo === 'grupo' ? 'bg-grafite/10 font-bold uppercase' : 'bg-white'">
                            <span class="touch-none cursor-grab select-none px-1 text-xl leading-none text-grafite/40 active:cursor-grabbing"
                                  title="Arraste para mover"
                                  @pointerdown="iniciarArrasto($event, s._k, s.linhas, j)"
                                  @pointermove="moverArrasto"
                                  @pointerup="soltarArrasto"
                                  @pointercancel="soltarArrasto">⠿</span>
                            <button class="px-1 text-red-500" @click="s.linhas.splice(j, 1)">×</button>
                        </div>
                    </div>

                    <div class="mt-3 flex gap-2 text-sm">
                        <button class="rounded-lg bg-offwhite px-3 py-2 font-medium text-azul" @click="s.linhas.push({ _k: novaChave(), tipo: 'linha', texto: '' })">+ Linha</button>
                        <button class="rounded-lg bg-offwhite px-3 py-2 font-medium text-azul" @click="s.linhas.push({ _k: novaChave(), tipo: 'grupo', texto: 'NOVA FAIXA' })">+ Faixa</button>
                    </div>
                </div>

                <button class="w-full rounded-xl border border-dashed border-azul py-3 text-sm font-semibold text-azul"
                        @click="rascunho.push({ _k: novaChave(), nome: '', titulo_primeira_coluna: 'ÁREA DE ATENDIMENTO', linhas: [] })">+ Nova seção</button>
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-grafite/10 p-4 md:p-5">
                <button class="text-sm font-medium text-grafite/70" @click="rascunho = null">Cancelar</button>
                <button class="rounded-xl bg-azul px-5 py-2.5 text-sm font-semibold text-white" @click="confirmarImportacao">Confirmar e salvar</button>
            </div>
        </div>
    </div>

    <!-- Popup para copiar faixa (ou título) e linhas para outra seção -->
    <div v-if="copiaPendente" class="fixed inset-0 z-40 flex items-end justify-center bg-grafite/50 sm:items-center sm:p-4" role="dialog" aria-modal="true">
        <div class="flex max-h-[85vh] w-full max-w-md flex-col rounded-t-2xl bg-white shadow-xl sm:rounded-2xl">
            <div class="border-b border-grafite/10 p-4">
                <h2 class="text-lg font-bold">Copiar para outra seção</h2>
                <p class="text-sm text-grafite/70">“{{ copiaPendente.resumo }}” com {{ copiaPendente.copia.length - 1 }} linha(s) abaixo. Será adicionada ao fim da seção escolhida.</p>
            </div>
            <div class="flex-1 space-y-2 overflow-y-auto p-4">
                <p v-if="!copiaPendente.destinos.length" class="text-sm text-grafite/60">Não há outras seções para copiar.</p>
                <label v-for="d in copiaPendente.destinos" :key="d.id"
                       class="flex cursor-pointer items-center gap-3 rounded-xl bg-offwhite px-4 py-3 text-sm font-medium"
                       :class="copiaPendente.escolhido === d.id && 'ring-2 ring-azul'">
                    <input type="radio" name="destino-copia" :value="d.id" v-model="copiaPendente.escolhido" class="accent-azul h-4 w-4">
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

    <div class="flex min-h-screen">
        <div :class="semPrevia ? 'grid w-full gap-6' : 'grid w-full gap-6 xl:grid-cols-[2fr_3fr]'">
            <!-- Coluna dos controles: no celular fica em cima; no computador, à esquerda -->
            <section class="min-w-0 space-y-5">
                <div v-if="!embutido" class="rounded-2xl bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-grafite/50">Supermercado</p>
                    <select class="mt-2 w-full rounded-xl bg-offwhite px-3 py-2 text-sm font-medium"
                            :value="supermercadoId ?? ''"
                            @change="carregarSupermercado(Number($event.target.value))">
                        <option value="" disabled>Escolha o supermercado</option>
                        <option v-for="m in supermercados" :key="m.id" :value="m.id">{{ m.nome }}</option>
                    </select>
                </div>

                <template v-if="supermercadoId">
                    <div v-if="!embutido" class="rounded-2xl bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase text-grafite/50">Logo do supermercado</p>
                        <div class="mt-3 flex items-center gap-4">
                            <div class="flex h-24 w-40 items-center justify-center rounded-xl bg-offwhite">
                                <img v-if="logoUrl" :src="logoUrl" class="max-h-20 max-w-full object-contain" alt="Logo">
                                <span v-else class="text-xs font-bold text-grafite/50 text-center">LOGO DO SUPERMERCADO</span>
                            </div>
                            <div class="grid gap-2 text-sm">
                                <label class="cursor-pointer rounded-xl bg-azul px-4 py-2 text-center font-semibold text-white">
                                    Trocar logo
                                    <input type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="hidden" @change="enviarLogo">
                                </label>
                                <button class="text-azul font-medium" @click="voltarLogoPadrao">Voltar ao padrão</button>
                            </div>
                        </div>
                    </div>

                    <p v-if="aviso" class="rounded-xl bg-white px-4 py-2 text-sm shadow-sm">{{ aviso }}</p>

                    <div class="rounded-2xl bg-white p-4 shadow-sm" :class="carregando && 'opacity-60'">
                        <p class="text-xs font-semibold uppercase text-grafite/50">Seções no arquivo</p>
                        <div class="mt-3 flex gap-2 text-sm">
                            <button class="text-azul font-medium" @click="ordenadas.forEach(s => selecionadas[s.id] = true)">Marcar todas</button>
                            <button class="text-azul font-medium" @click="ordenadas.forEach(s => selecionadas[s.id] = false)">Nenhuma</button>
                            <label class="ml-auto cursor-pointer rounded-xl bg-azul px-3 py-1.5 font-semibold text-white"
                                   :class="lendoPdf && 'pointer-events-none opacity-50'">
                                {{ lendoPdf ? 'Lendo PDF…' : 'Importar PDF' }}
                                <input type="file" accept="application/pdf" class="hidden" @change="escolherPdf">
                            </label>
                        </div>
                        <p v-if="planilha" class="mt-2 text-xs text-grafite/60">Tipo de planilha: <b class="text-grafite">{{ planilha.nome }}</b></p>

                        <div class="mt-3 space-y-2">
                            <div v-for="s in ordenadas" :key="s.id"
                                 class="rounded-xl border transition"
                                 :class="atual?.id === s.id ? 'border-azul bg-azul/5' : 'border-transparent bg-offwhite'">
                                <div class="flex cursor-pointer flex-wrap items-center gap-3 p-3"
                                     @click="atual?.id === s.id ? fechar() : abrir(s)">
                                    <label class="flex items-center gap-2 font-medium" @click.stop>
                                        <input type="checkbox" v-model="selecionadas[s.id]" class="accent-azul h-4 w-4">
                                        {{ s.nome }}
                                    </label>
                                    <span class="text-xs text-grafite/50">{{ s.linhas.length }} linhas</span>
                                    <span class="ml-auto text-xs font-semibold text-azul">{{ atual?.id === s.id ? 'Fechar ▲' : 'Editar ▼' }}</span>
                                    <button class="text-sm text-azul" @click.stop="renomear(s)">Renomear</button>
                                    <button class="text-red-500" @click.stop="excluirSecao(s)">×</button>
                                </div>

                                <div v-if="atual?.id === s.id" class="border-t border-grafite/10 p-4">
                                    <div class="flex items-center justify-end">
                                        <span class="text-xs font-semibold"
                                              :class="status === 'Erro ao salvar' ? 'text-red-500' : (estaSujo ? 'text-amber-600' : 'text-grafite/50')">{{ textoStatus }}</span>
                                    </div>
                                    <label class="block text-sm text-grafite/70">Título da primeira coluna</label>
                                    <div class="mt-1 flex items-center gap-2">
                                        <button class="shrink-0 rounded-lg bg-white px-2 py-2 text-azul" title="Copiar título e linhas para outra seção" @click="abrirCopia('titulo')"><svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button>
                                        <input v-model="titulo" class="min-w-0 flex-1 rounded-xl bg-grafite/10 px-3 py-2 text-sm font-bold uppercase">
                                    </div>

                                    <div class="mt-4 space-y-2">
                                        <div v-for="(l, i) in atual.linhas" :key="l.id ?? i" :data-linha="i" :data-grupo="atual.id"
                                             class="flex flex-wrap items-center gap-2 rounded-lg transition"
                                             :class="arrasto && arrasto.grupo === atual.id && arrasto.para === i && arrasto.de !== i ? 'ring-2 ring-azul' : ''">
                                            <select v-model="l.tipo" class="w-20 shrink-0 rounded-lg bg-offwhite px-2 py-2 text-xs">
                                                <option value="linha">Linha</option>
                                                <option value="grupo">Faixa</option>
                                            </select>
                                            <button v-if="l.tipo === 'grupo'" class="shrink-0 rounded-lg bg-offwhite px-2 py-2 text-azul" title="Copiar faixa e linhas para outra seção" @click="abrirCopia('faixa', i)"><svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button>
                                            <input v-model="l.texto" class="flex-1 rounded-xl px-3 py-2 text-sm"
                                                   :class="l.tipo === 'grupo' ? 'bg-grafite/10 font-bold uppercase' : 'bg-white'">
                                            <span class="touch-none cursor-grab select-none px-1 text-xl leading-none text-grafite/40 active:cursor-grabbing"
                                                  title="Arraste para mover"
                                                  @pointerdown="iniciarArrasto($event, atual.id, atual.linhas, i)"
                                                  @pointermove="moverArrasto"
                                                  @pointerup="soltarArrasto"
                                                  @pointercancel="soltarArrasto">⠿</span>
                                            <button class="px-1 text-xs font-semibold text-azul" title="Inserir linha acima" @click="inserirLinha(i, 0)">+↑</button>
                                            <button class="px-1 text-xs font-semibold text-azul" title="Inserir linha abaixo" @click="inserirLinha(i, 1)">+↓</button>
                                            <button class="px-1 text-red-500" @click="removerLinha(i)">×</button>
                                        </div>
                                    </div>

                                    <div class="mt-4 flex flex-wrap gap-2 text-sm">
                                        <button class="rounded-xl bg-white px-3 py-2 font-medium text-azul" @click="adicionarLinha">+ Linha</button>
                                        <button class="rounded-xl bg-white px-3 py-2 font-medium text-azul" @click="adicionarGrupo">+ Faixa</button>
                                        <button class="ml-auto rounded-xl bg-white px-3 py-2 font-medium" @click="copiarParaTodas">Copiar para todas</button>
                                        <button class="rounded-xl bg-azul px-4 py-2 font-semibold text-white disabled:opacity-40"
                                                :disabled="!estaSujo || status === 'Salvando…'" @click="salvar">Salvar</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form class="mt-3 flex gap-2" @submit.prevent="adicionarSecao">
                            <input v-model="novaSecao" placeholder="Nova seção (ex.: PADARIA)" class="flex-1 rounded-xl bg-offwhite px-3 py-2 text-sm">
                            <button class="rounded-xl bg-azul px-4 text-sm font-semibold text-white">Adicionar</button>
                        </form>
                    </div>
                </template>

                <p v-else class="rounded-2xl bg-white p-6 text-grafite/60 shadow-sm">Escolha um supermercado para carregar a planilha.</p>
            </section>

            <!-- Coluna da direita: pré-visualização em HTML (mesmo layout da folha A4); imprime pelo navegador -->
            <aside v-if="!semPrevia" class="no-print flex min-w-0 flex-col gap-4 xl:sticky xl:top-4 xl:h-[calc(100vh-2rem)] xl:overflow-hidden">
                <div class="flex items-center justify-between rounded-2xl bg-white px-5 py-4 shadow-sm">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-azul">Pré-visualização</p>
                        <p class="text-lg font-bold">{{ planilha?.nome || 'Planilha' }}</p>
                        <p class="text-xs text-grafite/60">{{ folhas.length }} página(s) · {{ idsSelecionados.length }} seção(ões) · A4 paisagem</p>
                    </div>
                    <button class="rounded-xl bg-azul px-3 py-2 text-sm font-semibold text-white disabled:opacity-40"
                            :disabled="!folhas.length" @click="imprimir">Imprimir / PDF</button>
                </div>
                <div id="sheets-box" class="min-h-0 flex-1 xl:overflow-y-auto">
                    <div v-if="folhas.length" id="sheets" class="flex flex-col items-center gap-6 pb-6">
                        <div v-for="(f, i) in folhas" :key="i" class="sheet" :style="{ zoom: zoomPreview }">
                            <table class="cab-logos">
                                <tr>
                                    <td class="sem-borda esq"><img src="/images/acoop.png" alt="ACCP" class="logo-acoop"></td>
                                    <td class="sem-borda titulo">PLANILHA DE MONITORAMENTO<br>{{ planilha?.nome?.toUpperCase() || 'HIGIENE E LIMPEZA AMBIENTAL' }}</td>
                                    <td class="sem-borda dir">
                                        <img v-if="logoUrl" :src="logoUrl" alt="Logo" class="logo-mae">
                                        <div v-else class="logo-box">LOGO DO SUPERMERCADO</div>
                                    </td>
                                </tr>
                            </table>

                            <table class="topo">
                                <tr>
                                    <td style="width:33.33%"><b>ENCARREGADO:</b></td>
                                    <td style="width:33.33%"><b>SETOR:</b><span class="valor">{{ f.secao.nome }}</span></td>
                                    <td style="width:33.34%"><b>MÊS/ANO:</b></td>
                                </tr>
                            </table>
                            <table class="topo local">
                                <tr>
                                    <td style="width:50%"><b>LOCAL:</b></td>
                                    <td style="width:50%"><b>DATA:</b></td>
                                </tr>
                            </table>

                            <table class="grade">
                                <colgroup>
                                    <col style="width:160px">
                                    <col v-for="d in 31" :key="d">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th class="faixa-cabecalho">{{ f.secao.titulo_primeira_coluna }}</th>
                                        <th v-for="d in 31" :key="d">{{ d }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="(l, j) in f.linhas" :key="j">
                                        <tr v-if="l.tipo === 'grupo'" class="faixa"><td :colspan="32">{{ l.texto }}</td></tr>
                                        <tr v-else>
                                            <td>{{ l.texto }}</td>
                                            <td v-for="d in 31" :key="d"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <p v-else class="rounded-2xl bg-white p-6 text-grafite/60 shadow-sm">Selecione um supermercado e pelo menos uma seção para ver a pré-visualização.</p>
                </div>
            </aside>
        </div>
    </div>
</template>

<style>
/* Folha A4 paisagem em HTML (mesmo layout do PDF). Largura de referência: 1040px. */
.sheet {
    width: 1040px;
    background: #fff;
    color: #000;
    font-family: Times, 'Times New Roman', serif;
    font-size: 9px;
    box-sizing: border-box;
    height: 794px; /* A4 paisagem (210mm) sempre, mesmo com poucas linhas */
    overflow: hidden;
    padding: 22px 36px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, .12);
}
.sheet table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.sheet td, .sheet th { border: 1px solid #000; padding: 0; text-align: center; vertical-align: middle; }
.sheet .sem-borda, .sheet .sem-borda td { border: 0; }
.sheet .cab-logos td { height: 50px; }
.sheet .cab-logos .esq { text-align: left; width: 200px; }
.sheet .cab-logos .dir { text-align: right; width: 200px; }
.sheet .titulo { font-size: 14px; font-weight: bold; line-height: 1.25; }
.sheet .logo-acoop, .sheet .logo-mae { height: 40px; display: inline-block; }
.sheet .logo-box { display: inline-block; width: 120px; height: 40px; line-height: 40px; border: 1px dashed #777; font-size: 7px; color: #555; text-align: center; }
.sheet .topo { margin-bottom: 0; }
.sheet .topo td { height: 36px; vertical-align: top; padding-top: 4px; font-size: 9px; }
.sheet .topo .valor { display: block; font-size: 10px; }
.sheet .topo.local { margin-top: -1px; }
.sheet .topo.local td { height: 26px; vertical-align: middle; text-align: left; padding-left: 4px; }
.sheet .grade { margin-top: 8px; }
.sheet .grade th { font-weight: bold; height: 18px; font-size: 9px; }
.sheet .grade td { height: 18px; font-size: 9px; }
.sheet .grade .faixa td { background: #d9d9d9; font-weight: bold; }
.sheet .grade th.faixa-cabecalho { background: #d9d9d9; }

/* Impressão: só a folha, em A4 paisagem */
@media print {
    @page { size: A4 landscape; margin: 8mm; }
    body * { visibility: hidden; }
    #sheets, #sheets * { visibility: visible; }
    #sheets { position: absolute; left: 0; top: 0; width: 100%; display: block; gap: 0; padding: 0; }
    #sheets-box { overflow: visible !important; height: auto !important; }
    .sheet { zoom: 1 !important; box-shadow: none; padding: 0; width: 100%; height: 194mm; overflow: hidden; break-after: page; page-break-after: always; }
    .sheet:last-child { break-after: auto; page-break-after: auto; }
    .sheet .grade td, .sheet .grade th, .sheet .topo td, .sheet .cab-logos td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .sheet .grade .faixa td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .sheet .grade th.faixa-cabecalho { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
