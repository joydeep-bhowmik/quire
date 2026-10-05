{{-- Secondary button for error pages. Pass href for a link, or onclick for a button. --}}
@props(['href' => null])

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class('rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800') }}>{{ $slot }}</a>
@else
    <button type="button" {{ $attributes->class('rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800') }}>{{ $slot }}</button>
@endif
