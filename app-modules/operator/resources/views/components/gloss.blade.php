{{-- What a word just drawn means, in place: the glossary's short line, and
     the longer one a tap away rather than leading. Nothing where the glossary
     does not carry the word, which is then read as it came. --}}
@if ($gloss->isExplained())
    <x-operator::note>{{ __('stacks.words.in_place', ['word' => $gloss->word, 'short' => $gloss->short]) }}</x-operator::note>
    @if ($gloss->goes !== '')
        <x-operator::quiet-action label="{{ __('stacks.words.more', ['word' => $gloss->word]) }}" :goes="$gloss->goes" />
    @endif
@endif
