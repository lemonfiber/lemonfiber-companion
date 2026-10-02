{{-- Only where a card above said the stack suggested nothing to try: saying
     so and leaving it there is a dead end. --}}
@if ($isOwed)
    <x-design::link label="{{ __('device.share_diagnostics') }}" :goes="$goes" />
@endif
