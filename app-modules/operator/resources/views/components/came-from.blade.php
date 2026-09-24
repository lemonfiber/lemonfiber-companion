{{-- The name or the reason, where the arm carries one. The two are never both
     present, so both placeholders are handed the one string and the sentence
     uses whichever it has. --}}
@if ($from->attributed !== null)
    <x-operator::note>{{ __($said, ['named' => $from->attributed, 'why' => $from->attributed]) }}</x-operator::note>
@else
    <x-operator::note>{{ __($said) }}</x-operator::note>
@endif
