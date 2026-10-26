@extends('layouts.app')

@section('titulo', $supermercado->exists ? 'Editar supermercado' : 'Novo supermercado')

@section('conteudo')
    @php($editando = $supermercado->exists)
    <h1 class="text-2xl font-bold mb-6">{{ $editando ? 'Editar supermercado' : 'Novo supermercado' }}</h1>

    <form method="POST" enctype="multipart/form-data"
          action="{{ $editando ? route('supermercados.update', $supermercado) : route('supermercados.store') }}"
          class="max-w-xl rounded-2xl bg-white p-6 shadow-sm space-y-5">
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
            <p class="block text-sm font-medium text-grafite/70">Imagem (logo)</p>
            <div class="mt-2 flex items-center gap-4">
                <div class="flex h-24 w-40 shrink-0 items-center justify-center rounded-xl bg-offwhite">
                    @if ($editando && $supermercado->logo_url)
                        <img src="{{ $supermercado->logo_url }}"
                             class="max-h-20 max-w-full object-contain" alt="Logo atual">
                    @else
                        <span class="text-xs font-bold text-grafite/50 text-center">SEM LOGO</span>
                    @endif
                </div>
                <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml"
                       class="text-sm">
            </div>
            <p class="mt-1 text-xs text-grafite/50">PNG, JPG, WEBP ou SVG, até 4 MB. Deixe em branco para manter a atual.</p>
            @error('logo')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-end gap-3 pt-2 text-sm">
            <a href="{{ route('supermercados.index') }}" class="font-medium text-grafite/70">Cancelar</a>
            <button class="rounded-xl bg-azul px-4 py-2 font-semibold text-white">{{ $editando ? 'Salvar alterações' : 'Cadastrar' }}</button>
        </div>
    </form>
@endsection
