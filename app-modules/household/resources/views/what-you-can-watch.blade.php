<native:top-bar title="{{ __('household.shelf') }}" />

<x-operator::content>
@if ($this->answer()->cameBack())
    {{-- One row per holding: the title, the kind under it, and the year at
         its end where the core had one. The kind is drawn under the title
         rather than beside it: a row that runs out of width puts the title
         second, and the title is the part somebody is scanning for. --}}
    <x-design::section>
        @forelse ($this->answer()->holdings as $holding)
            <x-design::row :headline="$holding->titled" :supporting="__($holding->medium)" :trailing="$holding->year" />
        @empty
            {{-- Said in as many words. A member whose shelf holds nothing has an
                 answer, and a blank frame is what a library nobody could reach
                 looks like — the branch below is what keeps those apart, and this
                 arm is only reached where the core answered. --}}
            <x-design::row :headline="__('household.shelf_is_empty')" :supporting="__('household.shelf_is_empty_action')" />
        @endforelse
    </x-design::section>

    <x-design::action label="{{ __('household.ask_again') }}" tap="again()" tone="tonal" />
@elseif ($this->answer()->isOutOfReach)
    {{-- Not an empty shelf, and drawn so it can never be mistaken for one. The
         library exists and could not be reached, which is the opposite thing
         to tell somebody about their own collection.

         The core's own sentences, in the core's own words. Whoever could not
         reach what is a fact about two machines, and a line written here would
         be this app guessing at which. --}}
    <x-design::notice tone="unknown">
        <x-design::strong>{{ __('household.shelf_is_out_of_reach') }}</x-design::strong>
        @forelse ($this->answer()->reasons as $reason)
            <x-design::body>{{ $reason }}</x-design::body>
        @empty
            {{-- The core said it could not, and said nothing about why. Better than
                 a blank frame, which reads as the shelf being empty. --}}
            <x-design::body>{{ __('household.shelf_is_out_of_reach_action') }}</x-design::body>
        @endforelse
    </x-design::notice>

    <x-design::action label="{{ __('household.ask_again') }}" tap="again()" />
@elseif ($this->answer()->isSignedIn)
    {{-- What stood in the way and what to do about it, both off the obstacle,
         so this screen cannot describe a condition differently from the one
         beside it. --}}
    <x-design::notice tone="unknown">
        <x-design::strong>{{ __($this->answer()->met) }}</x-design::strong>
        <x-design::body>{{ __($this->answer()->remedy) }}</x-design::body>
    </x-design::notice>

    <x-design::action label="{{ __('household.ask_again') }}" tap="again()" />
@else
    {{-- The session has ended, so nothing was asked and there is nothing to
         report. The remedy is a screen rather than a sentence. --}}
    <x-design::body>{{ __('connection.session_has_ended') }}</x-design::body>
    <x-design::action label="{{ __('connection.sign_in') }}" :goes="$this->signIn()" />
@endif

    {{-- The way back to the machine this reading is about, whatever became
         of the reading. --}}
    <x-design::link label="{{ __('household.back_to_the_machine') }}" :goes="$this->health()" />
</x-operator::content>
