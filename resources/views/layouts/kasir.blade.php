<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
    <style>
        body {
            overflow: hidden;
        }

        .scroll-thin::-webkit-scrollbar {
            width: 4px;
        }

        .scroll-thin::-webkit-scrollbar-thumb {
            background: #4b5563;
            border-radius: 9999px;
        }
    </style>
</head>

<body class="bg-gray-950 text-white h-full antialiased">
    {{ $slot }}
    @livewireScripts
    <script>
        window.addEventListener('open-print-layout', event => {
            window.open(
                '/penjualan/' + event.detail.id + '/print',
                '_blank',
                'width=850,height=700,scrollbars=yes'
            );
        });
    </script>
</body>

</html>