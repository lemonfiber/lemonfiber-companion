<x-operator::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-design::title>{{ __('stacks.words.heading') }}</x-design::title>

    {{-- Over the words and what else they are called, which is what somebody
         arriving from another tool searches for. --}}
    <native:outlined-text-input
        native:model="looking"
        label="{{ __('stacks.words.search_label') }}"
        placeholder="{{ __('stacks.words.search_placeholder') }}"
    />

    @forelse ($this->answer()->words as $place => $word)
        <x-design::card>
            <x-design::strong>{{ $word->word }}</x-design::strong>
            <x-design::body>{{ $word->short }}</x-design::body>
            @if ($word->alsoCalled !== '')
                <x-design::note>{{ __('stacks.words.also_called', ['names' => $word->alsoCalled]) }}</x-design::note>
            @endif

            {{-- The longer gloss is there for whoever asks, and does not lead. --}}
            @if ($word->isOpen)
                <x-design::body>{{ $word->deep }}</x-design::body>
                <x-design::link label="{{ __('stacks.words.less', ['word' => $word->word]) }}" tap="toggle('{{ $place }}')" />
            @elseif ($word->deep !== '')
                <x-design::link label="{{ __('stacks.words.more', ['word' => $word->word]) }}" tap="toggle('{{ $place }}')" />
            @endif
        </x-design::card>
    @empty
        @if ($this->answer()->isSearching)
            <x-design::body>{{ __('stacks.words.nothing_matched') }}</x-design::body>
        @else
            <x-design::body>{{ __('stacks.words.none') }}</x-design::body>
        @endif
    @endforelse

    {{-- A word searched for that the held glossary has no entry for. Asking
         is the operator's, for that one word; the stack saying it has none
         either is its answer, and the word stands as it came. --}}
    @if ($this->answer()->unexplained !== '')
        <x-design::note>{{ __('stacks.words.unexplained', ['word' => $this->answer()->unexplained]) }}</x-design::note>
    @endif
    @if ($this->answer()->askingMet !== '')
        <x-design::body>{{ __($this->answer()->askingMet) }}</x-design::body>
    @endif
    @if ($this->answer()->mayAsk !== '')
        <x-design::action label="{{ __('stacks.words.ask', ['word' => $this->answer()->mayAsk]) }}" tap="askTheStack()" tone="tonal" />
    @endif

    <x-design::action label="{{ __('health.ask_again') }}" tap="again()" tone="tonal" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
