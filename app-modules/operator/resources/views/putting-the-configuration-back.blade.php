@use('Modules\Kernel\Api\WhatToChange')
@use('Modules\Stacks\Api\AStacksScreen')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-operator::heading>{{ __('stacks.reset.heading') }}</x-operator::heading>

@if ($this->answer()->isWorking)
    <x-operator::emphasis>{{ __($this->answer()->words->working) }}</x-operator::emphasis>

    {{-- The stack says nothing of a job until it finishes, so the screen
         asks again on its cadence rather than drawing progress. --}}
@elseif ($this->answer()->hasEnded)
    {{-- Not a failure. After a yes the files may well have gone back, and
         the settings are where to look before asking for anything again. --}}
    <x-operator::emphasis>{{ __($this->answer()->words->ended) }}</x-operator::emphasis>
@elseif ($this->answer()->refused !== null)
    {{-- The stack's answer, in its words, and what it named where it named
         anything. --}}
    <x-operator::emphasis>{{ __($this->answer()->words->refusal) }}</x-operator::emphasis>
    <x-operator::refused-in-its-words :refused="$this->answer()->refused" />
@elseif ($this->answer()->changesNothing)
    {{-- Said in as many words, and nothing is offered: a yes here would be a
         yes to nothing. --}}
    <x-operator::emphasis>{{ __($this->answer()->words->heading) }}</x-operator::emphasis>
    <x-design::body>{{ __($this->answer()->words->nothing) }}</x-design::body>
@else
    {{-- Worded in the stack's own tense: a preview in the conditional, and
         only a report the stack says it carried out in the past. --}}
    <x-operator::emphasis>{{ __($this->answer()->words->heading) }}</x-operator::emphasis>

    <x-design::body>{{ __($this->answer()->words->files) }}</x-design::body>
    @forelse ($this->answer()->edits as $edit)
        <x-operator::entry>
            <x-operator::emphasis>{{ $edit->path }}</x-operator::emphasis>
            @forelse ($edit->lines as $line)
                <x-operator::note>{{ __($line->said, ['line' => $line->line]) }}</x-operator::note>
            @empty
                <x-operator::note>{{ __($this->answer()->words->noLine) }}</x-operator::note>
            @endforelse
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __($this->answer()->words->noFile) }}</x-operator::note>
    @endforelse

    {{-- The connections are part of what it does, not a footnote to it. --}}
    <x-design::body>{{ __($this->answer()->words->connections) }}</x-design::body>
    @forelse ($this->answer()->connections as $connection)
        <x-operator::note>{{ $connection }}</x-operator::note>
    @empty
        <x-operator::note>{{ __($this->answer()->words->noConnection) }}</x-operator::note>
    @endforelse

    @if ($this->answer()->mayBeAgreedTo)
        <x-operator::offered-action label="{{ __('stacks.reset.put_them_back') }}" tap="agree()" :offer="$this->offered(WhatToChange::BackToItsOwn)" />
    @endif
@endif

@if ($this->wasAgreedTo())
    <x-operator::action label="{{ __('stacks.reset.see_the_settings') }}" :goes="$this->goes()->to(AStacksScreen::Settings)" />
@endif
    <x-operator::quiet-action label="{{ __('health.ask_again') }}" tap="askAgain()" />
</x-operator::content>
@else
    {{-- The stack could not be asked, so there is nothing to agree to and
         nothing is offered. --}}
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :goes="$this->goes()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
