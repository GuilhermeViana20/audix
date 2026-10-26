<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';

const TOKEN = document.querySelector('meta[name="csrf-token"]').content;
const JSON_HEADERS = { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': TOKEN };

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
function imprimir() { window.print(); }

// Assinatura do que é salvo no banco para uma seção (título + linhas).
function assinatura(s, tit) {
    return JSON.stringify({ t: tit, l: s.linhas.map(l => [l.tipo, l.texto]) });
}
const estaSujo = computed(() => !!atual.value && assinatura(atual.value, titulo.value) !== atual.value._salvo);

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
    if (!id) return;
    await salvarAgora();
    carregando.value = true;
    try {
        supermercadoId.value = id;
        atual.value = null;
        titulo.value = '';
        selecionadas.value = {};
        await recarregarSecoes();
        await carregarLogo();
        history.replaceState(null, '', `/higiene/${id}`);
    } catch (e) { msg(e.message); }
    finally { carregando.value = false; }
}

async function abrir(secao) {
    await salvarAgora();
    atual.value = secao;
    titulo.value = secao.titulo_primeira_coluna;
}

// ---------- Salvamento automático ----------
let timer = null;
let fila = Promise.resolve();

function agendarSalvamento() {
    if (!atual.value || !estaSujo.value) return;
    status.value = 'Alterações pendentes';
    clearTimeout(timer);
    timer = setTimeout(salvarAgora, 700);
}

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
        status.value = estaSujo.value ? 'Alterações pendentes' : 'Tudo salvo';
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
    await salvarAgora();
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

