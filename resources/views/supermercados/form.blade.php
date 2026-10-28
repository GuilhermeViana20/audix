@extends('layouts.app')

@section('titulo', $supermercado->exists ? 'Editar supermercado' : 'Novo supermercado')

@section('breadcrumb')
    @include('partials.breadcrumb', ['itens' => [
        ['nome' => 'Início', 'url' => route('inicio')],
        ['nome' => 'Supermercados', 'url' => route('supermercados.index')],
        ['nome' => $supermercado->exists ? $supermercado->nome : 'Novo supermercado'],
    ]])
@endsection

@section('conteudo')
    @php($editando = $supermercado->exists)
    @php($linkAtual = $editando ? (string) $supermercado->logo_caminho : '')
    @php($previewInicial = old('logo_url', $linkAtual ?: ($editando ? $supermercado->logo_url : '')))

    <h1 class="text-2xl font-bold mb-6">{{ $editando ? 'Editar supermercado' : 'Novo supermercado' }}</h1>

    <form method="POST"
          action="{{ $editando ? route('supermercados.update', $supermercado) : route('supermercados.store') }}"
          class="w-full rounded-2xl bg-white p-6 shadow-sm md:p-8">
        @csrf
        @if ($editando)
            @method('PUT')
        @endif

        <div class="grid items-start gap-8 lg:grid-cols-2">
            {{-- Logo à esquerda --}}
            <div>
                <p class="mb-1 text-sm font-medium text-grafite/70">Logo</p>
                <div class="flex h-40 w-full items-center justify-center rounded-xl bg-offwhite p-6">
                    <img id="logo_preview" alt="Pré-visualização da logo"
                         src="{{ $previewInicial }}"
                         class="max-h-20 max-w-full object-contain {{ $previewInicial ? '' : 'hidden' }}">
                    <span id="logo_vazio" class="text-xs font-bold text-grafite/50 {{ $previewInicial ? 'hidden' : '' }}">SEM LOGO</span>
                    <span id="logo_erro" class="hidden text-xs font-semibold text-red-500">Não foi possível carregar a imagem deste link</span>
                </div>
            </div>

            {{-- Link da logo e nomes à direita --}}
            <div class="space-y-5">
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
                </div>

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
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2 text-sm">
            <a href="{{ route('supermercados.index') }}" class="font-medium text-grafite/70">Voltar</a>
            <button class="rounded-xl bg-azul px-4 py-2 font-semibold text-white">{{ $editando ? 'Salvar dados' : 'Cadastrar' }}</button>
        </div>
    </form>


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
