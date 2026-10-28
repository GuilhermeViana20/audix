@extends('layouts.app')

@section('titulo', 'Escolha o supermercado')

@section('conteudo')
    <div class="flex items-center justify-between gap-3 text-sm">
        <a href="{{ route('inicio') }}" class="font-medium text-azul">← Tipos</a>
        <a href="{{ route('supermercados.index') }}" class="font-medium text-grafite/70">Gerenciar supermercados</a>
    </div>

    <h1 class="mt-4 text-2xl font-bold">{{ $nomeTipo }}</h1>
    <p class="mt-1 text-sm text-grafite/60">Escolha o supermercado.</p>

    @forelse ($supermercados as $supermercado)
        @if ($loop->first)
            <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
        @endif
            <a href="{{ route($tipo === 'pragas' ? 'pragas.index' : 'higiene.index', $supermercado) }}"
               class="flex flex-col items-center gap-3 rounded-2xl bg-white p-4 text-center shadow-sm transition hover:shadow-md active:scale-[.98]">
                <div class="flex h-20 w-full items-center justify-center rounded-xl bg-offwhite p-2">
                    @if ($supermercado->logo_url)
                        <img src="{{ $supermercado->logo_url }}" alt="Logo {{ $supermercado->nome }}" class="max-h-16 max-w-full object-contain">
                    @else
                        <span class="text-xs font-bold text-grafite/40">SEM LOGO</span>
                    @endif
                </div>
                <span class="text-sm font-semibold leading-tight">{{ $supermercado->nome }}</span>
            </a>
        @if ($loop->last)
            </div>
        @endif
    @empty
        <div class="mt-6 rounded-2xl bg-white p-6 shadow-sm">
            <p class="text-grafite/70">Nenhum supermercado cadastrado ainda.</p>
            <a href="{{ route('supermercados.criar') }}" class="mt-4 inline-block rounded-xl bg-azul px-4 py-2 text-sm font-semibold text-white">+ Cadastrar supermercado</a>
        </div>
    @endforelse
@endsection
