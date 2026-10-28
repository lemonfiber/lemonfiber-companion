@use('Modules\Kernel\Api\ChoosingWhatIsAllowed')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    {{-- The core's own sentences, in its own order and wording: the
         household's rules are the core's to state. --}}
    <x-design::card>
        @forelse ($this->answer()->sentences as $sentence)
            <x-design::body>{{ $sentence }}</x-design::body>
        @empty
            {{-- Said in as many words: a blank card is what a stack that would
                 not say looks like, and this arm is only reached where it
                 answered. --}}
            <x-design::strong>{{ __('household.nothing_owed') }}</x-design::strong>
            <x-design::body>{{ __('household.nothing_owed_action') }}</x-design::body>
        @endforelse
    </x-design::card>

    <x-operator::heading>{{ __('stacks.allowance_change.title') }}</x-operator::heading>
    <x-design::body>{{ __('stacks.allowance_change.left_as_it_is') }}</x-design::body>

    <x-design::card>
        <x-design::strong>{{ __('stacks.allowance_change.who') }}</x-design::strong>
        <x-design::chips>
            <x-design::chip :label="__('stacks.allowance_change.everybody')" tap="forEverybody()" :chosen="! $this->choosingAMember" />
            <x-design::chip :label="__('stacks.allowance_change.one_member')" tap="forAMember()" :chosen="$this->choosingAMember" />
        </x-design::chips>
        @if ($this->choosingAMember)
            <x-design::chips>
                @forelse ($this->whoMayBeChosen()->members as $name)
                    <x-design::chip :label="$name" :tap="$this->tapping('forSomebody', $name)" :chosen="$this->who === $name" />
                @empty
                    {{-- Nothing: the stack listed nobody, which the note below says where it could not read them. --}}
                @endforelse
            </x-design::chips>
            @unless ($this->whoMayBeChosen()->membersRead)
                <x-design::note>{{ __('stacks.allowance_change.members_unread') }}</x-design::note>
            @endunless
        @endif
    </x-design::card>

    <x-design::card>
        <x-design::strong>{{ __('stacks.allowance_change.policy_is', ['choice' => __($this->policyChoice()->said)]) }}</x-design::strong>
        <x-design::chips>
            @forelse ($this->policyChoice()->offered as $choice)
                <x-design::chip :label="__($choice->said)" :tap="$this->tapping('policyIs', $choice->word)" :chosen="$choice->chosen" />
            @empty
                {{-- Nothing: every policy is always offered. --}}
            @endforelse
        </x-design::chips>
    </x-design::card>

    <x-design::card>
        <x-design::strong>{{ __('stacks.allowance_change.limit.heading') }}</x-design::strong>
        <native:outlined-text-input native:model="requests" label="{{ __('stacks.allowance_change.limit.requests') }}" supporting="{{ __('stacks.allowance_change.limit.requests_are') }}" />
        <native:outlined-text-input native:model="days" label="{{ __('stacks.allowance_change.limit.days') }}" supporting="{{ __('stacks.allowance_change.limit.days_are') }}" />
    </x-design::card>

    @if ($this->howItIsGoing()->notAskable !== '')
        <x-design::notice>
            <x-design::strong>{{ __($this->howItIsGoing()->notAskable) }}</x-design::strong>
        </x-design::notice>
    @endif

    @if ($this->howItIsGoing()->stands->went->cameBack())
        @if ($this->howItIsGoing()->stands->isWorking())
            <x-design::standing :said="__('stacks.allowance_change.working')" tone="working" />
        @elseif ($this->howItIsGoing()->stands->hasEnded())
            <x-design::notice tone="unknown">
                <x-design::strong>{{ __('stacks.allowance_change.no_outcome') }}</x-design::strong>
            </x-design::notice>
            <x-design::action :label="__('stacks.allowance_change.start_again')" tap="startAgain()" />
        @elseif ($this->howItIsGoing()->refused !== null)
            <x-design::notice>
                <x-design::strong>{{ __('stacks.allowance_change.refused') }}</x-design::strong>
                <x-operator::refused-in-its-words :refused="$this->howItIsGoing()->refused" />
            </x-design::notice>
            <x-design::action :label="__('stacks.allowance_change.start_again')" tap="startAgain()" />
        @elseif ($this->howItIsGoing()->change !== null)
            {{-- What the run was, said before the stack's sentences: a
                 rehearsal is never drawn as a change made. --}}
            <x-design::notice>
                <x-design::strong>{{ __($this->howItIsGoing()->change->didSaid) }}</x-design::strong>
                @forelse ($this->howItIsGoing()->change->sentences as $sentence)
                    <x-design::body>{{ $sentence }}</x-design::body>
                @empty
                    {{-- Nothing: the stack always says what a change comes to. --}}
                @endforelse
            </x-design::notice>

            @if ($this->howItIsGoing()->change->mayBeApplied)
                <x-operator::offered-action :label="__('stacks.allowance_change.apply')" tap="apply()" :offer="$this->offered(ChoosingWhatIsAllowed::Allow)" />
            @endif
        @endif

        @unless ($this->howItIsGoing()->stands->isWorking())
            <x-operator::offered-action :label="__('stacks.allowance_change.rehearse')" tap="rehearse()" tone="tonal" :offer="$this->offered(ChoosingWhatIsAllowed::Allow)" />
        @endunless

        <x-operator::quiet-action :label="__('health.ask_again')" tap="askAgain()" />
    @else
        <x-operator::what-stood-in-the-way
            :settings-would-not-open="$this->theSettingsWouldNotOpen"
            :went="$this->howItIsGoing()->stands->went"
            :goes="$this->goes()"
        />
    @endif
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :goes="$this->goes()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
