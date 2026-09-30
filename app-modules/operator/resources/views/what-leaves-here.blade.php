<x-operator::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    {{-- What lemonfiber sends on its own account, first and under its own
         heading: these are the connections this product answers for, each
         with the setting that stops it. --}}
    <x-design::heading>{{ __('stacks.outbound.ours.heading') }}</x-design::heading>

    @forelse ($this->answer()->ours as $request)
        <x-design::card>
            <x-design::strong>{{ __($request->asksForSaid) }}</x-design::strong>
            <x-design::note>{{ $request->purpose }}</x-design::note>
            <x-design::body>{{ __('stacks.outbound.ours.sends', ['sends' => $request->sends]) }}</x-design::body>

            @forelse ($request->destinations as $destination)
                <x-design::note>{{ __('stacks.outbound.ours.goes_to', ['destination' => $destination]) }}</x-design::note>
            @empty
                {{-- Nowhere is configured to reach, which is not switched off:
                     the line below says that, separately. --}}
                <x-design::note>{{ __('stacks.outbound.ours.nowhere') }}</x-design::note>
            @endforelse

            <x-design::body>{{ __($request->allowedSaid) }}</x-design::body>
            <x-design::note>{{ __('stacks.outbound.ours.switch', ['switch' => $request->switch]) }}</x-design::note>
            {{-- The cost is on every card, because turning something off is the
                 decision this list exists to inform. --}}
            <x-design::note>{{ __('stacks.outbound.ours.cost', ['cost' => $request->cost]) }}</x-design::note>
        </x-design::card>
    @empty
        <x-design::body>{{ __('stacks.outbound.ours.none') }}</x-design::body>
    @endforelse

    {{-- What the services send, under a heading of their own and never mixed
         into the list above: none of these is lemonfiber's doing, and none is
         switched off from here. --}}
    <x-design::heading>{{ __('stacks.outbound.theirs.heading') }}</x-design::heading>

    @forelse ($this->answer()->theirs as $request)
        <x-design::card>
            <x-design::strong>{{ $request->service }}</x-design::strong>
            {{-- Who put the service on the stack, where that is not the stack
                 itself — the report's rule, for its reason: nearly every card is
                 the stack's own, and the list says once what an unmarked card
                 is. A plugin's service is the card this matters on, since it is
                 the one likeliest to arrive with no record of where it goes. --}}
            @unless ($request->from->isTheStacksOwn())
                <x-operator::came-from :from="$request->from" :said="$request->from->came->ofAService()" />
            @endunless
            {{-- One of three sentences — where it goes, that it goes nowhere,
                 or that nobody knows — chosen by the presenter from the arm the
                 service took, never from whether a destination is empty. --}}
            <x-design::body>{{ __($request->reachesSaid, ['destination' => $request->destination]) }}</x-design::body>

            @if ($request->purpose !== '')
                <x-design::note>{{ $request->purpose }}</x-design::note>
            @endif
        </x-design::card>
    @empty
        <x-design::body>{{ __('stacks.outbound.theirs.none') }}</x-design::body>
    @endforelse

    @if ($this->answer()->marksAnOrigin())
        <x-design::note>{{ __('stacks.outbound.theirs.origin.legend') }}</x-design::note>
    @endif

    <x-design::action label="{{ __('health.ask_again') }}" tap="again()" tone="tonal" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
