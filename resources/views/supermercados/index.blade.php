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
        <div class="mb-3 flex cursor-pointer items-center gap-3 rounded-2xl bg-white p-5 shadow-sm transition hover:shadow-md"
             data-editar="{{ route('supermercados.editar', $supermercado) }}"
             onclick="if (!event.target.closest('a,button,form')) window.location.href = this.dataset.editar">
            <div class="flex-1 min-w-0">
                <span class="text-lg font-semibold">{{ $supermercado->nome }}</span>
                @if ($supermercado->nome_social)
                    <span class="block text-sm text-grafite/60">{{ $supermercado->nome_social }}</span>
                @endif
                <span class="text-sm font-medium text-azul">{{ $supermercado->secoes_count }} seções</span>
            </div>
            <a href="{{ route('higiene.index', $supermercado) }}" class="text-sm font-medium text-azul">Planilha</a>
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
