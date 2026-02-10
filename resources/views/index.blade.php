@php
    $manifestPath = public_path('asset-manifest.json');
    $manifest = [];
    if (file_exists($manifestPath)) {
        $manifest = json_decode(file_get_contents($manifestPath), true)['files'] ?? [];
    }
    $mainJs = $manifest['main.js'] ?? '';
    $mainCss = $manifest['main.css'] ?? '';
@endphp
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <link rel="icon" href="/favicon.ico" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />

    <!-- Cache-Busting Meta Tags -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <meta name="theme-color" content="#000000" />
    <meta name="description" content="KoboPoint - Digital Services Platform" />
    <link rel="apple-touch-icon" href="/logo192.png" />
    <link rel="manifest" href="/manifest.json" />
    <title>{{ env('APP_NAME', 'KoboPoint') }}</title>

    <script>
        // Professional Cache-Nuke: Force users to discard old design caches
        (function () {
            // 1. Unregister Service Workers
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.getRegistrations().then(function (registrations) {
                    for (let registration of registrations) {
                        registration.unregister();
                    }
                });
            }

            // 2. Clear old App Caches
            if (window.caches) {
                caches.keys().then(function (names) {
                    for (let name of names) caches.delete(name);
                });
            }

            // 3. Version Enforcement (Reload if build version changes)
            const currentVersion = "{{ $mainJs }}";
            const savedVersion = localStorage.getItem('app_version');
            if (savedVersion && savedVersion !== currentVersion) {
                localStorage.setItem('app_version', currentVersion);
                window.location.reload(true);
            } else {
                localStorage.setItem('app_version', currentVersion);
            }
        })();
    </script>

    @if($mainCss)
        <link href="{{ $mainCss }}" rel="stylesheet">
    @endif

    @if($mainJs)
        <script defer="defer" src="{{ $mainJs }}"></script>
    @endif
</head>

<body>
    <noscript>You need to enable JavaScript to run this app.</noscript>
    <div id="root"></div>
</body>

</html>