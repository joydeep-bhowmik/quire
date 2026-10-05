@props(['title', 'href' => null])

<div {{ $attributes->merge(['class' => 'card']) }}>
    <strong>
        @if ($href)
            <a href="{{ $href }}">{{ $title }}</a>
        @else
            {{ $title }}
        @endif
    </strong>
    <div>{{ $slot }}</div>
</div>
