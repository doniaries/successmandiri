@if ($url)
    @php
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
    @endphp

    @if ($extension === 'pdf')
        <div class="w-full h-[80vh]">
            <embed src="{{ $url }}" type="application/pdf" width="100%" height="100%" />
        </div>
    @else
        <div class="flex justify-center w-full">
            <img src="{{ $url }}" alt="Preview" class="h-auto max-w-full" />
        </div>
    @endif
@else
    <div class="p-4 text-center text-gray-500">
        File tidak tersedia
    </div>
@endif
