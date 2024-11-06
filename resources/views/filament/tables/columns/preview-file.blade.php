<div class="px-4 py-3" x-data>
    @if ($getState())
        @php
            $imageUrl = asset('storage/' . $getState());
        @endphp

        <div class="flex items-center justify-center">
            <img src="{{ $imageUrl }}" class="object-cover w-8 h-8 rounded cursor-pointer"
                x-on:click.stop.prevent="$dispatch('open-modal', { id: 'preview-image-{{ $getRecord()->id }}' })"
                alt="Preview" />
        </div>

        <x-filament::modal id="preview-image-{{ $getRecord()->id }}" width="xl">
            @include('filament.components.image-viewer', ['url' => $imageUrl])
        </x-filament::modal>
    @else
        <div class="flex items-center justify-center">
            <x-heroicon-o-x-circle class="w-6 h-6 text-gray-400" />
        </div>
    @endif
</div>
