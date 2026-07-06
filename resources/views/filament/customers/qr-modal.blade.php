<div class="flex flex-col items-center gap-4 text-center">
    <img src="{{ $dataUri }}" alt="Customer QR code" class="rounded-lg border border-gray-200 dark:border-gray-700" />
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Scan with any phone camera to open this customer's quick lookup page for sales reps.
    </p>
    <code class="break-all rounded bg-gray-100 px-2 py-1 text-xs dark:bg-gray-800">{{ $url }}</code>
</div>
