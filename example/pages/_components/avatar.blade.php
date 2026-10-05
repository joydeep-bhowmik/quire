@props(['name', 'size' => 'md'])

<span {{ $attributes->class([
    'grid shrink-0 place-items-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-500 font-semibold text-white',
    'size-10 text-sm' => $size === 'md',
    'size-20 text-2xl' => $size === 'lg',
]) }}>{{ initials($name) }}</span>
