{{-- What a word just drawn means, in place: the glossary's short line, and
     the longer one a tap away rather than leading. Where the glossary does not
     carry the word it is read as it came, with the way to ask the stack for
     that one word. --}}
@if ($gloss->isExplained())
    <x-operator::note>{{ __('stacks.words.in_place', ['word' => $gloss->word, 'short' => $gloss->short]) }}</x-operator::note>
    @if ($gloss->goes !== '')
        <x-operator::quiet-action label="{{ __('stacks.words.more', ['word' => $gloss->word]) }}" :goes="$gloss->goes" />
    @endif
@endif
@if ($gloss->asks !== '')
    <x-operator::quiet-action label="{{ __('stacks.words.ask_in_place', ['word' => $gloss->word]) }}" :goes="$gloss->asks" />
@endif
@if ($gloss->waits)
    <x-design::the-next-frame />
@endif
