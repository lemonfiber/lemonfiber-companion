@use('Modules\Stacks\Api\AStacksScreen')
<x-operator::screen-opens :title="$this->title()->named" :back="$this->hasAWayBack()" />

<x-operator::content>

@if ($this->title()->isKnown())
    {{-- The name on its poster, and the one action a title carries. Play is drawn and cannot be used, with the reason
         beside it in the app's own words: the core hands the app no way to
         play a title. --}}
    <x-household::poster :poster="$this->title()->poster" />
    <x-design::action label="{{ __('household.title.play') }}" :answersTo="__('household.title.play_named', ['title' => $this->title()->named])" :disabled="true" />
    <x-design::note>{{ __('household.title.cannot_play') }}</x-design::note>
@else
    {{-- Opened without what Home hands it, so there is no title to name, and
         Home is where it is opened from again. --}}
    <x-design::body>{{ __('household.title.not_handed_over') }}</x-design::body>
    <x-design::action label="{{ __('household.title.to_home') }}" :goes="$this->goes()->to(AStacksScreen::Shelf)" tone="tonal" />
@endif
</x-operator::content>

<x-household::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
