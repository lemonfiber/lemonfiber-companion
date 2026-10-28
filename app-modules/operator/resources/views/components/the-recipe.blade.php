{{-- One recipe as its steps in order, and every value it would carry
 elsewhere as its own line with its own switch, offered only beneath an
 account a yes may be given against. --}}
<x-operator::entry>
    <x-operator::emphasis>{{ $recipe->title }}</x-operator::emphasis>
    @if ($recipe->why !== '')
        <x-design::body>{{ $recipe->why }}</x-design::body>
    @endif
    @forelse ($recipe->steps as $step)
        <x-design::body>{{ __('plugins.step', ['method' => $step->method, 'to' => $step->to, 'path' => $step->path]) }}</x-design::body>
        @if ($step->adapter !== '')
            <x-operator::note>{{ __('plugins.adapter', ['adapter' => $step->adapter]) }}</x-operator::note>
        @endif
    @empty
        <x-operator::note>{{ __('plugins.no_steps') }}</x-operator::note>
    @endforelse
    @forelse ($recipe->pairs as $pair)
        <x-design::body>{{ __('plugins.pair', ['value' => $pair->value, 'to' => $pair->to]) }}</x-design::body>
        @if ($pair->origin !== '')
            <x-operator::note>{{ __('plugins.origin', ['origin' => $pair->origin]) }}</x-operator::note>
        @endif
        @if ($pair->release !== '')
            <x-operator::note>{{ __('plugins.release', ['from' => $pair->from, 'why' => $pair->release]) }}</x-operator::note>
        @endif
        {{-- Staying on this machine is the stack's answer, never what a
             missing switch is taken to mean. --}}
        @if (! $pair->asksForApproval)
            <x-operator::note>{{ __('plugins.stays_here') }}</x-operator::note>
        @elseif ($pair->switch !== null)
            @if ($agreeable)
                <x-design::toggle :label="__('plugins.approve', ['value' => $pair->value, 'to' => $pair->to])" :on="$pair->switch->on" :tap="$tapping('approve', $pair->switch->place)" />
            @endif
        @elseif ($recipe->onDemand)
            <x-operator::note>{{ __('plugins.asked_each_run') }}</x-operator::note>
        @else
            <x-operator::note>{{ __('plugins.not_asked_here') }}</x-operator::note>
        @endif
    @empty
        <x-operator::note>{{ __('plugins.sends_nothing') }}</x-operator::note>
    @endforelse
</x-operator::entry>
