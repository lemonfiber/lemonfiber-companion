@use('Modules\Stacks\Api\AStacksScreen')
<x-wayfinding::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    {{-- The one line, as the core computed it for every surface: the worst
         thing it named, in its words, beside the glyph of how the stack
         stands and that standing in a word, and the line's own sentence where
         it named nothing. A line that is not current reads as unknown and
         says when it was updated, so a stack nobody can vouch for right now
         is never drawn as healthy; a current one says no age, because it is
         being listened to. The line is led by the stack's port, which is
         the only thing that changes weight between healthy and not, so a
         healthy line is drawn as clearly as an unhealthy one. The port is
         read aloud as the standing in a word, as the word under the line
         says it to the eye. --}}
    <native:row class="w-full gap-3 items-center">
        <x-operator::port :tone="$this->summary()->tone" :label="__($this->summary()->word)" />
        <native:column class="flex-1 gap-1">
            <x-design::title>{{ $this->summary()->worst === '' ? __($this->summary()->said) : $this->summary()->worst }}</x-design::title>
            <x-design::note>{{ __($this->summary()->word) }}</x-design::note>
            @if ($this->summary()->ago->said !== '')
                <x-design::note>{{ __('health.summary.as_of', ['ago' => trans_choice($this->summary()->ago->said, $this->summary()->ago->count)]) }}</x-design::note>
            @endif
        </native:column>
    </native:row>

    @if ($this->summary()->met !== '')
        <x-design::notice tone="unknown">
            <x-design::strong>{{ __($this->summary()->met, $this->summary()->filling) }}</x-design::strong>
            <x-design::body>{{ __($this->summary()->remedy, $this->summary()->filling) }}</x-design::body>
        </x-design::notice>
        @if ($this->summary()->inTheAppsSettings)
            <x-design::action label="{{ __('connection.open_settings') }}" tap="openTheAppsSettings()" />
            @if ($this->theSettingsWouldNotOpen)
                <x-design::note>{{ __('connection.settings_would_not_open') }}</x-design::note>
            @endif
        @endif
    @endif

    {{-- What needs the operator, drawn whenever the core counted something:
         how many, counted by cause, as a figure under its label, and each
         affected item on its severity's ground with what it costs, what to try
         and what else is wrong because of it. A healthy stack counts nothing,
         and none of this is drawn. --}}
    @if ($this->summary()->counted !== '')
        <x-operator::heading>{{ __('health.summary.needs_you') }}</x-operator::heading>
        <x-operator::figure :figure="(string) $this->summary()->howMany" :caption="trans_choice($this->summary()->counted, $this->summary()->howMany)" />

        @forelse ($this->summary()->affected as $item)
            <x-design::notice :tone="$item->tone">
                <x-design::note>{{ __($item->severity) }}</x-design::note>
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
            </x-design::notice>
        @empty
            {{-- Nothing: a count the core made names the items it counted, and
                 the figure above says how many. --}}
        @endforelse

        <x-operator::somebody-to-ask :cards="$this->summary()->affected" :goes="$this->goes()->to(AStacksScreen::Help)" />
    @endif

    {{-- What stopped moving in the queue, worst first, one row per cause.
         Slow is drawn apart and after, because it needs time rather than a
         fix, and shown as stuck it teaches an operator to read past the list. --}}
    @unless ($this->summary()->stopped === [])
        <x-operator::heading>{{ __('health.stopped_heading') }}</x-operator::heading>
    @endunless

    @forelse ($this->summary()->stopped as $row)
        <x-operator::stopped-row :row="$row" :trace="$row->follows === '' ? '' : $this->traceOf($row->follows)" tone="attention" />
    @empty
        {{-- Nothing: the one line above already says how the stack stands, and
             a sentence saying the queue is clear is one the stack did not send. --}}
    @endforelse

    @unless ($this->summary()->slow === [])
        <x-operator::heading>{{ __('health.slow_heading') }}</x-operator::heading>
        <x-design::body>{{ __('health.slow_explained') }}</x-design::body>
    @endunless

    @forelse ($this->summary()->slow as $row)
        <x-operator::stopped-row :row="$row" :trace="$row->follows === '' ? '' : $this->traceOf($row->follows)" tone="working" />
    @empty
        {{-- Nothing: nothing is only slow. --}}
    @endforelse

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
            {{-- Which part of the machine it is about, and which service where
                 the report names one by what the stack calls it. The id is a
                 key rather than a name, and is never shown in its place. --}}
            @if ($finding->called !== '')
                <x-operator::heading>{{ __('health.about_the_service', ['about' => __($finding->about), 'service' => $finding->called]) }}</x-operator::heading>
            @else
                <x-operator::heading>{{ __($finding->about) }}</x-operator::heading>
            @endif
            <x-design::strong>{{ $finding->title }}</x-design::strong>

            {{-- Whose finding it is, only where it is not the stack's own;
                 unknown is always marked. --}}
            @unless ($finding->from->isTheStacksOwn())
                <x-operator::came-from :from="$finding->from" :said="$finding->from->came->ofACheck()" />
            @endunless

            <x-design::strong>{{ __($finding->verdict) }}</x-design::strong>

            @if ($finding->because !== '')
                <x-design::note>{{ __('health.because_of', ['title' => $finding->because]) }}</x-design::note>
            @endif

            @if ($finding->explainsItself())
                <x-design::body>{{ $finding->meaning }}</x-design::body>
                @forelse ($finding->remedies as $remedy)
                    <x-design::body>{{ $remedy->action() }}</x-design::body>
                @empty
                    <x-design::body>{{ __('health.nothing_to_try') }}</x-design::body>
                @endforelse
            @endif

            {{-- The technical detail, available and not leading: last on the
                 card, under the plain explanation and what to try. A finding
                 about the machine says its code here, having no logs to carry
                 it to. --}}
            @if ($finding->underneath !== '' || $finding->codeAtTheFoot() !== '')
                <x-design::note>{{ __('health.what_it_says_underneath') }}</x-design::note>
                @if ($finding->codeAtTheFoot() !== '')
                    <x-operator::stamp>{{ $finding->codeAtTheFoot() }}</x-operator::stamp>
                @endif
                @if ($finding->underneath !== '')
                    <x-design::verbatim>{{ $finding->underneath }}</x-design::verbatim>
                @endif
            @endif

            {{-- The logs, from the finding already about this service, with
                 its code carried there and said above the lines; a check
                 about the machine itself has no scrollback to read. --}}
            @if ($finding->service !== '')
                <x-design::link
                    label="{{ __('health.what_a_service_said') }}"
                    answers-to="{{ $finding->called !== '' ? __('health.what_that_service_said', ['service' => $finding->called]) : __('health.what_a_service_said') }}"
                    :goes="$this->logsOf($finding->service)"
                    :carries="$finding->carriedToTheLogs()"
                />
            @endif
        </x-design::card>
    @empty
        <x-design::body>{{ __('health.no_findings') }}</x-design::body>
    @endforelse

    <x-operator::somebody-to-ask :cards="$this->findings()" :goes="$this->goes()->to(AStacksScreen::Help)" />

    {{-- Said once, and only where a row is marked, so what an unmarked row
         is never has to be inferred. --}}
    @if ($this->answer()->marksAnOrigin)
        <x-design::note>{{ __('health.origin.legend') }}</x-design::note>
    @endif

    {{-- Under the findings: somebody who has just fixed something scrolls to
         the end of what was wrong, and that is where they ask whether it took. --}}
    <x-operator::quiet-action label="{{ __('health.ask_again') }}" tap="askAgain()" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :goes="$this->goes()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
