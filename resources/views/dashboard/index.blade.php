@extends('layouts.app')

@section('titulo', 'Visão geral')

@section('conteudo')
    <div class="mb-6 flex items-center justify-between gap-4">
        <h1 class="text-2xl font-bold">Visão geral</h1>
        <a href="{{ route('supermercados.criar') }}"
           class="rounded-xl bg-azul px-4 py-2 text-sm font-semibold text-white">+ Novo supermercado</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            'Supermercados cadastrados' => $stats['supermercados'],
            'Planilhas ativas' => $stats['planilhas_ativas'],
            'Seções cadastradas' => $stats['secoes'],
            'Relatórios gerados' => $stats['relatorios'],
        ] as $rotulo => $valor)
            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-grafite/50">{{ $rotulo }}</p>
                <p class="mt-2 text-3xl font-bold text-azul">{{ $valor }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-8 grid gap-6 xl:grid-cols-2">
        <section class="rounded-2xl bg-white p-5 shadow-sm">
            <h2 class="mb-3 text-lg font-semibold">Supermercados</h2>
            @forelse ($supermercados as $supermercado)
                <div class="flex items-center justify-between gap-3 border-b border-offwhite py-3 last:border-0">
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ $supermercado->nome }}</p>
                        <p class="text-xs text-grafite/50">{{ $supermercado->planilhas->count() }} planilha(s)</p>
                    </div>
                    <div class="flex gap-3 text-sm">
                        <a href="{{ route('higiene.index', $supermercado) }}" class="font-medium text-azul">Planilha</a>
                        <a href="{{ route('supermercados.editar', $supermercado) }}" class="font-medium text-azul">Editar</a>
                    </div>
                </div>
            @empty
                <p class="text-sm text-grafite/60">Nenhum supermercado cadastrado ainda.</p>
            @endforelse
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-sm">
            <h2 class="mb-3 text-lg font-semibold">Últimos relatórios</h2>
            @forelse ($recentes as $relatorio)
                <div class="flex items-center justify-between gap-3 border-b border-offwhite py-3 last:border-0 text-sm">
                    <span class="truncate">{{ $relatorio->planilha->supermercado->nome ?? '—' }} · {{ $relatorio->planilha->nome ?? '' }}</span>
                    <span class="shrink-0 text-xs text-grafite/50">{{ $relatorio->created_at->format('d/m/Y H:i') }}</span>
                </div>
            @empty
                <p class="text-sm text-grafite/60">Nenhum relatório gerado ainda.</p>
            @endforelse
        </section>
    </div>
@endsection
