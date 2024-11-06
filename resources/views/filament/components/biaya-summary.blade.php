{{-- resources/views/filament/components/biaya-summary.blade.php --}}
<div class="space-y-3">
    <div class="flex justify-between text-sm">
        <span>Total Transaksi</span>
        <span class="font-medium">Rp {{ number_format($total, 0, ',', '.') }}</span>
    </div>
    <div class="flex justify-between text-sm">
        <span>Upah Bongkar</span>
        <span class="font-medium text-danger-600">- Rp {{ number_format($upahBongkar, 0, ',', '.') }}</span>
    </div>
    <div class="flex justify-between text-sm">
        <span>Biaya Lain</span>
        <span class="font-medium text-danger-600">- Rp {{ number_format($biayaLain, 0, ',', '.') }}</span>
    </div>
    <hr class="my-2">
    <div class="flex justify-between text-lg font-bold">
        <span>Sisa Bayar</span>
        <span class="text-success-600">Rp {{ number_format($sisaBayar, 0, ',', '.') }}</span>
    </div>
</div>
