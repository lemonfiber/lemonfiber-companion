{{-- One setting in the stack's settings store, with whose it is where the screen
     has not said, what holds it, and changing it or putting it back: rehearsed,
     agreed to, and followed while the screen's change is about this one. --}}
<x-design::card>
    <x-design::strong>{{ $setting->name }}</x-design::strong>
    @if ($setting->whose !== null)
        <x-design::note>{{ __($setting->whose->key, $setting->whose->filling) }}</x-design::note>
    @endif
    @if ($setting->isSet)
        <x-design::body>{{ __('plugins.settings.set_by_you') }}</x-design::body>
        <x-design::body>{{ $setting->value }}</x-design::body>
    @else
        <x-design::body>{{ __('plugins.settings.default') }}</x-design::body>
    @endif
    @forelse ($setting->readers as $reader)
        @if ($reader->declaresADefault)
            <x-design::note>{{ __('plugins.settings.read_by_default', ['service' => $reader->service, 'variable' => $reader->variable, 'default' => $reader->default]) }}</x-design::note>
        @else
            <x-design::note>{{ __('plugins.settings.read_by', ['service' => $reader->service, 'variable' => $reader->variable]) }}</x-design::note>
        @endif
    @empty
        <x-design::note>{{ __('plugins.settings.read_by_none') }}</x-design::note>
    @endforelse

    @if ($changing?->named === $setting->name)
        @unless ($changing->toDefault)
            <native:outlined-text-input native:model="typed" label="{{ __('plugins.settings.value') }}" supporting="{{ $setting->name }}" />
            <x-operator::offered-action :label="__('plugins.settings.rehearse')" tap="rehearse()" :offer="$offerToSet" />
        @endunless

        @if (! $going->stands->went->cameBack())
            <x-operator::what-stood-in-the-way
                :settings-would-not-open="$settingsWouldNotOpen"
                :went="$going->stands->went"
                :goes="$goes"
            />
        @elseif ($going->stands->isWorking())
            <x-design::standing :said="__('plugins.settings.working')" tone="working" />
        @elseif ($going->stands->hasEnded())
            <x-design::notice tone="unknown">
                <x-design::strong>{{ __('plugins.settings.no_outcome') }}</x-design::strong>
            </x-design::notice>
            <x-design::action :label="__('plugins.settings.start_again')" :answers-to="__('plugins.settings.start_again_on', ['name' => $setting->name])" tap="startAgain()" />
        @elseif ($going->refused !== null)
            <x-design::notice>
                <x-design::strong>{{ __('plugins.settings.refused') }}</x-design::strong>
                <x-operator::refused-in-its-words :refused="$going->refused" />
            </x-design::notice>
            <x-design::action :label="__('plugins.settings.start_again')" :answers-to="__('plugins.settings.start_again_on', ['name' => $setting->name])" tap="startAgain()" />
        @elseif ($going->change !== null)
            <x-design::notice>
                <x-design::strong>{{ __($going->change->didSaid) }}</x-design::strong>
            </x-design::notice>
            @if ($going->change->toDefault)
                <x-design::body>{{ __('plugins.settings.to_default') }}</x-design::body>
            @else
                <x-design::body>{{ __('plugins.settings.to', ['value' => $going->change->value]) }}</x-design::body>
            @endif
            @if ($going->change->wasSet)
                <x-design::note>{{ __('plugins.settings.was', ['was' => $going->change->was]) }}</x-design::note>
            @else
                <x-design::note>{{ __('plugins.settings.was_default') }}</x-design::note>
            @endif
            @if ($going->change->restarts === '')
                <x-design::body>{{ __('plugins.settings.restarts_nothing') }}</x-design::body>
            @else
                <x-design::body>{{ __('plugins.settings.restarts', ['services' => $going->change->restarts]) }}</x-design::body>
            @endif
            @unless ($going->change->regenerates === '')
                <x-design::note>{{ __('plugins.settings.regenerates', ['services' => $going->change->regenerates]) }}</x-design::note>
            @endunless
            @if ($going->change->consequence !== '')
                <x-design::note>{{ __('plugins.settings.consequence', ['consequence' => $going->change->consequence]) }}</x-design::note>
            @endif
            @if ($going->change->unrestarted !== '')
                <x-design::note>{{ __('plugins.settings.unrestarted', ['why' => $going->change->unrestarted]) }}</x-design::note>
            @endif
            @if ($going->change->mayBeAgreed)
                @if ($going->change->toDefault)
                    <x-operator::offered-action :label="__('plugins.settings.agree_back')" tap="agree()" :offer="$offerToClear" />
                @else
                    <x-operator::offered-action :label="__('plugins.settings.agree')" tap="agree()" :offer="$offerToSet" />
                @endif
            @endif
        @endif

        <x-operator::quiet-action :label="__('plugins.settings.never')" tap="never()" />
    @else
        <x-operator::offered-action :label="__('plugins.settings.change')" :answers-to="__('plugins.settings.change_it', ['name' => $setting->name])" :tap="$tapping('change', $setting->name)" :offer="$offerToSet" />
        @if ($setting->isSet)
            <x-operator::offered-action :label="__('plugins.settings.back_to_default')" :answers-to="__('plugins.settings.back_to_default_it', ['name' => $setting->name])" :tap="$tapping('backToDefault', $setting->name)" :offer="$offerToClear" />
        @endif
    @endif
</x-design::card>
