<nav class="nav">
	<div class="logo">
		<span class="logo-mark">W</span>
		<span>{{ config("app.name") }}</span>
	</div>
	<div class="links">
		<a href="#pricing">Pricing</a>
	</div>
	<div class="spacer"></div>
	<div class="auth">
		<a class="login" href="{{ route("login") }}">Log in</a>
		@if($registrationEnabled)
			<a class="btn primary" href="{{ route("register") }}">Start free</a>
		@endif
	</div>
</nav>
