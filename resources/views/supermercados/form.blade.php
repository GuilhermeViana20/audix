@extends('layouts.app')

@section('titulo', $supermercado->exists ? 'Editar supermercado' : 'Novo supermercado')

@section('conteudo')
    @php($editando = $supermercado->exists)
    @php($linkAtual = $editando && $supermercado->logo_caminho && \App\Models\Supermercado::ehLinkExterno($supermercado->logo_caminho) ? $supermercado->logo_caminho : '')
    @php($previewInicial = old('logo_url', $linkAtual ?: ($editando ? $supermercado->logo_url : '')))

    <h1 class="text-2xl font-bold mb-6">{{ $editando ? 'Editar supermercado' : 'Novo supermercado' }}</h1>

    <form method="POST" enctype="multipart/form-data"
          action="{{ $editando ? route('supermercados.update', $supermercado) : route('supermercados.store') }}"
          class="w-full max-w-3xl space-y-5 rounded-2xl bg-white p-6 shadow-sm">
        @csrf
        @if ($editando)
            @method('PUT')
        @endif

        <div>
            <label for="nome" class="block text-sm font-medium text-grafite/70">Nome Fantasia</label>
            <input id="nome" name="nome" value="{{ old('nome', $supermercado->nome) }}" maxlength="120" required
                   placeholder="Ex.: Aconchemax"
                   class="mt-1 w-full rounded-xl bg-offwhite px-3 py-2 text-sm">
            @error('nome')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="nome_social" class="block text-sm font-medium text-grafite/70">Nome Social</label>
            <input id="nome_social" name="nome_social" value="{{ old('nome_social', $supermercado->nome_social) }}" maxlength="120"
                   placeholder="Ex.: Aconchegante Comércio de Alimentos Ltda"
                   class="mt-1 w-full rounded-xl bg-offwhite px-3 py-2 text-sm">
            @error('nome_social')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="logo_url" class="block text-sm font-medium text-grafite/70">Imagem (logo) — link</label>
            <input id="logo_url" name="logo_url" type="url" inputmode="url"
                   value="{{ old('logo_url', $linkAtual) }}" maxlength="500"
                   placeholder="https://i.ibb.co/xxxx/logo.png"
                   class="mt-1 w-full rounded-xl bg-offwhite px-3 py-2 text-sm">
            <p class="mt-1 text-xs text-grafite/50">Use o link direto da imagem, que termina em .png, .jpg, .webp, .svg ou .gif.</p>
            @error('logo_url')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror

            <div class="mt-3 flex h-28 w-full items-center justify-center rounded-xl bg-offwhite p-3">
                <img id="logo_preview" alt="Pré-visualização da logo"
                     src="{{ $previewInicial }}"
                     class="max-h-20 max-w-full object-contain {{ $previewInicial ? '' : 'hidden' }}">
                <span id="logo_vazio" class="text-xs font-bold text-grafite/50 {{ $previewInicial ? 'hidden' : '' }}">SEM LOGO</span>
                <span id="logo_erro" class="hidden text-xs font-semibold text-red-500">Não foi possível carregar a imagem deste link</span>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2 text-sm">
            <a href="{{ route('supermercados.index') }}" class="font-medium text-grafite/70">Voltar</a>
            <button class="rounded-xl bg-azul px-4 py-2 font-semibold text-white">{{ $editando ? 'Salvar dados' : 'Cadastrar' }}</button>
        </div>
    </form>

    @if ($editando)
        <section class="mt-8 w-full">
            <h2 class="mb-1 text-xl font-bold">Seções e linhas da planilha de higiene</h2>
            <p class="mb-4 text-sm text-grafite/60">As alterações da planilha são salvas pelo botão "Salvar" de cada seção.</p>
            {{-- Editor da planilha (Vue), sem o seletor de supermercado nem a pré-visualização --}}
            <div id="app"></div>
        </section>
        @vite(['resources/js/app.js'])
        <script>
            window.AUDIX = { supermercadoId: {{ $supermercado->id }}, supermercados: [], embutido: true };
        </script>
    @endif

    <script>
        (() => {
            const campo = document.getElementById('logo_url');
            const img = document.getElementById('logo_preview');
            const vazio = document.getElementById('logo_vazio');
            const erro = document.getElementById('logo_erro');

            const mostrar = url => {
                erro.classList.add('hidden');
                if (!url) {
                    img.classList.add('hidden');
                    vazio.classList.remove('hidden');
                    return;
                }
                vazio.classList.add('hidden');
                img.classList.remove('hidden');
                img.src = url;
            };

            img.addEventListener('error', () => {
                if (img.getAttribute('src')) {
                    img.classList.add('hidden');
                    erro.classList.remove('hidden');
                }
            });
            campo.addEventListener('input', () => mostrar(campo.value.trim()));
        })();
    </script>
@endsection
