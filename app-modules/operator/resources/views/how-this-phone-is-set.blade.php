<x-operator::screen-opens :title="__('settings.title')" />

<x-operator::content>
    {{-- First, because it changes what every setting below can do: with nowhere
         safe to keep anything, nothing chosen here outlasts the launch. --}}
    @if ($this->keepsNothing())
        <x-design::notice>
            <x-design::body>{{ __('settings.no_secure_storage') }}</x-design::body>
        </x-design::notice>
    @endif

    {{-- How long the app may be away before the lock asks again. The row says
         what is in force; the chips below it offer every choice. --}}
    <x-design::section :label="__('settings.lock')">
        <x-design::row :headline="__('settings.lock_after')" :trailing="__($this->lockAfter()->said)" />
    </x-design::section>

    <x-design::chips>
        @forelse ($this->lockAfter()->offered as $choice)
            <x-design::chip label="{{ __($choice->said) }}" tap="lockAfterIs('{{ $choice->word }}')" :chosen="$choice->chosen" />
        @empty
            {{-- Nothing: there are always five choices. --}}
        @endforelse
    </x-design::chips>

    <x-design::note>{{ __('settings.lock_after_is') }}</x-design::note>
</x-operator::content>
