@use('Modules\Operator\Internal\HowADecisionIsChanged')
<x-design::card>
    <x-design::strong>{{ $decision->key }}</x-design::strong>
    @if ($decision->how === HowADecisionIsChanged::ToObtain)
        <x-design::note>{{ __('decided.to_obtain') }}</x-design::note>
    @elseif ($decision->how === HowADecisionIsChanged::AtTheMachine)
        <x-design::body>{{ __($decision->standsSaid) }}</x-design::body>
    @elseif ($decision->isSet && $decision->setting !== null && $decision->goes === '')
        <x-design::note>{{ __('decided.not_listed_where_kept') }}</x-design::note>
    @elseif ($decision->isSet)
        <x-design::verbatim>{{ $decision->holds }}</x-design::verbatim>
    @else
        <x-design::note>{{ __('decided.not_set') }}</x-design::note>
    @endif
    @if ($decision->costSaid !== '')
        <x-design::note>{{ __($decision->costSaid) }}</x-design::note>
    @endif
    <x-design::body>{{ $decision->affects }}</x-design::body>

    @if ($decision->how === HowADecisionIsChanged::Here)
        @if ($open)
            <native:outlined-text-input
                native:model="typed"
                label="{{ $decision->key }}"
                supporting="{{ __('config.what_it_would_hold') }}"
            />
            @if ($decision->setting === null)
                <x-operator::the-review :key="$decision->key" :proposal="$proposal" :waiting="$waiting" :landing-said="$landingSaid" />
            @else
                <x-operator::the-review-where-kept :key="$decision->key" :going="$going" :settings-would-not-open="$settingsWouldNotOpen" :goes="$goes" />
            @endif
        @else
            <x-operator::offered-action
                :label="__('config.change_this')"
                :answers-to="__('config.change_key', ['key' => $decision->key])"
                :tap="$tapping('change', $decision->key)"
                :offer="$offer"
                drawn="link"
            />
        @endif
        @if ($decision->goes !== '')
            <x-design::note>{{ __('decided.kept_in_the_settings', ['setting' => $decision->setting]) }}</x-design::note>
            <x-design::action :label="__('decided.change_where_kept')" :answers-to="__('decided.change_where_kept_of', ['key' => $decision->key])" :goes="$decision->goes" tone="tonal" />
        @endif
    @elseif ($decision->how === HowADecisionIsChanged::AtTheMachine)
        <x-design::note>{{ __('decided.at_the_machine') }}</x-design::note>
        <x-design::verbatim>{{ $decision->command }}</x-design::verbatim>
        <x-design::action :label="__('decided.copy')" :answers-to="__('decided.copy_it', ['key' => $decision->key])" :tap="$tapping('copy', $decision->command)" tone="tonal" />
    @endif
</x-design::card>
