{{-- Each service that may be chosen to answer a capability, under the words
     that say a choice is offered. A tap asks what the choice would come to and
     changes nothing. --}}
@forelse ($choices as $service)
    @if ($loop->first)
        <x-design::note>{{ __('stacks.wiring.fills.choose') }}</x-design::note>
    @endif
    <x-design::action
        label="{{ $service }}"
        :answers-to="__('stacks.wiring.fills.choose_for', ['service' => $service, 'capability' => $capability, 'by' => $by])"
        tap="choose('{{ $capability }}', '{{ $service }}')"
        tone="tonal"
    />
@empty
    {{-- Fewer than two services claim it, or every claimant answers it
         already: there is nothing to choose. --}}
@endforelse
