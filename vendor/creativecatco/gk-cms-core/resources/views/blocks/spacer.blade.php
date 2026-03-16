@php
    $size = $block['data']['size'] ?? 'medium';
    $showLine = $block['data']['show_line'] ?? false;

    $sizeClass = match($size) {
        'small' => 'py-4',
        'large' => 'py-12',
        'xlarge' => 'py-16',
        default => 'py-8',
    };
@endphp

<div class="{{ $sizeClass }}">
    @if($showLine)
        <div class="max-w-7xl mx-auto px-6">
            <hr class="border-gray-200">
        </div>
    @endif
</div>
