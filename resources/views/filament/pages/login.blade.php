@php
    $logoUrl = $this->getLogoBrand();
@endphp

<x-filament-panels::page
    :heading="null"
    :subheading="null"
>
    <div class="mx-auto w-full max-w-sm space-y-6">
        <!-- Custom heading with logo -->
        <div class="flex items-center justify-center gap-4 mb-8">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="Logo" class="h-20 w-20 object-contain" />
            @endif
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ $this->getHeading() }}
            </h1>
        </div>

        <!-- Form -->
        <form
            wire:submit="authenticate"
            class="space-y-6"
        >
            {{ $this->form }}

            <x-filament::button
                type="submit"
                class="w-full"
            >
                {{ __('filament-panels::pages/auth/login.form.actions.authenticate.label') }}
            </x-filament::button>
        </form>
    </div>
</x-filament-panels::page>
