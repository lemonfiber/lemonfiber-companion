@use('Modules\Kernel\Api\ChangingTheLine')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-operator::the-line :line="$this->answer()" />

    <x-operator::heading>{{ __('stacks.line_change.title') }}</x-operator::heading>
    <x-design::body>{{ __('stacks.line_change.left_blank') }}</x-design::body>

    {{-- Each limit beside the limit in force, whose sentence carries the
         figure a share is a share of. --}}
    <x-design::card>
        <x-design::row :headline="__('stacks.line.down', ['says' => $this->answer()->downSays])" />
        <native:outlined-text-input native:model="down" label="{{ __('stacks.line_change.down') }}" supporting="{{ __('stacks.line_change.down_is') }}" />
        <x-design::row :headline="__('stacks.line.up', ['says' => $this->answer()->upSays])" />
        <native:outlined-text-input native:model="up" label="{{ __('stacks.line_change.up') }}" supporting="{{ __('stacks.line_change.up_is') }}" />
    </x-design::card>

    <x-design::card>
        @if ($this->answer()->measured !== null)
            <x-design::row
                :headline="__('stacks.line.carries', [
                    'down' => $this->answer()->measured->downFigure,
                    'down_unit' => __($this->answer()->measured->downUnit),
                    'up' => $this->answer()->measured->upFigure,
                    'up_unit' => __($this->answer()->measured->upUnit),
                ])"
                :supporting="__($this->answer()->measured->measuredSaid)"
            />
        @else
            <x-design::row :headline="__('stacks.line.unmeasured')" />
        @endif
        <native:outlined-text-input native:model="line" label="{{ __('stacks.line_change.line') }}" supporting="{{ __('stacks.line_change.line_is') }}" />
        @if ($this->answer()->hours !== null)
            <x-design::row :headline="__('stacks.line.up_from', ['from' => $this->answer()->hours->from, 'to' => $this->answer()->hours->to])" />
        @else
            <x-design::row :headline="__('stacks.line.no_hours')" />
        @endif
        <native:outlined-text-input native:model="active" label="{{ __('stacks.line_change.active') }}" supporting="{{ __('stacks.line_change.active_is') }}" />
    </x-design::card>

    {{-- What reaching the cap does is chosen with the cap, in advance. --}}
    <x-design::card>
        <native:outlined-text-input native:model="cap" label="{{ __('stacks.line_change.cap') }}" supporting="{{ __('stacks.line_change.cap_is') }}" />
        <x-design::note>{{ __('stacks.line_change.exceeded_is', ['choice' => __($this->exceededChoice()->said)]) }}</x-design::note>
        <x-design::chips>
            @forelse ($this->exceededChoice()->offered as $choice)
                <x-design::chip :label="__($choice->said)" :tap="$this->tapping('exceededIs', $choice->word)" :chosen="$choice->chosen" />
            @empty
                {{-- Nothing: every way is always offered. --}}
            @endforelse
        </x-design::chips>
    </x-design::card>

    <x-design::card>
        <x-design::strong>{{ __('stacks.line_change.lift.heading') }}</x-design::strong>
        <x-design::chips>
            @forelse ($this->liftChoice() as $choice)
                <x-design::chip :label="__($choice->said)" :tap="$this->tapping('liftFor', $choice->word)" :chosen="$choice->chosen" />
            @empty
                {{-- Nothing: every length is always offered. --}}
            @endforelse
        </x-design::chips>
        <native:outlined-text-input native:model="lift" label="{{ __('stacks.line_change.lift.minutes') }}" supporting="{{ __('stacks.line_change.lift.minutes_are') }}" />
    </x-design::card>

    @if ($this->howItIsGoing()->notAskable !== '')
        <x-design::notice>
            <x-design::strong>{{ __($this->howItIsGoing()->notAskable) }}</x-design::strong>
        </x-design::notice>
    @endif

    @if ($this->howItIsGoing()->went->cameBack())
        @if ($this->howItIsGoing()->isWorking)
            <x-design::standing :said="__('stacks.line_change.working')" tone="working" />
        @elseif ($this->howItIsGoing()->hasEnded)
            <x-design::notice tone="unknown">
                <x-design::strong>{{ __('stacks.line_change.no_outcome') }}</x-design::strong>
            </x-design::notice>
            <x-design::action :label="__('stacks.line_change.start_again')" tap="startAgain()" />
        @elseif ($this->howItIsGoing()->refused !== null)
            {{-- The stack's own sentence and remedy: a share of a line nobody
                 measured, hours with no time zone, a value it cannot read. --}}
            <x-design::notice>
                <x-design::strong>{{ __('stacks.line_change.refused') }}</x-design::strong>
                <x-operator::refused-in-its-words :refused="$this->howItIsGoing()->refused" />
            </x-design::notice>
            <x-design::action :label="__('stacks.line_change.start_again')" tap="startAgain()" />
        @elseif ($this->howItIsGoing()->change !== null)
            {{-- What the run was, said before anything else: a rehearsal is
                 never drawn as limits in force. --}}
            <x-design::notice>
                <x-design::strong>{{ __($this->howItIsGoing()->change->didSaid) }}</x-design::strong>
            </x-design::notice>

            <x-operator::the-line :line="$this->howItIsGoing()->change->line" />

            <x-design::section :label="__('stacks.line_change.clients')">
                @forelse ($this->howItIsGoing()->change->clients as $client)
                    <x-design::row :headline="$client->name" :supporting="$client->pullingSaid === '' ? '' : __($client->pullingSaid)" />
                    @if ($client->answered)
                        @forelse ($client->directions as $held)
                            <x-design::row
                                :headline="__($held->said, ['verdict' => __($held->verdictSaid)])"
                                :supporting="$held->miss ? __('stacks.line_change.missed') : ''"
                            />
                            @forelse ($held->rates as $rate)
                                <x-design::note>{{ __($rate->said, ['figure' => $rate->figure, 'unit' => __($rate->unit)]) }}</x-design::note>
                            @empty
                                {{-- Nothing: the client gave no figure for this direction. --}}
                            @endforelse
                        @empty
                            {{-- Unreachable: a client that answered answered for both directions. --}}
                        @endforelse
                    @else
                        <x-design::note>{{ __('stacks.line_change.silent', ['said' => $client->said]) }}</x-design::note>
                    @endif
                @empty
                    <x-design::row :headline="__('stacks.line_change.no_clients')" />
                @endforelse
            </x-design::section>

            @if ($this->howItIsGoing()->change->lift->said !== '')
                <x-design::row
                    :headline="trans_choice($this->howItIsGoing()->change->lift->said, $this->howItIsGoing()->change->lift->count)"
                    :supporting="$this->howItIsGoing()->change->lift->says"
                />
            @endif

            @if ($this->howItIsGoing()->change->mayBeApplied)
                <x-operator::offered-action :label="__('stacks.line_change.apply')" tap="apply()" :offer="$this->offered(ChangingTheLine::Change)" />
            @endif
        @endif

        @unless ($this->howItIsGoing()->isWorking)
            <x-operator::offered-action :label="__('stacks.line_change.rehearse')" tap="rehearse()" tone="tonal" :offer="$this->offered(ChangingTheLine::Change)" />
        @endunless

        <x-operator::quiet-action :label="__('health.ask_again')" tap="askAgain()" />
    @else
        <x-operator::what-stood-in-the-way
            :settings-would-not-open="$this->theSettingsWouldNotOpen"
            :went="$this->howItIsGoing()->went"
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
