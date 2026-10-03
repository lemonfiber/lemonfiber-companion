{{-- Only where other work held the stack: any other obstacle may have met a
     request the stack acted on, and that one is never sent twice. --}}
@if ($went->wasHeldByOtherWork())
    <x-operator::action label="{{ __('connection.try_again') }}" :tap="$tap" />
@endif
