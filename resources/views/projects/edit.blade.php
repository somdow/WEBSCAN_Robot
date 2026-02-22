<x-app-layout>
	<x-slot name="header">
		<div>
			<h1 class="text-[2.5rem] font-bold leading-tight tracking-tight text-text-primary">Edit Project</h1>
			<p class="mt-1 text-sm text-text-secondary">Update your project details.</p>
			<x-breadcrumb :items="array(
				array('label' => 'Home', 'url' => route('dashboard')),
				array('label' => 'Projects', 'url' => route('projects.index')),
				array('label' => $project->name, 'url' => route('projects.show', $project)),
				array('label' => 'Edit'),
			)" />
		</div>
	</x-slot>

	<div class="mx-auto max-w-2xl space-y-8">
		<div class="rounded-lg border border-border bg-surface p-6 shadow-card">
			<form method="POST" action="{{ route("projects.update", $project) }}">
				@csrf
				@method("PUT")

				<div>
					<x-input-label for="name" value="Project Name" />
					<x-text-input id="name" name="name" type="text" class="mt-1.5 block w-full" :value="old('name', $project->name)" required autofocus placeholder="My Website" />
					<x-input-error :messages="$errors->get('name')" class="mt-2" />
				</div>

				<div class="mt-5">
					<x-input-label for="url" value="Website URL" />
					<x-text-input id="url" name="url" type="text" class="mt-1.5 block w-full" :value="old('url', $project->url)" required placeholder="example.com" />
					<x-input-error :messages="$errors->get('url')" class="mt-2" />
					<p class="mt-1.5 text-xs text-text-tertiary">https:// will be added automatically if omitted</p>
				</div>

				<div class="mt-5">
					<x-input-label for="target_keywords">
						Target Keywords <span class="font-normal text-text-tertiary">(Optional)</span>
					</x-input-label>
					<textarea id="target_keywords" name="target_keywords" rows="4" class="mt-1.5 block w-full rounded-md border-border shadow-sm focus:border-accent focus:ring-accent" placeholder="e.g. running shoes, best running shoes, marathon training">{{ old('target_keywords', implode(', ', $project->target_keywords ?? array())) }}</textarea>
					<x-input-error :messages="$errors->get('target_keywords')" class="mt-2" />
					<p class="mt-1.5 text-xs text-text-tertiary">Comma-separated keywords for SEO analysis and AI optimization. If left blank, we'll auto-detect from your page content.</p>
				</div>

				<div class="mt-6 flex items-center justify-end gap-3">
					<a href="{{ route("projects.show", $project) }}" class="rounded-md px-4 py-2 text-sm font-medium text-text-secondary transition hover:text-text-primary">
						Cancel
					</a>
					<x-primary-button>
						Save Changes
					</x-primary-button>
				</div>
			</form>
		</div>

		{{-- Danger Zone --}}
		<div class="rounded-lg border border-red-200 bg-red-50 p-6">
			<h3 class="text-sm font-semibold text-red-700">Danger Zone</h3>
			<p class="mt-1 text-sm text-red-600/80">Deleting this project will permanently remove all associated scan data. This action cannot be undone.</p>
			<form method="POST" action="{{ route("projects.destroy", $project) }}" class="mt-4" x-data="{ confirming: false }">
				@csrf
				@method("DELETE")
				<div x-show="!confirming">
					<x-danger-button type="button" @click="confirming = true">
						Delete Project
					</x-danger-button>
				</div>
				<div x-show="confirming" x-cloak class="flex items-center gap-3">
					<p class="text-sm text-red-700">Are you sure? This cannot be undone.</p>
					<x-danger-button type="submit">
						Yes, Delete
					</x-danger-button>
					<button type="button" @click="confirming = false" class="text-sm font-medium text-text-secondary hover:text-text-primary">
						Cancel
					</button>
				</div>
			</form>
		</div>
	</div>
</x-app-layout>
