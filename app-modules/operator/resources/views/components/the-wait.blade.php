@if ($wait !== null && $wait->said !== '')
    <x-design::strong>{{ __($wait->said, ['count' => $wait->count]) }}</x-design::strong>
@endif
