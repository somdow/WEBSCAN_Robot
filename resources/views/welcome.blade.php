<!DOCTYPE html>
<html lang="{{ str_replace("_", "-", app()->getLocale()) }}">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<title>{{ config("app.name") }} — Audit your site. Find what is costing you traffic.</title>
	<meta name="description" content="Spot SEO gaps, security risks, and trust signals competitors miss. Get AI-guided fixes and client-ready reports — in minutes, not days.">
	<link rel="preconnect" href="https://fonts.bunny.net">
	<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|jetbrains-mono:400,500|caveat:600,700&display=swap" rel="stylesheet">
	@vite(["resources/css/landing.css", "resources/js/landing.js"])
</head>
<body>
	@if(session("status"))
		<div class="session-flash" role="status">{{ session("status") }}</div>
	@endif
	@include("landing.nav", ["registrationEnabled" => $registrationEnabled])
	@include("landing.hero", ["registrationEnabled" => $registrationEnabled])
	@include("landing.modules")
	@include("landing.testimonials")
	@include("landing.pricing", ["plans" => $plans, "registrationEnabled" => $registrationEnabled])
	@include("landing.cta-final", ["registrationEnabled" => $registrationEnabled])
	@include("landing.footer")
</body>
</html>
