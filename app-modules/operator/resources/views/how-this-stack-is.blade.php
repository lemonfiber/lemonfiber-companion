<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    {{-- The one line, as the core computed it for every surface. A line that
         is not current reads as unknown and says when it was last heard, so a
         stack nobody can vouch for right now is never drawn as healthy. --}}
    <x-design::standing
        :said="__($this->summary()->said)"
        :tone="$this->summary()->tone"
        :note="$this->summary()->ago->said === '' ? '' : __('health.summary.as_of', ['ago' => trans_choice($this->summary()->ago->said, $this->summary()->ago->count)])"
    />

    @if ($this->summary()->met !== '')
        <x-design::notice tone="unknown">
            <x-design::strong>{{ __($this->summary()->met) }}</x-design::strong>
            <x-design::body>{{ __($this->summary()->remedy) }}</x-design::body>
        </x-design::notice>
    @endif

    {{-- The worst thing, in the core's words, so the line says what is wrong
         rather than only grading it. --}}
    @if ($this->summary()->worst !== '')
        <x-design::body>{{ $this->summary()->worst }}</x-design::body>
    @endif

    {{-- How many, counted by cause, and the way into what it counts; offered
         only where it counts something. --}}
    @if ($this->summary()->counted !== '')
        <x-design::link label="{{ trans_choice($this->summary()->counted, $this->summary()->howMany) }}" tap="expand()" />
    @endif

    @if ($this->expanded)
        @forelse ($this->summary()->affected as $item)
            <x-design::card>
                <x-design::note>{{ __($item->severity) }} · {{ $item->check }}</x-design::note>
                <x-design::strong>{{ $item->summary }}</x-design::strong>
                <x-design::body>{{ $item->meaning }}</x-design::body>

                @forelse ($item->remedies as $remedy)
                    <x-design::body>{{ $remedy }}</x-design::body>
                @empty
                    <x-design::body>{{ __('health.nothing_to_try') }}</x-design::body>
                @endforelse

                @forelse ($item->downstream as $also)
                    <x-design::note>{{ __('health.summary.also', ['what' => $also]) }}</x-design::note>
                @empty
                    {{-- Nothing: an item that took nothing else down with it has
                         nothing to add. --}}
                @endforelse
            </x-design::card>
        @empty
            <x-design::body>{{ __('health.no_findings') }}</x-design::body>
        @endforelse
    @endif

    {{-- What stopped moving in the queue, worst first, one row per cause.
         Slow is drawn apart and after, because it needs time rather than a
         fix, and shown as stuck it teaches an operator to read past the list. --}}
    @unless ($this->summary()->stopped === [])
        <x-design::heading>{{ __('health.stopped_heading') }}</x-design::heading>
    @endunless

    @forelse ($this->summary()->stopped as $row)
        <x-operator::stopped-row :row="$row" :trace="$row->follows === '' ? '' : $this->traceOf($row->follows)" />
    @empty
        {{-- Nothing: the one line above already says how the stack stands, and
             a sentence saying the queue is clear is one the stack did not send. --}}
    @endforelse

    @unless ($this->summary()->slow === [])
        <x-design::heading>{{ __('health.slow_heading') }}</x-design::heading>
        <x-design::body>{{ __('health.slow_explained') }}</x-design::body>
    @endunless

    @forelse ($this->summary()->slow as $row)
        <x-operator::stopped-row :row="$row" :trace="$row->follows === '' ? '' : $this->traceOf($row->follows)" />
    @empty
        {{-- Nothing: nothing is only slow. --}}
    @endforelse

    <x-design::note>{{ __($this->cadence()->saidOnTheScreen(), ['count' => $this->cadence()->seconds()]) }}</x-design::note>

    {{-- The families this run has something to say about, as chips: the one
         being read is chosen, and choosing it again is the way back to all of
         them. Only families with findings are offered. --}}
    <x-design::chips>
        @forelse ($this->families() as $family)
            <x-design::chip
                label="{{ __('health.family_and_count', ['family' => __($family->said), 'count' => $family->howMany]) }}"
                tap="read('{{ $family->family }}')"
                :chosen="$family->isOpen"
            />
        @empty
            {{-- Nothing: no family has anything to say only where the run found
                 nothing at all, and the list below says so. --}}
        @endforelse
    </x-design::chips>

    @forelse ($this->findings() as $finding)
        <x-design::card>
            {{-- Which check, and which service it was about: the same check
                 runs against whichever service fills a role. --}}
            <x-design::note>{{ __($finding->about) }}@if ($finding->service !== '') · {{ $finding->service }}@endif</x-design::note>
            <x-design::strong>{{ $finding->title }}</x-design::strong>

            {{-- Whose finding it is, only where it is not the stack's own;
                 unknown is always marked. --}}
            @unless ($finding->from->isTheStacksOwn())
                <x-operator::came-from :from="$finding->from" :said="$finding->from->came->ofACheck()" />
            @endunless

            <x-design::body>{{ __($finding->verdict) }}</x-design::body>
            @if ($finding->cost !== '')
                <x-design::strong>{{ __($finding->cost) }}</x-design::strong>
            @endif

            @if ($finding->because !== '')
                <x-design::note>{{ __('health.because_of', ['title' => $finding->because]) }}</x-design::note>
            @endif

            @if ($finding->explainsItself())
                <x-design::body>{{ $finding->meaning }}</x-design::body>
                @if ($finding->code !== '')
                    <x-design::verbatim>{{ $finding->code }}</x-design::verbatim>
                @endif
                @forelse ($finding->remedies as $remedy)
                    <x-design::body>{{ $remedy->action() }}</x-design::body>
                @empty
                    <x-design::body>{{ __('health.nothing_to_try') }}</x-design::body>
                @endforelse
            @endif

            {{-- The technical detail, available and not leading: last on the
                 card, under the plain explanation and what to try. --}}
            @if ($finding->underneath !== '')
                <x-design::note>{{ __('health.what_it_says_underneath') }}</x-design::note>
                <x-design::verbatim>{{ $finding->underneath }}</x-design::verbatim>
            @endif

            {{-- The logs, from the finding already about this service; a check
                 about the machine itself has no scrollback to read. --}}
            @if ($finding->service !== '')
                <x-design::link
                    label="{{ __('health.what_a_service_said') }}"
                    answers-to="{{ __('health.what_that_service_said', ['service' => $finding->service]) }}"
                    :goes="$this->logsOf($finding->service)"
                />
            @endif
        </x-design::card>
    @empty
        <x-design::body>{{ __('health.no_findings') }}</x-design::body>
    @endforelse

    {{-- Said once, and only where a row is marked, so what an unmarked row
         is never has to be inferred. --}}
    @if ($this->answer()->marksAnOrigin)
        <x-design::note>{{ __('health.origin.legend') }}</x-design::note>
    @endif

    {{-- Under the findings: somebody who has just fixed something scrolls to
         the end of what was wrong, and that is where they ask whether it took. --}}
    <x-design::action label="{{ __('health.ask_again') }}" tap="again()" tone="tonal" />

    {{-- The readings the bar under this screen has no room for, grouped by
         what they are about. Services, repairs and updates are the bar's own. --}}
    <x-design::section :label="__('health.roads.house')">
        <x-design::row :headline="__('household.asked_for')" :goes="$this->goes()->requests()" />
        <x-design::row :headline="__('household.yours')" :goes="$this->goes()->yours()" />
        <x-design::row :headline="__('health.what_stopped')" :goes="$this->goes()->stuck()" />
        <x-design::row :headline="__('health.walkthrough.road_in')" :goes="$this->goes()->ofItself()->walkthrough()" />
    </x-design::section>

    <x-design::section :label="__('health.roads.people')">
        <x-design::row :headline="__('stacks.credentials.road_in')" :goes="$this->goes()->whoGetsIn()->credentials()" />
        <x-design::row :headline="__('stacks.clients.road_in')" :goes="$this->goes()->whoGetsIn()->clients()" />
        <x-design::row :headline="__('stacks.front_door.road_in')" :goes="$this->goes()->whoGetsIn()->frontDoor()" />
        <x-design::row :headline="__('stacks.invitation.road_in')" :goes="$this->goes()->whoGetsIn()->invite()" />
    </x-design::section>

    <x-design::section :label="__('health.roads.machine')">
        <x-design::row :headline="__('health.what_else_is_running')" :goes="$this->goes()->elsewhere()" />
        <x-design::row :headline="__('stacks.already_here.road_in')" :goes="$this->goes()->ofItself()->alreadyHere()" />
        <x-design::row :headline="__('stacks.what_keeps_running')" :goes="$this->goes()->keepsRunning()" />
        <x-design::row :headline="__('config.what_this_is_set_to')" :goes="$this->goes()->settings()" />
        <x-design::row :headline="__('stacks.record.road_in')" :goes="$this->goes()->ofItself()->record()" />
        <x-design::row :headline="__('stacks.origins.road_in')" :goes="$this->goes()->ofItself()->origins()" />
        <x-design::row :headline="__('stacks.outbound.road_in')" :goes="$this->goes()->ofItself()->leaving()" />
        <x-design::row :headline="__('stacks.alerts.road_in')" :goes="$this->goes()->ofItself()->told()" />
        <x-design::row :headline="__('stacks.line.road_in')" :goes="$this->goes()->ofItself()->line()" />
        <x-design::row :headline="__('stacks.keeps.road_in')" :goes="$this->goes()->ofItself()->keeps()" />
        <x-design::row :headline="__('stacks.room.road_in')" :goes="$this->goes()->ofItself()->room()" />
        <x-design::row :headline="__('stacks.itself.road_in')" :goes="$this->goes()->ofItself()->itself()" />
        <x-design::row :headline="__('quality.road_in')" :goes="$this->goes()->ofItself()->quality()" />
        <x-design::row :headline="__('stacks.wiring.road_in')" :goes="$this->goes()->ofItself()->wiring()" />
    </x-design::section>

    <x-design::section :label="__('health.roads.help')">
        <x-design::row :headline="__('stacks.help.road_in')" :goes="$this->goes()->ofItself()->help()" />
        <x-design::row :headline="__('stacks.words.road_in')" :goes="$this->goes()->ofItself()->words()" />
    </x-design::section>
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
