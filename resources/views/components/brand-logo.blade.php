@props([
    'size' => 'md', // sm | md | lg
    'showName' => true,
    'showBadge' => true,
    'link' => false,
])

@php
    $markClass = match ($size) {
        'lg' => 'brand-mark brand-mark-lg',
        'sm' => 'brand-mark brand-mark-sm',
        default => 'brand-mark',
    };
    $nameClass = match ($size) {
        'lg' => 'brand-name brand-name-lg',
        'sm' => 'brand-name brand-name-sm',
        default => 'brand-name',
    };
@endphp

@if ($link)
    <a {{ $attributes->class(['brand']) }} href="{{ route('dashboard') }}" aria-label="DietaIA by Ancame — Ir al inicio">
@else
    <div {{ $attributes->class(['brand', 'brand-static']) }} role="img" aria-label="DietaIA by Ancame">
@endif
    <svg class="{{ $markClass }}" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <rect width="48" height="48" rx="14" fill="#ecfdf5"/>
        <path d="M24 8c-7.4 8.1-11.2 14.8-11.2 21.2 0 6.2 4.9 11.1 11.2 11.1s11.2-5 11.2-11.1C35.2 22.8 31.4 16.1 24 8z" fill="#10b981"/>
        <path d="M24 12.2c1.8 4.2 4.7 8.4 4.7 12.8 0 2.7-2.1 4.8-4.7 4.8" stroke="#065f46" stroke-width="2" stroke-linecap="round" opacity=".55"/>
        <path d="M33.5 11.5l1.1 2.6 2.6 1.1-2.6 1.1-1.1 2.6-1.1-2.6-2.6-1.1 2.6-1.1 1.1-2.6z" fill="#34d399"/>
        <path d="M37.8 18.2l.7 1.6 1.6.7-1.6.7-.7 1.6-.7-1.6-1.6-.7 1.6-.7.7-1.6z" fill="#6ee7b7"/>
        <path d="M30.8 7.8l.55 1.25 1.25.55-1.25.55-.55 1.25-.55-1.25-1.25-.55 1.25-.55.55-1.25z" fill="#a7f3d0"/>
    </svg>
    @if ($showName || $showBadge)
        <span class="brand-text-wrap">
            @if ($showName)
                <span class="{{ $nameClass }}">Dieta<span class="brand-ia">IA</span></span>
            @endif
            @if ($showBadge)
                <span class="brand-badge">by Ancame</span>
            @endif
        </span>
    @endif
@if ($link)
    </a>
@else
    </div>
@endif
