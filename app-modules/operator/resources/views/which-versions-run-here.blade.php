<x-operator::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-design::title>{{ __('stacks.versions.title') }}</x-design::title>

    {{-- Each version with what it is, since none of the three names says
         what it does for the machine. --}}
    <x-design::card>
        <x-design::strong>{{ __('stacks.versions.lemonfiber', ['version' => $this->answer()->lemonfiber]) }}</x-design::strong>
        <x-design::note>{{ __('stacks.versions.lemonfiber_means') }}</x-design::note>
    </x-design::card>
    <x-design::card>
        <x-design::strong>{{ __('stacks.versions.stack', ['version' => $this->answer()->stack]) }}</x-design::strong>
        <x-design::note>{{ __('stacks.versions.stack_means') }}</x-design::note>
    </x-design::card>
    <x-design::card>
        @if ($this->answer()->engine !== '')
            <x-design::strong>{{ __('stacks.versions.engine', ['version' => $this->answer()->engine]) }}</x-design::strong>
        @else
            <x-design::strong>{{ __('stacks.versions.engine_unknown') }}</x-design::strong>
        @endif
        <x-design::note>{{ __('stacks.versions.engine_means') }}</x-design::note>
    </x-design::card>

    {{-- The running release's notes where they describe this copy, and
         otherwise what stands in their place: neither a lag nor a record out
         of step is ever drawn as current. --}}
    @if ($this->answer()->release !== '')
        <x-design::heading>{{ __('updates.what_it_changed', ['version' => $this->answer()->release]) }}</x-design::heading>
        @if ($this->answer()->withdrawn)
            <x-design::notice>
                <x-design::strong>{{ __('updates.running_withdrawn') }}</x-design::strong>
            </x-design::notice>
        @endif
        <x-design::note>{{ __($this->answer()->noticedSaid) }}</x-design::note>
        <x-design::card>
            @forelse ($this->answer()->changes as $line)
                @if ($line->heading)
                    <x-design::strong>{{ $line->said }}</x-design::strong>
                @else
                    <x-design::body>{{ $line->said }}</x-design::body>
                @endif
            @empty
                <x-design::note>{{ __('updates.delivers_unsaid') }}</x-design::note>
            @endforelse
        </x-design::card>
    @else
        <x-design::heading>{{ __($this->answer()->notesSaid) }}</x-design::heading>
        <x-design::note>{{ __($this->answer()->notesMeanSaid) }}</x-design::note>
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
