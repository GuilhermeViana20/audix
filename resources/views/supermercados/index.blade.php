@extends('layouts.app')

@section('titulo', 'Supermercados')

@section('conteudo')
    <h1 class="text-2xl font-bold mb-6">Supermercados</h1>

    @forelse ($supermercados as $supermercado)
        <a href="{{ route('supermercados.show', $supermercado) }}"
           class="block bg-white rounded-2xl shadow-sm p-5 mb-3 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-lg font-semibold">{{ $supermercado->nome }}</span>
                <span class="text-sm text-azul font-medium">{{ $supermercado->secoes_count }} seções</span>
            </div>
        </a>
    @empty
        <p class="text-grafite/70">Nenhum supermercado cadastrado. Rode o <code>db:seed</code>.</p>
    @endforelse
@endsection
