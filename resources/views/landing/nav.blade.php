<nav class="nav">
	<a href="{{ route("home") }}" class="logo" aria-label="{{ config("app.name") }} — go to homepage">
		<img class="logo-mark" src="{{ asset("images/webscanrobot-logo.webp") }}" alt="">
	</a>
	<div class="links">
		<a href="#how-it-works">How it works</a>
		<a href="#features">Features</a>
		<a href="#pricing">Pricing</a>
		<a href="#customers">Customers</a>
	</div>
	<div class="spacer"></div>
	<div class="auth">
		<a class="login" href="{{ route("login") }}">Log in</a>
		@if($registrationEnabled)
			<a class="btn primary" href="{{ route("register") }}">Start free</a>
		@endif
	</div>
</nav>
