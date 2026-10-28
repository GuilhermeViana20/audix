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

        <a href="{{ route('planilhas.supermercados', 'temperatura') }}"
           class="group flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-grafite/5 transition hover:-translate-y-0.5 hover:shadow-md active:scale-[.99]">
            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-azul/10 text-2xl">🌡</span>
            <div>
                <p class="text-lg font-semibold">Planilha de Temperatura</p>
                <p class="mt-1 text-sm text-grafite/60">Controle de temperatura dos setores</p>
            </div>
            <span class="mt-auto text-sm font-semibold text-azul transition group-hover:translate-x-1">Abrir →</span>
        </a>
    </div>
@endsection
