@extends('layouts.app')

@section('titulo', 'Início')

@section('conteudo')
    <h1 class="text-2xl font-bold">Qual planilha você quer preencher?</h1>
    <p class="mt-1 text-sm text-grafite/60">Escolha o tipo de controle.</p>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="flex cursor-not-allowed flex-col gap-2 rounded-2xl bg-white p-6 opacity-60 shadow-sm">
            <span class="text-3xl">🐜</span>
            <span class="text-lg font-semibold">Controle de Pragas</span>
            <span class="text-xs font-semibold uppercase tracking-wide text-grafite/50">Em breve</span>
        </div>

        <a href="{{ route('planilhas.supermercados', 'higiene') }}"
           class="flex flex-col gap-2 rounded-2xl bg-white p-6 shadow-sm transition hover:shadow-md active:scale-[.99]">
            <span class="text-3xl">💧</span>
            <span class="text-lg font-semibold">Planilha de Higiene</span>
            <span class="text-sm text-grafite/60">Higiene e limpeza ambiental</span>
        </a>

        <div class="flex cursor-not-allowed flex-col gap-2 rounded-2xl bg-white p-6 opacity-60 shadow-sm">
            <span class="text-3xl">🌡</span>
            <span class="text-lg font-semibold">Planilha de Temperatura</span>
            <span class="text-xs font-semibold uppercase tracking-wide text-grafite/50">Em breve</span>
        </div>
    </div>
@endsection
