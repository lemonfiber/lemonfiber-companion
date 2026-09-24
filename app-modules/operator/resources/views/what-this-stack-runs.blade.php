<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    {{-- What it all amounts to, as the stack judged it — said before the
         rows, so an operator who opened this because a film would not play
         reads the answer before the list. --}}
    <x-operator::emphasis>{{ __($this->answer()->overall) }}</x-operator::emphasis>

    @if ($this->answer()->isSettling)
        {{-- Something here becomes something else on its own, and
             this says how often the screen looks. A screen that refreshes
             silently is one an operator cannot reason about. --}}
        <x-operator::note>{{ __($this->cadence()->saidOnTheScreen()) }}</x-operator::note>
    @endif

    @forelse ($this->answer()->services as $service)
        {{-- A row, not a rack. The verbs live behind it, on the screen about
             that one service — a list is read and a verb is chosen, and the
             two acts do not want the same frame. What the row carries is what
             somebody scanning the list is looking for: which thing, and
             whether it is on. --}}
        <native:pressable
            class="w-full min-h-12 justify-center gap-1 py-2"
            @navigate="$this->goes()->doingWith($service->id)"
            a11y-label="{{ __('health.open_service', ['name' => $service->name]) }}"
            :press-opacity="0.6"
        >
            <x-operator::emphasis>{{ $service->name }}</x-operator::emphasis>
            <native:text>{{ __($service->runsSaid) }}</native:text>
        </native:pressable>

        @unless ($loop->last)
            <native:divider />
        @endunless
    @empty
        {{-- Not the same screen as a stack that could not be asked. Nothing
             running is the state the operator came here to change, and
             saying so is what tells it apart from the obstacle branch. --}}
        <x-operator::emphasis>{{ __('health.nothing_is_running') }}</x-operator::emphasis>
    @endforelse

    {{-- The other granularity. The forms come from the stack's own list
         rather than from the rows, so the form an operator opened this
         screen to start — the one with nothing running in it — is here. --}}
    <x-operator::emphasis>{{ __('health.by_form') }}</x-operator::emphasis>

    @forelse ($this->answer()->forms as $form)
        <native:pressable
            class="w-full min-h-12 justify-center py-2"
            @navigate="$this->goes()->doingWithTheForm($form)"
            a11y-label="{{ __('health.open_form', ['name' => $form]) }}"
            :press-opacity="0.6"
        >
            <native:text>{{ $form }}</native:text>
        </native:pressable>

        @unless ($loop->last)
            <native:divider />
        @endunless
    @empty
        {{-- A stack with no forms at all is a machine nothing has been set
             up on, which is not the same as one whose forms are all
             stopped — and it is not something *ask again* fixes. --}}
        <native:text>{{ __('health.no_forms_at_all') }}</native:text>
    @endforelse

    {{-- Quiet, because it is not the thing this frame wants anybody to do.
         The rows are, and a filled bar beside a column of them reads as one
         more of the same kind of control. --}}
    <x-operator::quiet-action label="{{ __('health.ask_again') }}" tap="again()" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="services" />
