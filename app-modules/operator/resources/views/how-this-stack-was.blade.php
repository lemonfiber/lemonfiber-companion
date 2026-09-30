<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

<x-operator::content>
    {{-- The first frame, drawn before the stack is asked anything: the one
         line the phone kept from an earlier session, drawn as the live screen
         draws it. It is never current, so it reads as unknown and says when
         it was updated; the worst thing it named is still the heading, so the
         operator knows what was last true. The fresh summary replaces it on
         the next frame. --}}
    <x-design::standing
        :said="$this->summary()->worst === '' ? __($this->summary()->said) : $this->summary()->worst"
        :tone="$this->summary()->tone"
        :note="$this->summary()->ago->said === '' ? '' : __('health.summary.as_of', ['ago' => trans_choice($this->summary()->ago->said, $this->summary()->ago->count)])"
        :word="__($this->summary()->word)"
    />
</x-operator::content>

<x-operator::screen-closes :goes="$this->goes()" here="health" />
