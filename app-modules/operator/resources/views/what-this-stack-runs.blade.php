@use('Modules\Stacks\Api\AStacksScreen')
<x-wayfinding::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    @if ($this->answer()->waitsForTheStack)
        {{-- What the phone kept, said for what it is: how long ago it was
             read, and above it what stopped the reading asked for now. Asking
             again is the column's own, below. --}}
        @if (! $this->answer()->askedNow->cameBack())
            <x-operator::what-stood-in-the-way
                :settings-would-not-open="$this->theSettingsWouldNotOpen"
                :went="$this->answer()->askedNow"
                ask-again=""
                :goes="$this->goes()"
            />
        @endif
        <x-operator::stamp>{{ __('health.summary.as_of', ['ago' => trans_choice($this->answer()->readAgo->said, $this->answer()->readAgo->count)]) }}</x-operator::stamp>
    @endif

    {{-- What it all amounts to, as the stack judged it — said before the
         rows, so an operator who opened this because a film would not play
         reads the answer before the list. --}}
    <x-design::title>{{ __($this->answer()->overall) }}</x-design::title>

    {{-- The forms asked for, before the services they expand to: a stack
         running part of itself is the operator's intent, and this says which
         part. --}}
    @if ($this->answer()->active !== [])
        <x-design::body>{{ __('health.forms_running', ['forms' => implode(', ', $this->answer()->active)]) }}</x-design::body>
    @else
        <x-design::body>{{ __('health.no_form_running') }}</x-design::body>
    @endif

    {{-- A port per service, not a rack. The verbs live behind it, on the
         screen about that one service — a list is read and a verb is chosen,
         and the two acts do not want the same frame. What the row carries is
         what somebody scanning the list is looking for: which thing, how it
         stands in its port, and every form that brought it, so a service is
         never shown without why it is there. Only a port that wants the
         operator carries weight. --}}
    <x-operator::heading>{{ __('health.services_heading') }}</x-operator::heading>
    @forelse ($this->answer()->installed() as $service)
        <x-operator::port-row
            :tone="$service->tone"
            :name="$service->name"
            :said="__($service->runsSaid) . ' · ' . ($service->runsFor === [] ? __('health.runs_for_no_form') : __('health.runs_for', ['forms' => implode(', ', $service->runsFor)]))"
            :figure="$service->exited === '' ? '' : __('health.exit_figure', ['code' => $service->exited])"
            :goes="$this->goes()->doingWith($service->id)"
            :answers-to="__('health.open_service', ['name' => $service->name])"
        />
    @empty
        {{-- Not the same screen as a stack that could not be asked. Nothing
             running is the state the operator came here to change, and saying
             so is what tells it apart from the obstacle branch. --}}
        <x-design::body>{{ __('health.nothing_is_running') }}</x-design::body>
    @endforelse

    {{-- Filtered, not failed: each service the forms asked for and the
         stack left out, with what it would need. It has no standing of its
         own, so its row has no port. --}}
    <x-operator::heading>{{ __('health.left_out_heading') }}</x-operator::heading>
    @forelse ($this->answer()->leftOut as $left)
        <x-operator::port-row
            :name="$left->name"
            :said="__('health.left_out_by', ['forms' => implode(', ', $left->askedBy), 'needs' => __($left->needsSaid)])"
        />
    @empty
        <x-design::body>{{ __('health.nothing_left_out') }}</x-design::body>
    @endforelse

    {{-- The other granularity. The forms come from the stack's own list
         of the forms it declares rather than from the rows — a row's
         profile is not a form — so the form an operator opened this screen
         to start, the one with nothing running in it, is here. They are
         read on the frame after the listing, and until then the heading is
         not drawn. A form has no standing of its own here, so its row has no
         port. --}}
    @if ($this->forms()?->went->cameBack())
        <x-operator::heading>{{ __('health.by_form') }}</x-operator::heading>
        @forelse ($this->forms()->names as $form)
            <x-operator::port-row
                :name="$form"
                :goes="$this->goes()->doingWithTheForm($form)"
                :answers-to="__('health.open_form', ['name' => $form])"
            />
        @empty
            {{-- A stack that declares no forms at all, which is not the same as
                 one whose forms are all stopped — and it is not something *ask
                 again* fixes. --}}
            <x-design::body>{{ __('health.no_forms_at_all') }}</x-design::body>
        @endforelse
    @endif

    {{-- What the stack could run and no running form asked for, folded away
         at the foot: nothing is wrong with a service nobody wanted, so it
         carries the quiet port and no warning. Each opens to the screen
         about it, which says what starting it would do. --}}
    @unless ($this->answer()->notInstalled() === [])
        <x-operator::port-row
            :name="__('health.not_installed', ['count' => count($this->answer()->notInstalled())])"
            tone="quiet"
            tap="showWhatIsNotInstalled()"
        />
        @if ($this->showsWhatIsNotInstalled)
            @forelse ($this->answer()->notInstalled() as $service)
                <x-operator::port-row
                    :tone="$service->tone"
                    :name="$service->name"
                    :said="__($service->mattersSaid)"
                    :goes="$this->goes()->doingWith($service->id)"
                    :answers-to="__('health.open_service', ['name' => $service->name])"
                />
            @empty
                {{-- Unreachable while the guard above holds: the fold is
                     drawn only where something is not installed. --}}
            @endforelse
        @endif
    @endunless

    {{-- Words rather than a bar, because it is not the thing this frame wants
         anybody to do. The rows are. --}}
    <x-operator::quiet-action label="{{ __('health.ask_again') }}" tap="askAgain()" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :goes="$this->goes()"
    />
@endif

@if ($this->waitsForTheNextFrame())
    <x-design::the-next-frame />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
