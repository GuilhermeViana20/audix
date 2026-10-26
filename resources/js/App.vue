<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const SUPER_ID = window.AUDIX.supermercadoId;
const TOKEN = document.querySelector('meta[name="csrf-token"]').content;
const JSON_HEADERS = { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': TOKEN };

const secoes = ref([]);
const selecionadas = ref({});
const atual = ref(null);
const linhas = ref([]);
const titulo = ref('');
const novaSecao = ref('');
const aviso = ref('');
const salvando = ref(false);
const logoUrl = ref(null);
const versaoPreview = ref(0);

const ordenadas = computed(() => [...secoes.value].sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR')));
const idsSelecionados = computed(() => ordenadas.value.filter(s => selecionadas.value[s.id]).map(s => s.id));
const urlPreview = computed(() => idsSelecionados.value.length
    ? `/higiene/${SUPER_ID}/pdf?secoes=${idsSelecionados.value.join(',')}&v=${versaoPreview.value}`
    : null);

function msg(texto) { aviso.value = texto; setTimeout(() => (aviso.value = ''), 3500); }
function atualizarPreview() { versaoPreview.value++; }

async function api(url, opts = {}) {
    const r = await fetch(url, { headers: JSON_HEADERS, ...opts });
    const dados = await r.json().catch(() => ({}));
    if (!r.ok) throw new Error(dados.message || Object.values(dados.errors || {})[0]?.[0] || 'Erro ao salvar');
    return dados;
}

async function carregarSecoes() {
    secoes.value = await api(`/api/supermercados/${SUPER_ID}/secoes`);
    secoes.value.forEach(s => { if (selecionadas.value[s.id] === undefined) selecionadas.value[s.id] = true; });
    atualizarPreview();
}

async function abrir(secao) {
    atual.value = secao;
    titulo.value = secao.titulo_primeira_coluna;
    linhas.value = await api(`/api/secoes/${secao.id}/linhas`);
}

async function salvarLinhas() {
    if (!atual.value) return;
    salvando.value = true;
    try {
        linhas.value = await api(`/api/secoes/${atual.value.id}/linhas`, {
            method: 'PUT',
            body: JSON.stringify({ linhas: linhas.value.map(l => ({ tipo: l.tipo, texto: l.texto })) }),
        });
        await api(`/api/secoes/${atual.value.id}`, {
            method: 'PUT',
            body: JSON.stringify({ nome: atual.value.nome, titulo_primeira_coluna: titulo.value }),
        });
        atual.value.titulo_primeira_coluna = titulo.value;
        msg('Linhas salvas');
        atualizarPreview();
    } catch (e) { msg(e.message); }
    finally { salvando.value = false; }
}

async function copiarParaTodas() {
    if (!atual.value) return;
    await salvarLinhas();
    try {
        await api(`/api/secoes/${atual.value.id}/copiar-para-todas`, { method: 'POST' });
        msg('Copiado para todas as seções');
        atualizarPreview();
    } catch (e) { msg(e.message); }
}

function moverLinha(i, d) {
    const j = i + d;
    if (j < 0 || j >= linhas.value.length) return;
    [linhas.value[i], linhas.value[j]] = [linhas.value[j], linhas.value[i]];
}
function adicionarLinha() { linhas.value.push({ tipo: 'linha', texto: '' }); }
function adicionarGrupo() { linhas.value.push({ tipo: 'grupo', texto: 'NOVA FAIXA' }); }
function removerLinha(i) { linhas.value.splice(i, 1); }

async function adicionarSecao() {
    const nome = novaSecao.value.trim();
    if (!nome) return;
    try {
        const nova = await api(`/api/supermercados/${SUPER_ID}/secoes`, { method: 'POST', body: JSON.stringify({ nome }) });
        novaSecao.value = '';
        await carregarSecoes();
        selecionadas.value[nova.id] = true;
        await abrir(nova);
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
        await carregarSecoes();
        if (atual.value?.id === secao.id) atual.value = secoes.value.find(s => s.id === secao.id);
    } catch (e) { msg(e.message); }
}

async function excluirSecao(secao) {
    if (!window.confirm(`Remover a seção ${secao.nome} e suas linhas?`)) return;
    try {
        await api(`/api/secoes/${secao.id}`, { method: 'DELETE' });
        delete selecionadas.value[secao.id];
        if (atual.value?.id === secao.id) { atual.value = null; linhas.value = []; }
        await carregarSecoes();
    } catch (e) { msg(e.message); }
}

async function enviarLogo(evento) {
    const arquivo = evento.target.files[0];
    if (!arquivo) return;
    const corpo = new FormData();
    corpo.append('logo', arquivo);
    try {
        const r = await fetch(`/api/supermercados/${SUPER_ID}/logo`, {
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
        await api(`/api/supermercados/${SUPER_ID}/logo`, { method: 'DELETE' });
        logoUrl.value = null;
        msg('Logo padrão restaurada');
        atualizarPreview();
    } catch (e) { msg(e.message); }
}

// Atualiza a pré-visualização quando muda a seleção (sem sobrecarregar a geração)
watch(selecionadas, atualizarPreview, { deep: true });

onMounted(async () => {
    await carregarSecoes();
    const r = await fetch(`/api/supermercados/${SUPER_ID}/logo`, { headers: { Accept: 'application/json' } });
    logoUrl.value = (await r.json()).logo_url;
});
</script>

<template>
    <div class="flex min-h-screen">
        <aside class="hidden md:flex w-60 shrink-0 flex-col bg-grafite text-white sticky top-0 h-screen px-4 py-7">
            <a href="/" class="px-2 text-3xl font-extrabold tracking-tight">audi<span class="text-azul">x</span></a>
            <p class="px-2 pt-1 pb-6 text-xs text-white/55">Inteligência em cada controle.</p>
            <nav class="grid gap-1">
                <a href="/" class="rounded-xl px-3 py-2.5 text-sm font-medium text-white/80 hover:bg-white/5">Visão geral</a>
                <span class="rounded-xl px-3 py-2.5 text-sm text-white/35">Temperatura</span>
                <span class="rounded-xl bg-azul px-3 py-2.5 text-sm font-medium text-white shadow-lg shadow-azul/30">Higiene</span>
                <span class="rounded-xl px-3 py-2.5 text-sm text-white/35">CIP</span>
            </nav>
        </aside>

        <div class="flex-1 min-w-0 grid xl:grid-cols-[2fr_3fr] h-screen">
            <!-- Coluna da esquerda: controles (rola sozinha) -->
            <section class="overflow-y-auto p-5 md:p-8 space-y-5">
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

                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-grafite/50">Seções no arquivo</p>
                    <div class="mt-3 flex gap-2 text-sm">
                        <button class="text-azul font-medium" @click="ordenadas.forEach(s => selecionadas[s.id] = true)">Marcar todas</button>
                        <button class="text-azul font-medium" @click="ordenadas.forEach(s => selecionadas[s.id] = false)">Nenhuma</button>
                    </div>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2 2xl:grid-cols-3">
                        <div v-for="s in ordenadas" :key="s.id"
                             class="rounded-xl border p-3"
                             :class="atual?.id === s.id ? 'border-azul' : 'border-transparent bg-offwhite'">
                            <label class="flex items-center gap-2 font-medium">
                                <input type="checkbox" v-model="selecionadas[s.id]" class="accent-azul h-4 w-4">
                                {{ s.nome }}
                            </label>
                            <div class="mt-2 flex flex-wrap items-center gap-3 text-sm">
                                <button class="text-azul font-semibold" @click="abrir(s)">Linhas</button>
                                <button class="text-azul" @click="renomear(s)">Renomear</button>
                                <button class="ml-auto text-red-500" @click="excluirSecao(s)">×</button>
                            </div>
                        </div>
                    </div>
                    <form class="mt-3 flex gap-2" @submit.prevent="adicionarSecao">
                        <input v-model="novaSecao" placeholder="Nova seção (ex.: PADARIA)" class="flex-1 rounded-xl bg-offwhite px-3 py-2 text-sm">
                        <button class="rounded-xl bg-azul px-4 text-sm font-semibold text-white">Adicionar</button>
                    </form>
                </div>

                <div v-if="atual" class="rounded-2xl bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-bold">Linhas de {{ atual.nome }}</h2>
                    <label class="mt-3 block text-sm text-grafite/70">Título da primeira coluna</label>
                    <input v-model="titulo" class="mt-1 w-full rounded-xl bg-offwhite px-3 py-2 text-sm">

                    <div class="mt-4 space-y-2">
                        <div v-for="(l, i) in linhas" :key="i" class="flex items-center gap-2">
                            <span class="w-12 text-xs text-grafite/50">{{ l.tipo === 'grupo' ? 'Faixa' : 'Linha' }}</span>
                            <input v-model="l.texto" class="flex-1 rounded-xl px-3 py-2 text-sm"
                                   :class="l.tipo === 'grupo' ? 'bg-grafite/10 font-bold uppercase' : 'bg-offwhite'">
                            <button class="px-1 text-azul disabled:opacity-30" :disabled="i === 0" @click="moverLinha(i, -1)">↑</button>
                            <button class="px-1 text-azul disabled:opacity-30" :disabled="i === linhas.length - 1" @click="moverLinha(i, 1)">↓</button>
                            <button class="px-1 text-red-500" @click="removerLinha(i)">×</button>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2 text-sm">
                        <button class="rounded-xl bg-offwhite px-3 py-2 font-medium text-azul" @click="adicionarLinha">+ Linha</button>
                        <button class="rounded-xl bg-offwhite px-3 py-2 font-medium text-azul" @click="adicionarGrupo">+ Faixa</button>
                        <button class="ml-auto rounded-xl bg-offwhite px-3 py-2 font-medium" @click="copiarParaTodas">Copiar para todas</button>
                        <button class="rounded-xl bg-azul px-4 py-2 font-semibold text-white disabled:opacity-50" :disabled="salvando" @click="salvarLinhas">
                            {{ salvando ? 'Salvando…' : 'Salvar' }}
                        </button>
                    </div>
                </div>
            </section>

            <!-- Coluna da direita: pré-visualização do PDF real (rola sozinha) -->
            <aside class="hidden xl:flex flex-col h-screen overflow-hidden p-5 md:p-8 bg-offwhite border-l border-grafite/5">
                <div class="flex items-center justify-between rounded-2xl bg-white px-5 py-4 shadow-sm">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-azul">Pré-visualização do PDF</p>
                        <p class="text-lg font-bold">Higiene e limpeza ambiental</p>
                        <p class="text-xs text-grafite/60">{{ idsSelecionados.length }} seção(ões) · A4 paisagem</p>
                    </div>
                    <div class="flex gap-2">
                        <button class="rounded-xl bg-offwhite px-3 py-2 text-sm font-medium" @click="atualizarPreview">Atualizar</button>
                        <a v-if="urlPreview" :href="urlPreview" target="_blank" class="rounded-xl bg-azul px-3 py-2 text-sm font-semibold text-white">Abrir PDF</a>
                    </div>
                </div>
                <div class="mt-4 flex-1 overflow-hidden rounded-2xl bg-white shadow-sm">
                    <iframe v-if="urlPreview" :src="urlPreview" class="h-full w-full" title="Pré-visualização"></iframe>
                    <p v-else class="p-6 text-grafite/60">Selecione pelo menos uma seção para ver a pré-visualização.</p>
                </div>
            </aside>
        </div>
    </div>
</template>
