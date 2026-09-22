<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<native:column class="w-full gap-4 px-6 py-4">
    {{-- How many there are, said before the list. An operator who came here
         to change one thing wants to know whether this is a screen they can
         read or one they have to search, and the count is the only honest
         answer to that before they scroll. --}}
    <x-operator::emphasis>
        {{ trans_choice('config.setting_count', $this->answer()->howMany()) }}
    </x-operator::emphasis>

    {{-- Said on every reading rather than only when something is withheld.
         A screen that is silent about where its list came from teaches an
         operator to read silence, and this list is one they will compare
         against what they see in the stack's own interfaces. --}}
    <x-operator::note>{{ __('config.listing_is_the_stacks') }}</x-operator::note>

    @forelse ($this->answer()->set as $setting)
        <x-operator::entry>
            <x-operator::emphasis>{{ $setting->key }}</x-operator::emphasis>

            @if ($this->changing() === $setting->key)
                {{-- One input on the screen, for the setting that is open.
                     Seeded with what the stack showed, because the common
                     edit is one character of a path and starting from blank
                     would make retyping the whole thing the default. --}}
                <native:outlined-text-input
                    native:model="typed"
                    label="{{ $setting->key }}"
                    supporting="{{ __('config.what_it_would_hold') }}"
                />

                @if ($this->proposalFor($setting->key) !== null)
                    {{-- What the stack said this would come to, before
                         anything is agreed to. The difference and not just
                         the new value: somebody deciding is deciding between
                         two things, and a screen showing one is asking them
                         to remember the other correctly. --}}
                    <x-operator::note>
                        @if ($this->proposalFor($setting->key)->holdsNothingYet)
                            {{ __('config.holds_nothing_yet') }}
                        @else
                            {{ __('config.holds_now', ['value' => $this->proposalFor($setting->key)->fromSaid]) }}
                        @endif
                    </x-operator::note>
                    {{-- Both sides of the difference, and this is the half
                         the input does not already show: what the stack
                         understood it to be. An operator agreeing is agreeing
                         to this, not to what is in the field. --}}
                    <native:text>
                        {{ __('config.would_hold', ['value' => $this->proposalFor($setting->key)->toSaid]) }}
                    </native:text>

                    <x-operator::note>{{ __($this->proposalFor($setting->key)->costSaid) }}</x-operator::note>
                    <x-operator::emphasis>{{ __($this->proposalFor($setting->key)->stanceSaid) }}</x-operator::emphasis>

                    @if ($this->proposalFor($setting->key)->wroteSomething)
                        {{-- Said only where something was written. A setting
                             that already held the value reaches the stance
                             above saying so and needs no line about a
                             restart that is not going to happen. --}}
                        <x-operator::note>{{ __('config.services_will_restart') }}</x-operator::note>
                    @endif

                    @if ($this->proposalFor($setting->key)->refusalSaid !== '')
                        {{-- The stack's own words for why nothing was
                             written. Shown as they stand: a reason this app
                             reworded would be a second account of something
                             it does not understand. --}}
                        <x-operator::note>{{ $this->proposalFor($setting->key)->refusalSaid }}</x-operator::note>
                    @endif

                    @unless ($this->proposalFor($setting->key)->holdsWhatWasAsked)
                        @if ($this->proposalFor($setting->key)->mustBeAgreedFirst)
                            {{-- Before the control rather than after it. The
                                 core decides which changes cost something,
                                 and an operator reading downward should meet
                                 the warning before the button rather than
                                 underneath it. --}}
                            <x-operator::emphasis>{{ __('config.worth_reading_twice') }}</x-operator::emphasis>
                        @endif

                        {{-- The spoken name carries the setting; the drawn
                             one stays short. Four rows each offering "Make
                             this change" is four controls with one name
                             between them, and which row a control is on is
                             the one thing somebody being read to cannot
                             check. --}}
                        <x-operator::action
                            label="{{ __('config.agree') }}"
                            a11y-label="{{ __('config.agree_to', ['key' => $setting->key]) }}"
                            tap="agree('{{ $setting->key }}')"
                        />
                    @endunless
                @else
                    <x-operator::action
                        label="{{ __('config.what_would_happen') }}"
                        a11y-label="{{ __('config.what_would_happen_to', ['key' => $setting->key]) }}"
                        tap="wouldBe('{{ $setting->key }}')"
                    />
                @endif

                <x-operator::quiet-action label="{{ __('config.never_mind') }}" tap="never()" />
            @elseif ($setting->withheld)
                {{-- The stack's own note that the value is set and withheld,
                     shown as it stands. Not dots and not the word hidden: the
                     operator reads this stack through other interfaces too,
                     and a second vocabulary for the same fact is one they have
                     to learn twice and one that drifts. --}}
                <x-operator::note>{{ $setting->said }}</x-operator::note>
            @else
                <native:text>{{ $setting->said }}</native:text>

                {{-- Offered here and on no other arm. A withheld value is a
                     credential and this app does not offer to set one, so the
                     control an operator would type into never appears beside
                     one — absent rather than disabled, because a disabled
                     control still says *this is a thing you could do*. --}}
                <x-operator::quiet-action
                    label="{{ __('config.change_this') }}"
                    a11y-label="{{ __('config.change_key', ['key' => $setting->key]) }}"
                    tap="change('{{ $setting->key }}')"
                />
            @endif

            {{-- Beside the value, on every row, so that reading what a setting
                 is and reading who set it are one act. Every arm says
                 something, including the ordinary one: a row silent about its
                 origin is read as a default, and *nobody could establish this*
                 is the one attribution that must never be mistaken for the
                 stack's own. --}}
            @if ($setting->attributed !== null)
                <x-operator::note>{{ __($setting->came->said(), ['named' => $setting->attributed, 'why' => $setting->attributed]) }}</x-operator::note>
            @else
                <x-operator::note>{{ __($setting->came->said()) }}</x-operator::note>
            @endif
        </x-operator::entry>
    @empty
        {{-- A stack with nothing set is an answer, and not the same screen as
             a stack that could not be asked. Saying so in as many words is
             what tells the two apart — they are otherwise the same blank. --}}
        <x-operator::emphasis>{{ __('config.nothing_is_set') }}</x-operator::emphasis>
        <native:text>{{ __('config.nothing_is_set_action') }}</native:text>
    @endforelse

    {{-- Last, under what it is about, for the reason every other reading
         screen puts it there: somebody who has just changed something in the
         stack scrolls to the end of what they were reading, and that is where
         they want to ask whether it took. --}}
    <x-operator::action label="{{ __('config.ask_again') }}" tap="again()" />
</native:column>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
