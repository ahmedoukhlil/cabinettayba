@props([
    'id',
    'title',
    'subtitle' => null,
    'icon' => null,
    'close',
    'maxWidth' => 'max-w-3xl',
    'level' => 'base',
    'bodyClass' => 'modal-body',
])

<div
    {{ $attributes->class(['modal-overlay', 'modal-overlay-nested' => $level === 'nested']) }}
    x-data
    x-init="$nextTick(() => $refs.dialog.focus())"
    wire:click.self="{{ $close }}"
    wire:keydown.escape.window="{{ $close }}"
>
    <section
        x-ref="dialog"
        tabindex="-1"
        class="modal-box {{ $maxWidth }}"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $id }}-title"
        @if($subtitle) aria-describedby="{{ $id }}-description" @endif
        style="outline: none"
    >
        <header class="modal-header">
            <div class="min-w-0">
                <h2 id="{{ $id }}-title" class="flex items-center gap-2">
                    @if($icon)<i class="fas {{ $icon }}" aria-hidden="true"></i>@endif
                    <span>{{ $title }}</span>
                </h2>
                @if($subtitle)
                    <p id="{{ $id }}-description">{{ $subtitle }}</p>
                @endif
            </div>
            <button type="button" wire:click="{{ $close }}" class="modal-close" aria-label="Fermer {{ $title }}">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </header>

        <div class="{{ $bodyClass }}">
            {{ $slot }}
        </div>

        @isset($footer)
            <footer class="modal-footer">
                {{ $footer }}
            </footer>
        @endisset
    </section>
</div>
