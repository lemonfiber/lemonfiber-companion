{{-- Every value a setting lists, each a chip, the one chosen marked, and each
     that cannot be chosen drawn so; each that is not a choice now, with what it needs. --}}
<x-design::chips>
    @forelse ($setting->choices as $choice)
        <x-design::chip :label="$choice->value" :tap="$tapping('choose', $setting->name, $choice->value)" :chosen="$choice->value === $chosen" :disabled="! $offers($choice)" />
    @empty
    @endforelse
</x-design::chips>
@forelse ($setting->choices as $choice)
    @unless ($choice->isOpen)
        <x-design::note>{{ __('plugins.settings.not_a_choice_now', ['value' => $choice->value]) }}</x-design::note>
        @forelse ($choice->needs as $needs)
            <x-design::note>{{ __($needs->key, $needs->filling) }}</x-design::note>
        @empty
        @endforelse
    @endunless
@empty
@endforelse
