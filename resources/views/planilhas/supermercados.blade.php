@extends('layouts.app')

@section('titulo', 'Escolha o supermercado')

@section('conteudo')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('inicio') }}" class="text-sm font-medium text-azul">← Tipos de planilha</a>
        <a href="{{ route('supermercados.index') }}" class="text-sm font-medium text-grafite/60 transition hover:text-azul">Gerenciar supermercados</a>
    </div>

    <div class="mt-4 mb-6">
        <p class="text-xs font-semibold uppercase tracking-wide text-azul">Planilha</p>
        <h1 class="text-2xl font-bold tracking-tight">{{ $nomeTipo }}</h1>
        <p class="mt-1 text-sm text-grafite/60">Escolha o supermercado para preencher.</p>
    </div>

    @forelse ($supermercados as $supermercado)
        @if ($loop->first)
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        @endif
            <a href="{{ route($tipo === 'pragas' ? 'pragas.index' : 'higiene.index', $supermercado) }}"
               class="group flex flex-col rounded-2xl bg-white p-4 shadow-sm ring-1 ring-grafite/5 transition hover:-translate-y-0.5 hover:shadow-md active:scale-[.98]">
                <div class="flex h-24 items-center justify-center rounded-xl bg-offwhite p-3">
                    @if ($supermercado->logo_url)
                        <img src="{{ $supermercado->logo_url }}" alt="Logo {{ $supermercado->nome }}" loading="lazy" decoding="async" referrerpolicy="no-referrer"
                             class="max-h-full max-w-full object-contain">
                    @else
                        <span class="text-xs font-bold tracking-wide text-grafite/40">SEM LOGO</span>
                    @endif
                </div>
                <div class="mt-3 flex items-center justify-between gap-2">
                    <span class="text-sm font-semibold leading-tight">{{ $supermercado->nome }}</span>
                    <span class="text-azul transition group-hover:translate-x-1">→</span>
                </div>
            </a>
        @if ($loop->last)
            </div>
        @endif
    @empty
        <div class="rounded-2xl bg-white p-8 text-center shadow-sm ring-1 ring-grafite/5">
            <p class="text-grafite/70">Nenhum supermercado cadastrado ainda.</p>
            <a href="{{ route('supermercados.criar') }}" class="mt-4 inline-block rounded-xl bg-azul px-4 py-2 text-sm font-semibold text-white">+ Cadastrar supermercado</a>
        </div>
    @endforelse
@endsection
