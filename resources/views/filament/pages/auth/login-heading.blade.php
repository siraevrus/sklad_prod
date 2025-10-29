@php
    $heading = $this->getHeading();
    $logo = $this->getLogoBrand();
@endphp

<div class="flex items-center gap-4">
    @if ($logo)
        <img src="{{ $logo }}" alt="Logo" class="h-20 w-20 object-contain" />
    @endif
    
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        {{ $heading }}
    </h1>
</div>
