<x-app-layout>
	<x-slot name="header">
		<div>
			<h1 class="text-[2.5rem] font-bold leading-tight tracking-tight text-text-primary">New Project</h1>
			<p class="mt-1 text-sm text-text-secondary">Add a website to start monitoring its SEO health.</p>
			<x-breadcrumb :items="array(
				array('label' => 'Home', 'url' => route('dashboard')),
				array('label' => 'Projects', 'url' => route('projects.index')),
				array('label' => 'New Project'),
			)" />
		</div>
	</x-slot>

	<div class="mx-auto max-w-2xl">
		<div class="rounded-lg border border-border bg-surface p-6">
			<form method="POST" action="{{ route("projects.store") }}">
				@csrf

				<div>
					<x-input-label for="name" value="Project Name" />
					<x-text-input id="name" name="name" type="text" class="mt-1.5 block w-full" :value="old('name')" required autofocus placeholder="My Website" />
					<x-input-error :messages="$errors->get('name')" class="mt-2" />
				</div>

				<div class="mt-5">
					<x-input-label for="url" value="Website URL" />
					<x-text-input id="url" name="url" type="text" class="mt-1.5 block w-full" :value="old('url')" required placeholder="example.com" />
					<x-input-error :messages="$errors->get('url')" class="mt-2" />
					<p class="mt-1.5 text-xs text-text-tertiary">https:// will be added automatically if omitted</p>
				</div>

				<div class="mt-5" style="display: none;">
					<x-input-label for="target_keywords">
						Target Keywords <span class="font-normal text-text-tertiary">(Optional)</span>
					</x-input-label>
					<textarea id="target_keywords" name="target_keywords" rows="4" class="mt-1.5 block w-full rounded-md border-border shadow-sm focus:border-accent focus:ring-accent" placeholder="e.g. running shoes, best running shoes, marathon training">{{ old('target_keywords') }}</textarea>
					<x-input-error :messages="$errors->get('target_keywords')" class="mt-2" />
					<p class="mt-1.5 text-xs text-text-tertiary">Comma-separated keywords for SEO analysis and AI optimization. If left blank, we'll auto-detect from your page content.</p>
				</div>

				<div class="mt-6 flex items-center justify-end gap-3">
					<a href="{{ route("projects.index") }}" class="rounded-md px-4 py-2 text-sm font-medium text-text-secondary transition hover:text-text-primary">
						Cancel
					</a>
					<x-primary-button>
						Create Project
					</x-primary-button>
				</div>
			</form>
		</div>
	</div>
</x-app-layout>
