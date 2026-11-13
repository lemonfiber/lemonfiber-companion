@use('Modules\Stacks\Api\AStacksScreen')
<x-operator::screen-opens :title="__('household.your_ask.heading')" :back="$this->hasAWayBack()" />

<x-operator::content>

@if ($this->ask()->ask !== null)
    {{-- Lettered with its name until the core serves its cover. --}}
    <x-household::poster :poster="$this->ask()->ask->poster" />
    <x-design::title>{{ is_string($this->ask()->ask->title) ? $this->ask()->ask->title : __($this->ask()->ask->title->key, $this->ask()->ask->title->filling) }}</x-design::title>
    <x-design::note>{{ __($this->ask()->ask->kind) }}</x-design::note>

    {{-- Where it stands is the one action, shown and not usable: there is no
         Play for music or books. --}}
    <x-design::action :label="__($this->ask()->ask->standing)" tap="askAgain()" :disabled="true" />

    @if ($this->ask()->ask->saysWhereToFindIt())
        <x-design::body>{{ __($this->ask()->ask->foundIn) }}</x-design::body>
    @endif
@elseif ($this->ask()->cameBack)
    {{-- The house answered and this ask is not among what they asked for. --}}
    <x-design::body>{{ __('household.your_ask.not_among') }}</x-design::body>
    <x-design::action :label="__('household.found.to_requests')" :goes="$this->goes()->to(AStacksScreen::Owed)" tone="tonal" />
@elseif ($this->ask()->stopped !== null)
    <x-design::notice tone="unknown">
        <x-household::what-stood-in-the-way :stopped="$this->ask()->stopped" />
    </x-design::notice>
    <x-design::action :label="__('household.ask_again')" tap="askAgain()" />
@else
    <x-design::body>{{ __('household.signed_out') }}</x-design::body>
    <x-design::action :label="__('connection.sign_in')" :goes="$this->signIn()" />
@endif
</x-operator::content>

<x-household::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
