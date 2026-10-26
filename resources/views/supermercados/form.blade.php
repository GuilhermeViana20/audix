@extends('layouts.app')

@section('titulo', $supermercado->exists ? 'Editar supermercado' : 'Novo supermercado')

@section('conteudo')
    @php($editando = $supermercado->exists)
    <h1 class="text-2xl font-bold mb-6">{{ $editando ? 'Editar supermercado' : 'Novo supermercado' }}</h1>

    <form method="POST"
          action="{{ $editando ? route('supermercados.update', $supermercado) : route('supermercados.store') }}"
          class="max-w-xl rounded-2xl bg-white p-6 shadow-sm space-y-4">
        @csrf
        @if ($editando)
            @method('PUT')
        @endif

        <div>
            <label for="nome" class="block text-sm font-medium text-grafite/70">Nome do supermercado</label>
            <input id="nome" name="nome" value="{{ old('nome', $supermercado->nome) }}" maxlength="120" required
                   placeholder="Ex.: Aconchemax"
                   class="mt-1 w-full rounded-xl bg-offwhite px-3 py-2 text-sm">
            @error('nome')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
            @if ($editando)
                <p class="mt-1 text-xs text-grafite/50">Endereço da planilha: {{ $supermercado->slug }}</p>
            @endif
        </div>

        <p class="text-xs text-grafite/50">A logo e as seções são editadas na tela da planilha de higiene.</p>

        <div class="flex items-center justify-end gap-3 pt-2 text-sm">
            <a href="{{ route('supermercados.index') }}" class="font-medium text-grafite/70">Cancelar</a>
            <button class="rounded-xl bg-azul px-4 py-2 font-semibold text-white">{{ $editando ? 'Salvar alterações' : 'Cadastrar' }}</button>
        </div>
    </form>
@endsection
