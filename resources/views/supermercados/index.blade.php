@extends('layouts.app')

@section('titulo', 'Supermercados')

@section('conteudo')
    <div class="mb-6 flex items-center justify-between gap-4">
        <h1 class="text-2xl font-bold">Supermercados</h1>
        <a href="{{ route('supermercados.criar') }}"
           class="rounded-xl bg-azul px-4 py-2 text-sm font-semibold text-white">+ Novo supermercado</a>
    </div>

    @if (session('ok'))
        <p class="mb-4 rounded-xl bg-white px-4 py-2 text-sm shadow-sm">{{ session('ok') }}</p>
    @endif

    @forelse ($supermercados as $supermercado)
        <div class="mb-3 flex items-center gap-3 rounded-2xl bg-white p-5 shadow-sm">
            <a href="{{ route('supermercados.show', $supermercado) }}" class="flex-1 min-w-0">
                <span class="text-lg font-semibold">{{ $supermercado->nome }}</span>
                <span class="ml-2 text-sm font-medium text-azul">{{ $supermercado->secoes_count }} seções</span>
            </a>
            <a href="{{ route('higiene.index', $supermercado) }}" class="text-sm font-medium text-azul">Planilha</a>
            <a href="{{ route('supermercados.editar', $supermercado) }}" class="text-sm font-medium text-azul">Editar</a>
            <form method="POST" action="{{ route('supermercados.destroy', $supermercado) }}"
                  onsubmit="return confirm('Remover {{ addslashes($supermercado->nome) }} e todas as suas seções e linhas?')">
                @csrf
                @method('DELETE')
                <button class="text-sm font-medium text-red-500">Remover</button>
            </form>
        </div>
    @empty
        <p class="text-grafite/70">Nenhum supermercado cadastrado. Clique em "+ Novo supermercado" para começar.</p>
    @endforelse
@endsection
