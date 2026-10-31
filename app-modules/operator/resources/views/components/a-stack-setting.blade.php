{{-- One setting in the stack's settings store, with whose it is where the screen
     has not said, what holds it, and changing it or putting it back: chosen among
     the values it lists or typed where it lists none, moved alone or with the
     settings the stack's refusal names, rehearsed, agreed to, and followed while
     the screen's change is about this one. --}}
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

    @if ($changing?->moves($setting->name) && $changing->named !== $setting->name)
        @if ($changing->toDefault)
            <x-design::note>{{ __('plugins.settings.alongside_back', ['setting' => $changing->named]) }}</x-design::note>
        @else
            <x-design::note>{{ __('plugins.settings.alongside', ['setting' => $changing->named]) }}</x-design::note>
            <x-operator::choices-of :setting="$setting" :chosen="$changing->alongside[$setting->name] ?? ''" :changing="$changing" />
        @endif
        <x-operator::quiet-action :label="__('plugins.settings.leave_out')" :tap="$tapping('leaveOut', $setting->name)" />
    @elseif ($changing?->named === $setting->name)
        @unless ($changing->toDefault)
            @if ($setting->choices === [])
                <native:outlined-text-input native:model="typed" label="{{ __('plugins.settings.value') }}" supporting="{{ $setting->name }}" />
            @else
                <x-operator::choices-of :setting="$setting" :chosen="$typed" :changing="$changing" />
            @endif
        @endunless
        @if (! $changing->toDefault)
            <x-operator::offered-action :label="__('plugins.settings.rehearse')" tap="rehearse()" :offer="$offerToSet" />
        @elseif ($changing->alongside !== [])
            <x-operator::offered-action :label="__('plugins.settings.rehearse')" tap="rehearse()" :offer="$offerToClear" />
        @endif

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
            @forelse ($toMoveAlongside as $alongside)
                <x-design::action :label="__('plugins.settings.also_move', ['setting' => $alongside])" :answers-to="__('plugins.settings.also_move_it', ['setting' => $alongside, 'with' => $setting->name])" :tap="$tapping('alsoMove', $alongside)" />
            @empty
            @endforelse
            <x-design::action :label="__('plugins.settings.start_again')" :answers-to="__('plugins.settings.start_again_on', ['name' => $setting->name])" tap="startAgain()" />
        @elseif ($going->change !== null)
            <x-design::notice>
                <x-design::strong>{{ __($going->change->didSaid) }}</x-design::strong>
            </x-design::notice>
            @forelse ($going->change->moves as $move)
                <x-design::body>{{ $move->toDefault ? __('plugins.settings.moves_to_default', ['setting' => $move->setting]) : __('plugins.settings.moves_to', ['setting' => $move->setting, 'value' => $move->value]) }}</x-design::body>
                <x-design::note>{{ $move->wasSet ? __('plugins.settings.was', ['was' => $move->was]) : __('plugins.settings.was_default') }}</x-design::note>
                @if ($move->consequence !== '')
                    <x-design::note>{{ __('plugins.settings.consequence', ['consequence' => $move->consequence]) }}</x-design::note>
                @endif
            @empty
            @endforelse
            @if ($going->change->restarts === '')
                <x-design::body>{{ __('plugins.settings.restarts_nothing') }}</x-design::body>
            @else
                <x-design::body>{{ __('plugins.settings.restarts', ['services' => $going->change->restarts]) }}</x-design::body>
            @endif
            @unless ($going->change->regenerates === '')
                <x-design::note>{{ __('plugins.settings.regenerates', ['services' => $going->change->regenerates]) }}</x-design::note>
            @endunless
            @unless ($going->change->starts === '')
                <x-design::body>{{ __('plugins.settings.starts', ['services' => $going->change->starts]) }}</x-design::body>
            @endunless
            @forelse ($going->change->waiting as $waiting)
                <x-operator::a-service-waiting :waiting="$waiting" />
            @empty
            @endforelse
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
