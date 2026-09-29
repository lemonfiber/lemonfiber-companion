{{-- A stack's refusal, in its words: the sentence, what it means, and what
     it named. What it names can be a file on the machine, and the screen that
     asked is the only place it is drawn. The screen says around it what the
     refusal was of and where to go next. --}}
<x-operator::emphasis>{{ $refused->said }}</x-operator::emphasis>

@if ($refused->meaning !== '')
    <native:text>{{ $refused->meaning }}</native:text>
@endif

@if ($refused->named !== '')
    <native:text>{{ __('stacks.refusal.named', ['named' => $refused->named]) }}</native:text>
@endif
