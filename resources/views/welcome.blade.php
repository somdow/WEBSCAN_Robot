<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>{{ config('app.name', 'Laravel') }}</title>
		<link rel="preconnect" href="https://fonts.bunny.net">
		<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
		@vite(['resources/css/app.css', 'resources/js/app.js'])
	</head>
	<body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
		<div class="flex items-center justify-center w-full lg:grow">
			@auth
				<a
					href="{{ url('/dashboard') }}"
					class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal"
				>
					Dashboard
				</a>
			@else
				<a
					href="{{ route('login') }}"
					class="inline-block px-6 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm text-sm font-medium leading-normal hover:bg-black dark:hover:bg-white transition"
				>
					Log in
				</a>
			@endauth
		</div>

		<footer class="w-full lg:max-w-4xl max-w-[335px] text-center text-[13px] leading-[20px] text-[#706f6c] dark:text-[#A1A09A]">
			<div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2">
				<a href="{{ route('legal.terms') }}" class="hover:text-[#1b1b18] dark:hover:text-[#EDEDEC] transition">Terms of Service</a>
				<a href="{{ route('legal.privacy') }}" class="hover:text-[#1b1b18] dark:hover:text-[#EDEDEC] transition">Privacy Policy</a>
				<a href="{{ route('legal.acceptable-use') }}" class="hover:text-[#1b1b18] dark:hover:text-[#EDEDEC] transition">Acceptable Use</a>
			</div>
			<p class="mt-2">&copy; {{ date('Y') }} {{ config('app.name', 'HELLO WEB_SCANS') }}</p>
		</footer>
	</body>
</html>
