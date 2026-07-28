@props([
    'targets' => null,
    'messages' => [
        'Analizando tus datos…',
        'Consultando al asistente nutricional…',
        'Preparando el resultado…',
        'Casi listo…',
    ],
])

@php
    $msgJson = e(json_encode(array_values($messages), JSON_UNESCAPED_UNICODE));
@endphp

<div
    class="ai-busy"
    wire:loading.flex
    @if ($targets) wire:target="{{ $targets }}" @endif
    role="status"
    aria-live="polite"
    data-ai-busy
    data-messages='{!! $msgJson !!}'
>
    <div class="ai-busy-card">
        <div class="ai-spinner" aria-hidden="true"></div>
        <p class="ai-busy-title">Trabajando…</p>
        <p class="ai-busy-msg">{{ $messages[0] }}</p>
        <div class="ai-busy-bar" aria-hidden="true"><span></span></div>
    </div>
</div>
