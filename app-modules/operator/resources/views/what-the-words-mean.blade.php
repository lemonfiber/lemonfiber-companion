<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-operator::emphasis>{{ __('stacks.words.heading') }}</x-operator::emphasis>

    {{-- Over the words and what else they are called, which is what somebody
         arriving from another tool searches for. --}}
    <native:outlined-text-input
        native:model="looking"
        label="{{ __('stacks.words.search_label') }}"
        placeholder="{{ __('stacks.words.search_placeholder') }}"
    />

    @forelse ($this->answer()->words as $place => $word)
        <x-operator::entry>
            <x-operator::emphasis>{{ $word->word }}</x-operator::emphasis>
            <native:text>{{ $word->short }}</native:text>
            @if ($word->alsoCalled !== '')
                <x-operator::note>{{ __('stacks.words.also_called', ['names' => $word->alsoCalled]) }}</x-operator::note>
            @endif

            {{-- The longer gloss is there for whoever asks, and does not lead. --}}
            @if ($word->isOpen)
                <native:text>{{ $word->deep }}</native:text>
                <x-operator::quiet-action label="{{ __('stacks.words.less', ['word' => $word->word]) }}" tap="toggle('{{ $place }}')" />
            @elseif ($word->deep !== '')
                <x-operator::quiet-action label="{{ __('stacks.words.more', ['word' => $word->word]) }}" tap="toggle('{{ $place }}')" />
            @endif
        </x-operator::entry>
    @empty
        @if ($this->answer()->isSearching)
            <x-operator::emphasis>{{ __('stacks.words.nothing_matched') }}</x-operator::emphasis>
        @else
            <x-operator::emphasis>{{ __('stacks.words.none') }}</x-operator::emphasis>
        @endif
    @endforelse

    <x-operator::action label="{{ __('health.ask_again') }}" tap="again()" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
