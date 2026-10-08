@use('Modules\Operator\View\HowAnOfferIsDrawn')
{{-- A button for something the stack is asked to do. Pressed where the stack
     offers it; drawn unpressable with the reason beside it where it does not,
     and never left out. Once it cannot be pressed every look is the platform's
     disabled button, because a line of words cannot be marked as not usable. --}}
@if ($pressable && $look === HowAnOfferIsDrawn::Quiet)
    <x-operator::quiet-action :label="$label" :tap="$tap" />
@elseif ($pressable && $look === HowAnOfferIsDrawn::Link)
    <x-design::link :label="$label" :tap="$tap" :answers-to="$answersTo" />
@else
    <x-design::action :label="$label" :tap="$tap" :tone="$tone" :answers-to="$answersTo" :disabled="! $pressable" />
@endif
@if ($offer->note !== '')
    <x-operator::note>{{ __($offer->note) }}</x-operator::note>
@endif
@if ($offer->updatesAt !== '')
    <x-operator::quiet-action label="{{ __('connection.go_to_updates') }}" :goes="$offer->updatesAt" />
@endif
