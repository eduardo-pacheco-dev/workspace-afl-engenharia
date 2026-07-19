@props([
    'modules' => [],
])

@foreach ($modules as $module)
    <flux:sidebar.group :heading="$module['name']" expandable class="grid">
        @foreach ($module['items'] as $item)
            <flux:sidebar.item
                :icon="$item['icon'] ?? 'circle'"
                :href="$item['href']"
                :current="request()->routeIs($item['route'] ?? '')"
                wire:navigate
            >
                {{ $item['name'] }}
            </flux:sidebar.item>
        @endforeach
    </flux:sidebar.group>
@endforeach
