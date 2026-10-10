@use('Modules\Kernel\Api\HandingOver')
@use('Modules\Stacks\Api\AStacksScreen')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
@if ($this->asking !== null)
    {{-- The question, naming the command, before anything is sent. What the
         act does to the machine is said beside it, because taking a command
         back is a guarantee the household stops having. --}}
    <x-design::title>{{ __($this->asking->doing()->askedOnTheScreen(), ['name' => $this->asking->named()]) }}</x-design::title>
    <x-design::body>{{ __($this->asking->doing()->meansOnTheScreen()) }}</x-design::body>

    <x-operator::offered-action label="{{ __('health.go_ahead') }}" tap="agree()" :offer="$this->offered($this->asking->doing())" />
    <x-design::action label="{{ __('health.never_mind') }}" tap="neverMind()" tone="tonal" />
@else
    <x-design::title>{{ __('stacks.what_keeps_running') }}</x-design::title>

    @if ($this->handedOver !== null)
        {{-- What came of the last one, as the stack said it. The heading names
             what was asked for and never says it worked: whether the command
             runs is the standing below it. --}}
        <x-design::card>
            <x-design::heading>{{ __($this->handedOver->headingSaid, ['name' => $this->handedOver->name]) }}</x-design::heading>

            @if ($this->handedOver->rehearsed)
                {{-- Said first, because everything under it is what would have
                     happened and none of it did. --}}
                <x-design::strong>{{ __('stacks.handed_over.rehearsed') }}</x-design::strong>
            @endif

            @if ($this->handedOver->refused !== '')
                {{-- The stack's own words for why it would not. --}}
                <x-design::body>{{ $this->handedOver->refused }}</x-design::body>
            @endif

            @if ($this->handedOver->metSaid !== '')
                <x-design::body>{{ __($this->handedOver->metSaid) }}</x-design::body>
            @endif

            @if ($this->handedOver->standingSaid !== '')
                <x-design::body>{{ __('stacks.handed_over.stands', ['standing' => __($this->handedOver->standingSaid)]) }}</x-design::body>
            @endif

            @if ($this->handedOver->startedSaid !== '')
                <x-design::note>{{ __($this->handedOver->startedSaid) }}</x-design::note>
            @endif

            @if ($this->handedOver->writesToSaid !== '')
                {{-- Where its words go. A hosted command with nowhere visible to
                     speak is one nobody can tell from a failure. --}}
                <x-design::note>{{ __($this->handedOver->writesToSaid, ['output' => $this->handedOver->writesTo]) }}</x-design::note>
            @endif

            @if ($this->handedOver->touchedNothingSaid !== '')
                {{-- Every file, each with what happened to it. --}}
                @forelse ($this->handedOver->touched as $file)
                    <x-design::note>{{ __($this->handedOver->touchedSaid, ['file' => $file]) }}</x-design::note>
                @empty
                    <x-design::note>{{ __($this->handedOver->touchedNothingSaid) }}</x-design::note>
                @endforelse
            @endif
        </x-design::card>
    @endif

    {{-- What keeps them running, said before the list. It is a fact about the
         machine rather than about any card, and an operator reading cards
         without it cannot tell a launch agent from a login item they set up
         themselves years ago. --}}
    <x-design::body>{{ __($this->answer()->keptBySaid) }}</x-design::body>

    @if ($this->answer()->instead !== '')
        {{-- The sentence that makes *not available here* read differently from
             *off*. Drawn instead of a control, never beside one: there is
             nothing on this platform to switch, and a disabled switch is an
             invitation to go looking for the reason it is disabled. --}}
        <x-design::notice tone="unknown">
            <x-design::body>{{ $this->answer()->instead }}</x-design::body>
        </x-design::notice>
    @endif

    @if ($this->answer()->missing > 0)
        {{-- How many are installed and not running, said before the cards for
             the stuck screen's reason: somebody who opened this the morning
             after a reboot should not have to count. --}}
        <x-design::notice tone="trouble">
            <x-design::strong>{{ trans_choice('stacks.did_not_come_back', $this->answer()->missing) }}</x-design::strong>
        </x-design::notice>
    @endif

    @forelse ($this->answer()->commands as $command)
        <x-design::card>
            <x-design::strong>{{ $command->name }}</x-design::strong>

            {{-- Where it stands and what it guarantees. Both, because either
                 alone leaves the decision unmade: a standing with no promise
                 asks somebody whether a name they do not recognise should
                 survive a reboot, and a promise with no standing says what it
                 would do without saying whether it is doing it. --}}
            <x-design::body>{{ __($command->standingSaid) }}</x-design::body>
            <x-design::note>{{ $command->guarantees }}</x-design::note>

            {{-- How it is typed. The card is only actionable with it: an
                 operator who has just read that something did not come back
                 goes to the machine and runs it, and a card carrying the name
                 alone has shown them a label they cannot search for. --}}
            <x-design::verbatim>{{ $command->command }}</x-design::verbatim>

            @if ($command->missing !== '')
                {{-- Only an orphan has one. The program rather than the
                     service, because that is the difference between *this is
                     not running* and *the file it runs is not there any more* —
                     the same card, and different work. --}}
                <x-design::note>{{ __('stacks.missing_program', ['program' => $command->missing]) }}</x-design::note>
            @endif

            @if ($this->answer()->handsOver)
                {{-- Both acts on every card, each naming its command, and each
                     only a question until the operator says yes. A machine
                     with no manager draws neither: the stack has said it cannot
                     do this there. --}}
                <x-operator::offered-action
                    label="{{ __('stacks.handing_over.install', ['name' => $command->name]) }}"
                    tap="wouldInstall('{{ $command->name }}')"
                    :offer="$this->offered(HandingOver::Install)"
                    tone="tonal"
                />
                <x-operator::offered-action
                    label="{{ __('stacks.handing_over.remove', ['name' => $command->name]) }}"
                    tap="wouldRemove('{{ $command->name }}')"
                    :offer="$this->offered(HandingOver::Remove)"
                    tone="tonal"
                />
            @endif
        </x-design::card>
    @empty
        {{-- Not the same screen as a machine that could not be asked, and not
             the same as one this product cannot configure — that one carries
             the sentence above and this one does not. --}}
        <x-design::card>
            <x-design::strong>{{ __('stacks.keeps_nothing_running') }}</x-design::strong>
            <x-design::body>{{ __('stacks.keeps_nothing_running_action') }}</x-design::body>
        </x-design::card>
    @endforelse

    {{-- A screen an operator cannot ask again is a screen that relies on being
         left and returned to. On both arms rather than the obstacle one, for
         the stuck screen's reason: somebody who has just started something at
         the machine is looking at a screen they want to ask again. --}}
    <x-design::action label="{{ __('health.ask_again') }}" tap="askAgain()" tone="tonal" />

    {{-- A guard held by a screen of this app, apart from everything above:
         it outlives nothing, and it is never one of the hosted rows. --}}
    <x-design::note>{{ __('stacks.guard.apart') }}</x-design::note>
    <x-design::action label="{{ __('stacks.guard.road_in') }}" :goes="$this->goes()->to(AStacksScreen::Guard)" tone="tonal" />
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
