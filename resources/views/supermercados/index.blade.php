@extends('layouts.app')

@section('titulo', 'Supermercados')

@section('breadcrumb')
    @include('partials.breadcrumb', ['itens' => [
        ['nome' => 'Início', 'url' => route('inicio')],
        ['nome' => 'Supermercados'],
    ]])
@endsection

@section('conteudo')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-azul">Cadastro</p>
            <h1 class="text-2xl font-bold tracking-tight">Supermercados</h1>
            <p class="mt-1 text-sm text-grafite/60">Clique em um cartão para editar os dados.</p>
        </div>
        <a href="{{ route('supermercados.criar') }}"
           class="rounded-xl bg-azul px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90">+ Novo supermercado</a>
    </div>


    @forelse ($supermercados as $supermercado)
        @if ($loop->first)
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @endif
            <div class="group flex cursor-pointer flex-col rounded-2xl bg-white p-4 shadow-sm ring-1 ring-grafite/5 transition hover:-translate-y-0.5 hover:shadow-md"
                 data-editar="{{ route('supermercados.editar', $supermercado) }}"
                 onclick="if (!event.target.closest('a,button,form')) window.location.href = this.dataset.editar">

                <div class="flex h-28 items-center justify-center rounded-xl bg-offwhite p-4">
                    @if ($supermercado->logo_url)
                        <img src="{{ $supermercado->logo_url }}" alt="Logo {{ $supermercado->nome }}" loading="lazy" decoding="async" referrerpolicy="no-referrer"
                             class="max-h-full max-w-full object-contain">
                    @else
                        <span class="text-xs font-bold tracking-wide text-grafite/40">SEM LOGO</span>
                    @endif
                </div>

                <div class="mt-4 flex flex-1 items-center justify-center text-center">
                    <p class="text-base font-semibold">{{ $supermercado->nome }}</p>
                </div>
            </div>
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
