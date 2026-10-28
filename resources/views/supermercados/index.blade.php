@extends('layouts.app')

@section('titulo', 'Supermercados')

@section('conteudo')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-azul">Cadastro</p>
            <h1 class="text-2xl font-bold tracking-tight">Supermercados</h1>
            <p class="mt-1 text-sm text-grafite/60">Clique em um supermercado para editar.</p>
        </div>
        <a href="{{ route('supermercados.criar') }}"
           class="rounded-xl bg-azul px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90">+ Novo supermercado</a>
    </div>

    @if (session('ok'))
        <p class="mb-4 rounded-xl bg-white px-4 py-2 text-sm shadow-sm ring-1 ring-grafite/5">{{ session('ok') }}</p>
    @endif

    @forelse ($supermercados as $supermercado)
        <div class="mb-3 flex cursor-pointer flex-wrap items-center gap-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-grafite/5 transition hover:shadow-md sm:flex-nowrap sm:p-5"
             data-editar="{{ route('supermercados.editar', $supermercado) }}"
             onclick="if (!event.target.closest('a,button,form')) window.location.href = this.dataset.editar">
            <div class="flex h-14 w-24 shrink-0 items-center justify-center rounded-xl bg-offwhite p-2">
                @if ($supermercado->logo_url)
                    <img src="{{ $supermercado->logo_url }}" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer"
                         class="max-h-full max-w-full object-contain">
                @else
                    <span class="text-[10px] font-bold tracking-wide text-grafite/40">SEM LOGO</span>
                @endif
            </div>

            <div class="min-w-0 flex-1">
                <p class="truncate text-base font-semibold">{{ $supermercado->nome }}</p>
                @if ($supermercado->nome_social)
                    <p class="truncate text-sm text-grafite/60">{{ $supermercado->nome_social }}</p>
                @endif
                <span class="mt-1 inline-flex rounded-full bg-azul/10 px-2.5 py-0.5 text-xs font-semibold text-azul">{{ $supermercado->secoes_count }} seções</span>
            </div>

            <div class="flex w-full items-center justify-end gap-2 sm:w-auto">
                <a href="{{ route('higiene.index', $supermercado) }}"
                   class="rounded-lg px-3 py-2 text-sm font-semibold text-azul transition hover:bg-azul/10">Planilha</a>
                <form method="POST" action="{{ route('supermercados.destroy', $supermercado) }}"
                      onsubmit="return confirm('Remover {{ addslashes($supermercado->nome) }} e todas as suas seções e linhas?')">
                    @csrf
                    @method('DELETE')
                    <button class="rounded-lg px-3 py-2 text-sm font-semibold text-red-500 transition hover:bg-red-50">Remover</button>
                </form>
            </div>
        </div>
    @empty
        <div class="rounded-2xl bg-white p-8 text-center shadow-sm ring-1 ring-grafite/5">
            <p class="text-grafite/70">Nenhum supermercado cadastrado. Clique em "+ Novo supermercado" para começar.</p>
        </div>
    @endforelse
@endsection
