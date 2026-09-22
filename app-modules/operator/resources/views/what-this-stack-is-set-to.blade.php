<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<native:column class="w-full gap-4 px-6 py-4">
    {{-- How many there are, said before the list. An operator who came here
         to change one thing wants to know whether this is a screen they can
         read or one they have to search, and the count is the only honest
         answer to that before they scroll. --}}
    <x-operator::emphasis>
        {{ trans_choice('config.setting_count', $this->answer()->howMany) }}
    </x-operator::emphasis>

    {{-- Said on every reading rather than only when something is withheld.
         A screen that is silent about where its list came from teaches an
         operator to read silence, and this list is one they will compare
         against what they see in the stack's own interfaces. --}}
    <x-operator::note>{{ __('config.listing_is_the_stacks') }}</x-operator::note>

    @forelse ($this->answer()->set as $setting)
        <x-operator::entry>
            <x-operator::emphasis>{{ $setting->key }}</x-operator::emphasis>

            @if ($setting->withheld)
                {{-- The stack's own note that the value is set and withheld,
                     shown as it stands. Not dots and not the word hidden: the
                     operator reads this stack through other interfaces too,
                     and a second vocabulary for the same fact is one they have
                     to learn twice and one that drifts. --}}
                <x-operator::note>{{ $setting->said }}</x-operator::note>
            @else
                <native:text>{{ $setting->said }}</native:text>
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
