<nav aria-label="Você está em" class="mb-6 text-sm">
    <ol class="flex flex-wrap items-center gap-1.5 text-grafite/60">
        @foreach ($itens as $item)
            <li class="flex items-center gap-1.5">
                @unless ($loop->first)
                    <span aria-hidden="true" class="text-grafite/30">/</span>
                @endunless
                @if (! empty($item['url']))
                    <a href="{{ $item['url'] }}" class="font-medium text-azul transition hover:underline">{{ $item['nome'] }}</a>
                @else
                    <span aria-current="page" class="font-semibold text-grafite">{{ $item['nome'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
