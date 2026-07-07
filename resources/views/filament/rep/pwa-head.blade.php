<link rel="manifest" href="{{ asset('manifest.json') }}">
<meta name="theme-color" content="#059669">
<link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('{{ asset('sw.js') }}', { scope: '/rep/' });
        });
    }
</script>
