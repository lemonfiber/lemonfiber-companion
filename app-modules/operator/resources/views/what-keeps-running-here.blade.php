<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<native:column class="w-full gap-4 px-6 py-4">
    {{-- What keeps them running, said before the list. It is a fact about the
         machine rather than about any row, and an operator reading rows without
         it cannot tell a launch agent from a login item they set up themselves
         years ago. --}}
    <x-operator::emphasis>{{ __($this->answer()->keptBySaid) }}</x-operator::emphasis>

    @if ($this->answer()->instead !== '')
        {{-- The sentence that makes *not available here* read differently from
             *off*. Drawn instead of a control, never beside one: there is
             nothing on this platform to switch, and a disabled switch is an
             invitation to go looking for the reason it is disabled. --}}
        <x-operator::note>{{ $this->answer()->instead }}</x-operator::note>
    @endif

    @if ($this->answer()->missing > 0)
        {{-- How many are installed and not running, said before the rows for
             the stuck screen's reason: somebody who opened this the morning
             after a reboot should not have to count. --}}
        <x-operator::emphasis>
            {{ trans_choice('stacks.did_not_come_back', $this->answer()->missing) }}
        </x-operator::emphasis>
    @endif

    @forelse ($this->answer()->commands as $command)
        <x-operator::entry>
            <x-operator::emphasis>{{ $command->name }}</x-operator::emphasis>

            {{-- Where it stands and what it guarantees. Both, because either
                 alone leaves the decision unmade: a standing with no promise
                 asks somebody whether a name they do not recognise should
                 survive a reboot, and a promise with no standing says what it
                 would do without saying whether it is doing it. --}}
            <native:text>{{ __($command->standingSaid) }}</native:text>
            <x-operator::note>{{ $command->guarantees }}</x-operator::note>

            {{-- How it is typed. The row is only actionable with it: an
                 operator who has just read that something did not come back
                 goes to the machine and runs it, and a row carrying the name
                 alone has shown them a label they cannot search for. --}}
            <x-operator::note>{{ $command->command }}</x-operator::note>

            @if ($command->missing !== '')
                {{-- Only an orphan has one. The program rather than the
                     service, because that is the difference between *this is
                     not running* and *the file it runs is not there any more* —
                     the same row, and different work. --}}
                <x-operator::note>
                    {{ __('stacks.missing_program', ['program' => $command->missing]) }}
                </x-operator::note>
            @endif
        </x-operator::entry>
    @empty
        {{-- Not the same screen as a machine that could not be asked, and not
             the same as one this product cannot configure — that one carries
             the sentence above and this one does not. --}}
        <x-operator::emphasis>{{ __('stacks.keeps_nothing_running') }}</x-operator::emphasis>
        <native:text>{{ __('stacks.keeps_nothing_running_action') }}</native:text>
    @endforelse

    {{-- A screen an operator cannot ask again is a screen that relies on being
         left and returned to. On both arms rather than the obstacle one, for
         the stuck screen's reason: somebody who has just started something at
         the machine is looking at a screen they want to ask again. --}}
    <x-operator::action label="{{ __('health.ask_again') }}" tap="again()" />
</native:column>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
