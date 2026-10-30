@use('Modules\Stacks\Api\AStacksScreen')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-operator::heading>{{ __('stacks.guard.heading') }}</x-operator::heading>

    {{-- Said before the guard starts and for as long as it runs: it lives
         while this screen asks, and it is not hosted. The way to one that
         outlives the screen is the hosting screen, named here and kept apart. --}}
    <x-design::body>{{ __('stacks.guard.lives_while_asked') }}</x-design::body>
    <x-operator::note>{{ __('stacks.guard.not_hosted') }}</x-operator::note>
    <x-operator::quiet-action :label="__('stacks.guard.to_host_one')" :goes="$this->goes()->to(AStacksScreen::Hosting)" />

@if ($this->asking !== null)
    {{-- Asked before it starts, naming the forms it would stop. --}}
    <x-operator::emphasis>{{ __('stacks.guard.about_to', ['forms' => $this->choosing()->named]) }}</x-operator::emphasis>
    <x-design::body>{{ __('stacks.guard.would_do') }}</x-design::body>
    <x-operator::note>{{ __('stacks.guard.not_said_before') }}</x-operator::note>

    <x-operator::offered-action :label="__('health.go_ahead')" tap="agree()" :offer="$this->offered($this->asking)" />
    <x-operator::quiet-action :label="__('health.never_mind')" tap="neverMind()" />
@endif

@if ($this->asking === null && $this->lastGuard()->stands->wasAsked())
    @if ($this->lastGuard()->stands->went->cameBack())
        @if ($this->lastGuard()->guarding !== null)
            <x-operator::emphasis>{{ __('stacks.guard.guarding', ['forms' => $this->lastGuard()->guarding]) }}</x-operator::emphasis>
        @endif

        @if ($this->lastGuard()->ended !== null)
            {{-- How it ended, each of the four in words of its own. --}}
            <x-operator::emphasis>{{ __($this->lastGuard()->ended->said) }}</x-operator::emphasis>

            @if ($this->lastGuard()->ended->why !== null && $this->lastGuard()->ended->why !== '')
                {{-- The stack's own words for why. --}}
                <x-design::body>{{ $this->lastGuard()->ended->why }}</x-design::body>
            @endif

            @if ($this->lastGuard()->ended->stopped !== null)
                {{-- Whether stopping the forms worked, which a guard that could
                     not stop them never reads as having done. --}}
                <x-design::body>{{ __($this->lastGuard()->ended->stopped->said) }}</x-design::body>
                @forelse ($this->lastGuard()->ended->stopped->forms as $form)
                    <x-operator::note>{{ $form }}</x-operator::note>
                @empty
                    <x-operator::note>{{ __('stacks.guard.named_no_forms') }}</x-operator::note>
                @endforelse
            @endif

            <x-operator::action :label="__('stacks.guard.start_over')" tap="startOver()" />
        @endif
    @else
        <x-operator::what-stood-in-the-way
            :settings-would-not-open="$this->theSettingsWouldNotOpen"
            :went="$this->lastGuard()->stands->went"
            :goes="$this->goes()"
        />
    @endif

    <x-operator::quiet-action :label="__('health.ask_again')" tap="askAgain()" />
@endif

@if ($this->asking === null && ! $this->lastGuard()->stands->wasAsked())
    {{-- Before any guard: what one does, in this app's words, and the forms
         the stack declares, each named or left out on a tap. --}}
    <x-design::body>{{ __('stacks.guard.would_do') }}</x-design::body>
    <x-operator::note>{{ __('stacks.guard.not_said_before') }}</x-operator::note>

    <x-operator::emphasis>{{ __('stacks.guard.which_forms') }}</x-operator::emphasis>

    @forelse ($this->choosing()->forms as $form)
        @if ($form->isNamed)
            <x-operator::action
                :label="__('stacks.guard.named', ['form' => $form->name])"
                :answers-to="__('stacks.guard.leave_out', ['form' => $form->name])"
                :tap="$this->tapping('choose', $form->name)"
            />
        @endif
        @if (! $form->isNamed)
            <x-operator::quiet-action :label="__('stacks.guard.name', ['form' => $form->name])" :tap="$this->tapping('choose', $form->name)" />
        @endif
    @empty
        <x-operator::note>{{ __('stacks.guard.no_forms') }}</x-operator::note>
    @endforelse

    @if ($this->choosing()->canStart)
        <x-operator::offered-action :label="__('stacks.guard.start')" tap="wouldGuard()" :offer="$this->offered($this->theGuardNamed())" />
    @endif

    <x-operator::quiet-action :label="__('health.ask_again')" tap="askAgain()" />
@endif
</x-operator::content>
@else
    {{-- The forms could not be listed, so no guard is offered: a stack that
         cannot say what it declares is not one to name forms for. --}}
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :goes="$this->goes()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
