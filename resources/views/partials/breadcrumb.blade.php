@php($destaque = ($variante ?? '') === 'destaque')
<nav aria-label="Você está em" class="{{ $destaque ? 'inline-flex max-w-full items-center rounded-xl bg-[linear-gradient(135deg,#4063ff,#2f4fe0)] px-4 py-2 text-[13px] text-white' : 'mb-6 text-sm' }}">
    <ol class="flex min-w-0 flex-wrap items-center gap-1.5 {{ $destaque ? 'text-white/70' : 'text-grafite/60' }}">
        @foreach ($itens as $item)
            <li class="flex items-center gap-1.5">
                @unless ($loop->first)
                    <span aria-hidden="true" class="{{ $destaque ? 'text-white/40' : 'text-grafite/30' }}">/</span>
                @endunless
                @if (! empty($item['url']))
                    <a href="{{ $item['url'] }}" class="{{ $destaque ? 'font-medium text-white/90 transition hover:text-white' : 'font-medium text-azul transition hover:underline' }}">{{ $item['nome'] }}</a>
                @else
                    <span aria-current="page" class="{{ $destaque ? 'font-semibold text-white' : 'font-semibold text-grafite' }}">{{ $item['nome'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
