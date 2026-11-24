{{-- One decision the settings store keeps, open for changing through the stack's review of `settings-set`: what it would come to, agreeing to it, or leaving it. --}}
@if (! $going->stands->went->cameBack())
    <x-operator::what-stood-in-the-way
        :settings-would-not-open="$settingsWouldNotOpen"
        :went="$going->stands->went"
        :goes="$goes"
    />
@elseif ($going->stands->isWorking())
    <x-design::standing :said="__('plugins.settings.working')" tone="working" />
    <x-operator::following-a-job :said="$said" />
@elseif ($going->stands->hasEnded())
    <x-operator::why-it-ended :stands="$going->stands" />
    <x-design::notice tone="unknown">
        <x-design::strong>{{ __('plugins.settings.no_outcome') }}</x-design::strong>
    </x-design::notice>
@elseif ($going->refused !== null)
    <x-design::notice>
        <x-design::strong>{{ __('plugins.settings.refused') }}</x-design::strong>
        <x-operator::refused-in-its-words :refused="$going->refused" />
    </x-design::notice>
@elseif ($going->change !== null)
    <x-operator::what-a-setting-change-comes-to :change="$going->change" />
@endif

@if ($going->change?->mayBeAgreed ?? false)
    <x-design::action
        :label="__('config.agree')"
        :answers-to="__('config.agree_to', ['key' => $key])"
        :tap="$tapping('agree', $key)"
    />
@elseif (! $going->stands->isWorking())
    <x-design::action
        :label="__('config.what_would_happen')"
        :answers-to="__('config.what_would_happen_to', ['key' => $key])"
        :tap="$tapping('wouldBe', $key)"
    />
@endif

<x-design::action
    :label="__('config.never_mind')"
    :answers-to="__('config.never_mind_about', ['key' => $key])"
    tap="never()"
    tone="tonal"
/>
