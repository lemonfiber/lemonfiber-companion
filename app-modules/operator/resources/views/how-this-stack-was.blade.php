<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

<x-operator::content>
    {{-- The first frame, drawn before the stack is asked anything: the one
         line the phone kept from an earlier session, with when it was read.
         It is never current, so it reads as unknown and says when it was last
         heard; the worst thing it named is still said, so the operator knows
         what was last true. The fresh summary replaces it on the next frame. --}}
    <x-design::standing
        :said="__($this->summary()->said)"
        :tone="$this->summary()->tone"
        :note="$this->summary()->ago->said === '' ? '' : __('health.summary.as_of', ['ago' => trans_choice($this->summary()->ago->said, $this->summary()->ago->count)])"
    />

    @if ($this->summary()->worst !== '')
        <x-design::body>{{ $this->summary()->worst }}</x-design::body>
    @endif
</x-operator::content>

<x-operator::screen-closes :goes="$this->goes()" here="health" />
