@extends('layouts.app')
@section('titulo', 'Visão geral')
@section('conteudo')
<div class="audix-dashboard">
    <section class="audix-hero">
        <div class="audix-hero-content"><span class="audix-eyebrow">MAIS CLAREZA. MAIS CONTROLE.</span>
            <h1>Qualidade em cada detalhe.<br><span>Controle em um só lugar.</span></h1>
            <p>Organize as rotinas dos supermercados e prepare suas planilhas de monitoramento com praticidade.</p>
            <a href="{{ route('supermercados.index') }}" class="audix-primary">Gerenciar supermercados <span aria-hidden="true">↗</span></a>
        </div>
        <div class="audix-hero-art" aria-hidden="true"><div class="audix-hero-logo-wrap"><img src="/images/audix-logo-branco.png" alt="" class="audix-hero-logo so-tema-escuro"><img src="/images/audix-logo.png" alt="" class="audix-hero-logo so-tema-claro"></div></div>
    </section>
    <div class="audix-section-heading"><div><span class="audix-eyebrow">ROTINA ORGANIZADA</span><h2>Seus controles</h2></div><p>Escolha uma planilha para começar</p></div>
    <div class="audix-control-grid">
        @foreach ([['temperatura', '01', 'Temperatura', 'Acompanhe as temperaturas dos setores e equipamentos.', 'Monitoramento térmico'], ['higiene', '02', 'Higiene e limpeza', 'Padronize a limpeza dos ambientes e as rotinas de higienização.', 'Higiene ambiental'], ['pragas', '03', 'Controle de pragas', 'Organize as inspeções e o controle integrado de pragas.', 'Controle integrado']] as [$codigo, $numero, $nome, $descricao, $categoria])
            <a class="audix-control-card" href="{{ route('planilhas.supermercados', $codigo) }}">
                <div class="audix-card-top"><span class="audix-card-icon">@include('partials.icone', ['icone' => $codigo])</span><span class="audix-card-number">{{ $numero }}</span></div>
                <span class="audix-card-category">{{ $categoria }}</span><h3>{{ $nome }}</h3><p>{{ $descricao }}</p>
                <div class="audix-card-action">Abrir controle <span aria-hidden="true">→</span></div>
            </a>
        @endforeach
    </div>
    <section class="audix-guide"><div><span class="audix-eyebrow">DO CADASTRO À IMPRESSÃO</span><h2>Uma rotina mais simples.</h2></div><ol><li><b>01</b><span>Escolha o controle</span></li><li><b>02</b><span>Selecione o supermercado</span></li><li><b>03</b><span>Edite e imprima a planilha</span></li></ol></section>
</div>
@endsection
