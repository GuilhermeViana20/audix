@extends('layouts.app')

@section('titulo', 'Planilhas')

@section('conteudo')
    <div class="mb-8">
        <h1 class="text-2xl font-bold tracking-tight">Qual planilha você quer preencher?</h1>
        <p class="mt-1 text-sm text-grafite/60">Escolha o tipo de controle para continuar.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <a href="{{ route('planilhas.supermercados', 'pragas') }}"
           class="group flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-grafite/5 transition hover:-translate-y-0.5 hover:shadow-md active:scale-[.99]">
            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-azul/10 text-2xl">🐜</span>
            <div>
                <p class="text-lg font-semibold">Controle de Pragas</p>
                <p class="mt-1 text-sm text-grafite/60">CIP – controle integrado de pragas</p>
            </div>
            <span class="mt-auto text-sm font-semibold text-azul transition group-hover:translate-x-1">Abrir →</span>
        </a>

        <a href="{{ route('planilhas.supermercados', 'higiene') }}"
           class="group flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-grafite/5 transition hover:-translate-y-0.5 hover:shadow-md active:scale-[.99]">
            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-azul/10 text-2xl">💧</span>
            <div>
                <p class="text-lg font-semibold">Planilha de Higiene</p>
                <p class="mt-1 text-sm text-grafite/60">Higiene e limpeza ambiental</p>
            </div>
            <span class="mt-auto text-sm font-semibold text-azul transition group-hover:translate-x-1">Abrir →</span>
        </a>

        <div class="flex cursor-not-allowed flex-col gap-4 rounded-2xl bg-white/60 p-6 ring-1 ring-grafite/5">
            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-grafite/5 text-2xl opacity-60">🌡</span>
            <div>
                <p class="text-lg font-semibold text-grafite/60">Planilha de Temperatura</p>
                <p class="mt-1 text-sm text-grafite/50">Controle de temperatura</p>
            </div>
            <span class="mt-auto inline-flex w-fit rounded-full bg-grafite/5 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-grafite/50">Em breve</span>
        </div>
    </div>
@endsection
