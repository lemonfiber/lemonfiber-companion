{{-- What the member met, and what to do about it where there is anything to
     say. The caller holds the notice, so its tone stays the screen's. --}}
<x-design::strong>{{ $said }}</x-design::strong>
@if ($todo !== '')
    <x-design::body>{{ $todo }}</x-design::body>
@endif
