<footer class="foot">
	<div>
		<div class="brand-mark">W</div>
		<div class="brand-tag">Diagnose what matters. Prioritize fixes. Deliver client-ready reports.</div>
	</div>
	<div>
		<h5>Product</h5>
		<ul>
			<li><a href="#pricing">Pricing</a></li>
		</ul>
	</div>
	<div>
		<h5>Legal</h5>
		<ul>
			<li><a href="{{ route("legal.privacy") }}">Privacy</a></li>
			<li><a href="{{ route("legal.terms") }}">Terms</a></li>
			<li><a href="{{ route("legal.acceptable-use") }}">Acceptable use</a></li>
		</ul>
	</div>
</footer>

<div class="foot-bar">
	<div>© {{ config("app.name") }} · {{ date("Y") }}</div>
	<div>Built for agencies that ship.</div>
</div>