async function adicionarSecao() {
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

// Autosave: qualquer mudança nas linhas ou no título das seções agenda a gravação
watch([titulo, () => atual.value?.linhas], agendarSalvamento, { deep: true });
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

    <div class="flex min-h-screen">
        <div class="flex-1 min-w-0 grid xl:grid-cols-[2fr_3fr] h-screen">
            <!-- Coluna da esquerda: controles (rola sozinha) -->
            <section class="overflow-y-auto p-5 md:p-8 space-y-5">
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-grafite/50">Supermercado</p>
                    <select class="mt-2 w-full rounded-xl bg-offwhite px-3 py-2 text-sm font-medium"
                            :value="supermercadoId ?? ''"
                            @change="carregarSupermercado(Number($event.target.value))">
                        <option value="" disabled>Escolha o supermercado</option>
                        <option v-for="m in supermercados" :key="m.id" :value="m.id">{{ m.nome }}</option>
                    </select>
                </div>

                <template v-if="supermercadoId">
                    <div class="rounded-2xl bg-white p-4 shadow-sm">
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
                        </div>
                        <p v-if="planilha" class="mt-2 text-xs text-grafite/60">Tipo de planilha: <b class="text-grafite">{{ planilha.nome }}</b></p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2 2xl:grid-cols-3">
                            <div v-for="s in ordenadas" :key="s.id"
                                 class="cursor-pointer rounded-xl border p-3 transition hover:border-azul/40"
                                 :class="atual?.id === s.id ? 'border-azul bg-azul/5' : 'border-transparent bg-offwhite'"
                                 @click="abrir(s)">
                                <label class="flex items-center gap-2 font-medium" @click.stop>
                                    <input type="checkbox" v-model="selecionadas[s.id]" class="accent-azul h-4 w-4">
                                    {{ s.nome }}
                                </label>
                                <div class="mt-2 flex flex-wrap items-center gap-3 text-sm">
                                    <span class="text-xs text-grafite/50">{{ s.linhas.length }} linhas</span>
                                    <button class="text-azul" @click.stop="renomear(s)">Renomear</button>
                                    <button class="ml-auto text-red-500" @click.stop="excluirSecao(s)">×</button>
                                </div>
                            </div>
                        </div>
                        <form class="mt-3 flex gap-2" @submit.prevent="adicionarSecao">
                            <input v-model="novaSecao" placeholder="Nova seção (ex.: PADARIA)" class="flex-1 rounded-xl bg-offwhite px-3 py-2 text-sm">
                            <button class="rounded-xl bg-azul px-4 text-sm font-semibold text-white">Adicionar</button>
                        </form>
                    </div>

                    <div v-if="atual" class="rounded-2xl bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-lg font-bold">Linhas de {{ atual.nome }}</h2>
                            <span class="text-xs font-semibold"
                                  :class="status === 'Erro ao salvar' ? 'text-red-500' : 'text-grafite/50'">{{ status }}</span>
                        </div>
                        <label class="mt-3 block text-sm text-grafite/70">Título da primeira coluna</label>
                        <input v-model="titulo" class="mt-1 w-full rounded-xl bg-offwhite px-3 py-2 text-sm">

                        <div class="mt-4 space-y-2">
                            <div v-for="(l, i) in atual.linhas" :key="l.id ?? i" class="flex items-center gap-2">
                                <span class="w-12 text-xs text-grafite/50">{{ l.tipo === 'grupo' ? 'Faixa' : 'Linha' }}</span>
                                <input v-model="l.texto" class="flex-1 rounded-xl px-3 py-2 text-sm"
                                       :class="l.tipo === 'grupo' ? 'bg-grafite/10 font-bold uppercase' : 'bg-offwhite'">
                                <button class="px-1 text-azul disabled:opacity-30" :disabled="i === 0" @click="moverLinha(i, -1)">↑</button>
                                <button class="px-1 text-azul disabled:opacity-30" :disabled="i === atual.linhas.length - 1" @click="moverLinha(i, 1)">↓</button>
                                <button class="px-1 text-red-500" @click="removerLinha(i)">×</button>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2 text-sm">
                            <button class="rounded-xl bg-offwhite px-3 py-2 font-medium text-azul" @click="adicionarLinha">+ Linha</button>
                            <button class="rounded-xl bg-offwhite px-3 py-2 font-medium text-azul" @click="adicionarGrupo">+ Faixa</button>
                            <button class="ml-auto rounded-xl bg-offwhite px-3 py-2 font-medium" @click="copiarParaTodas">Copiar para todas</button>
                        </div>
                    </div>
                </template>

                <p v-else class="rounded-2xl bg-white p-6 text-grafite/60 shadow-sm">Escolha um supermercado para carregar a planilha.</p>
            </section>

            <!-- Coluna da direita: pré-visualização em HTML (mesmo layout da folha A4); imprime pelo navegador -->
            <aside class="no-print hidden xl:flex flex-col h-screen overflow-hidden p-5 md:p-8 bg-offwhite border-l border-grafite/5">
                <div class="flex items-center justify-between rounded-2xl bg-white px-5 py-4 shadow-sm">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-azul">Pré-visualização</p>
                        <p class="text-lg font-bold">{{ planilha?.nome || 'Planilha' }}</p>
                        <p class="text-xs text-grafite/60">{{ folhas.length }} página(s) · {{ idsSelecionados.length }} seção(ões) · A4 paisagem</p>
                    </div>
                    <button class="rounded-xl bg-azul px-3 py-2 text-sm font-semibold text-white disabled:opacity-40"
                            :disabled="!folhas.length" @click="imprimir">Imprimir / PDF</button>
                </div>
                <div id="sheets-box" class="mt-4 flex-1 overflow-y-auto">
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
                                        <th>{{ f.secao.titulo_primeira_coluna }}</th>
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
    padding: 22px 0;
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
.sheet .topo.local td { height: 26px; vertical-align: middle; text-align: left; padding-left: 4px; }
.sheet .grade { margin-top: 8px; }
.sheet .grade th { font-weight: bold; height: 18px; font-size: 9px; }
.sheet .grade td { height: 18px; font-size: 9px; }
.sheet .grade .faixa td { background: #d9d9d9; font-weight: bold; }

/* Impressão: só a folha, em A4 paisagem */
@media print {
    @page { size: A4 landscape; margin: 8mm; }
    body * { visibility: hidden; }
    #sheets, #sheets * { visibility: visible; }
    #sheets { position: absolute; left: 0; top: 0; width: 100%; display: block; gap: 0; padding: 0; }
    #sheets-box { overflow: visible !important; height: auto !important; }
    .sheet { zoom: 1 !important; box-shadow: none; padding: 0; width: 100%; break-after: page; page-break-after: always; }
    .sheet:last-child { break-after: auto; page-break-after: auto; }
    .sheet .grade td, .sheet .grade th, .sheet .topo td, .sheet .cab-logos td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .sheet .grade .faixa td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
