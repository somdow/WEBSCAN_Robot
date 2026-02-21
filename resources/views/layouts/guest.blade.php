<!DOCTYPE html>
<html lang="{{ str_replace("_", "-", app()->getLocale()) }}">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="csrf-token" content="{{ csrf_token() }}">

		<title>{{ config("app.name", "HELLO WEB_SCANS") }}</title>

		@vite(["resources/css/app.css", "resources/js/app.js"])
	</head>
	<body class="font-sans antialiased text-text-primary">
		<div class="flex min-h-screen flex-col items-center bg-background pt-6 sm:justify-center sm:pt-0">
			<div class="mb-6">
				<a href="/" class="text-2xl font-bold tracking-tight text-text-primary">
					{{ config("app.name", "HELLO WEB_SCANS") }}
				</a>
			</div>

			<div class="w-full overflow-hidden rounded-lg border border-border bg-surface px-6 py-6 shadow-sm sm:max-w-md">
				{{ $slot }}
			</div>
		</div>
		<x-toast-container />
	</body>
</html>
