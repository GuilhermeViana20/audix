@extends('layouts.app')

@section('titulo', $supermercado->nome)

@section('conteudo')
    <a href="{{ route('supermercados.index') }}" class="text-azul text-sm">← Voltar</a>
    <h1 class="text-2xl font-bold mt-2 mb-6">{{ $supermercado->nome }}</h1>

    @forelse ($supermercado->planilhas->flatMap->secoes as $secao)
        <section class="bg-white rounded-2xl shadow-sm p-5 mb-5">
            <div class="flex items-baseline justify-between mb-3">
                <h2 class="text-lg font-semibold">{{ $secao->nome }}</h2>
                <span class="text-xs text-grafite/60">{{ $secao->titulo_primeira_coluna }} · {{ $secao->linhas->count() }} linhas</span>
            </div>

            <ul class="divide-y divide-offwhite">
                @forelse ($secao->linhas as $linha)
                    @if ($linha->tipo === 'grupo')
                        <li class="py-2 text-center text-sm font-bold uppercase bg-offwhite">{{ $linha->texto }}</li>
                    @else
                        <li class="py-2 text-sm">{{ $linha->texto ?: '—' }}</li>
                    @endif
                @empty
                    <li class="py-2 text-sm text-grafite/60">Sem linhas cadastradas.</li>
                @endforelse
            </ul>
        </section>
    @empty
        <p class="text-grafite/70">Este supermercado ainda não tem seções.</p>
    @endforelse
@endsection
