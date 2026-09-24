<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<native:column class="w-full gap-4 px-6 py-4">
    {{-- What lemonfiber sends on its own account, first and under its own
         heading: these are the connections this product answers for, each
         with the setting that stops it. --}}
    <x-operator::emphasis>{{ __('stacks.outbound.ours.heading') }}</x-operator::emphasis>

    @forelse ($this->answer()->ours as $request)
        <x-operator::entry>
            <x-operator::emphasis>{{ __($request->asksForSaid) }}</x-operator::emphasis>
            <x-operator::note>{{ $request->purpose }}</x-operator::note>
            <native:text>{{ __('stacks.outbound.ours.sends', ['sends' => $request->sends]) }}</native:text>

            @forelse ($request->destinations as $destination)
                <x-operator::note>{{ __('stacks.outbound.ours.goes_to', ['destination' => $destination]) }}</x-operator::note>
            @empty
                {{-- Nowhere is configured to reach, which is not switched off:
                     the line below says that, separately. --}}
                <x-operator::note>{{ __('stacks.outbound.ours.nowhere') }}</x-operator::note>
            @endforelse

            <native:text>{{ __($request->allowedSaid) }}</native:text>
            <x-operator::note>{{ __('stacks.outbound.ours.switch', ['switch' => $request->switch]) }}</x-operator::note>
            {{-- The cost is on every row, because turning something off is the
                 decision this list exists to inform. --}}
            <x-operator::note>{{ __('stacks.outbound.ours.cost', ['cost' => $request->cost]) }}</x-operator::note>
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('stacks.outbound.ours.none') }}</x-operator::note>
    @endforelse

    {{-- What the services send, under a heading of their own and never mixed
         into the list above: none of these is lemonfiber's doing, and none is
         switched off from here. --}}
    <x-operator::emphasis>{{ __('stacks.outbound.theirs.heading') }}</x-operator::emphasis>

    @forelse ($this->answer()->theirs as $request)
        <x-operator::entry>
            <x-operator::emphasis>{{ $request->service }}</x-operator::emphasis>
            {{-- Who put the service on the stack, where that is not the stack
                 itself — the report's rule, for its reason: nearly every row is
                 the stack's own, and the list says once what an unmarked row
                 is. A plugin's service is the row this matters on, since it is
                 the one likeliest to arrive with no record of where it goes. --}}
            @unless ($request->from->isTheStacksOwn())
                <x-operator::came-from :from="$request->from" :said="$request->from->came->ofAService()" />
            @endunless
            {{-- One of three sentences — where it goes, that it goes nowhere,
                 or that nobody knows — chosen by the presenter from the arm the
                 service took, never from whether a destination is empty. --}}
            <native:text>{{ __($request->reachesSaid, ['destination' => $request->destination]) }}</native:text>

            @if ($request->purpose !== '')
                <x-operator::note>{{ $request->purpose }}</x-operator::note>
            @endif
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('stacks.outbound.theirs.none') }}</x-operator::note>
    @endforelse

    @if ($this->answer()->marksAnOrigin())
        <x-operator::note>{{ __('stacks.outbound.theirs.origin.legend') }}</x-operator::note>
    @endif

    <x-operator::action label="{{ __('health.ask_again') }}" tap="again()" />
</native:column>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
