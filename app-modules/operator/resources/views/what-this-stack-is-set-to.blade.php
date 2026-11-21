@use('Modules\Kernel\Api\WhatToChange')
@use('Modules\Stacks\Api\AStacksScreen')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-operator::set-up-with :settled="$this->settled()" />

    {{-- How many there are, said before the list. An operator who came here
         to change one thing wants to know whether this is a screen they can
         read or one they have to search, and the count is the only honest
         answer to that before they scroll. --}}
    <x-design::heading>{{ trans_choice('config.setting_count', $this->answer()->howMany()) }}</x-design::heading>

    {{-- Said on every reading rather than only when something is withheld.
         A screen that is silent about where its list came from teaches an
         operator to read silence, and this list is one they will compare
         against what they see in the stack's own interfaces. --}}
    <x-design::note>{{ __('config.listing_is_the_stacks') }}</x-design::note>
    <x-design::action :label="__('navigation.menu.decided')" :goes="$this->goes()->to(AStacksScreen::Decided)" tone="tonal" />

    @forelse ($this->answer()->set as $setting)
        <x-design::card>
            <x-design::strong>{{ $setting->key }}</x-design::strong>

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

                <x-operator::the-review :key="$setting->key" :proposal="$this->proposalFor($setting->key)" :waiting="$this->landing !== null" :landing-said="$this->landingSaid" />
            @elseif ($setting->withheld)
                {{-- The stack's own note that the value is set and withheld,
                     shown as it stands. Not dots and not the word hidden: the
                     operator reads this stack through other interfaces too,
                     and a second vocabulary for the same fact is one they have
                     to learn twice and one that drifts. --}}
                <x-design::note>{{ $setting->said }}</x-design::note>
            @else
                <x-design::verbatim>{{ $setting->said }}</x-design::verbatim>

                {{-- Offered here and on no other arm. A withheld value is a
                     credential and this app does not offer to set one, so the
                     control an operator would type into never appears beside
                     one — absent rather than disabled, because a disabled
                     control still says *this is a thing you could do*. --}}
                <x-operator::offered-action
                    :label="__('config.change_this')"
                    :answers-to="__('config.change_key', ['key' => $setting->key])"
                    :tap="$this->tapping('change', $setting->key)"
                    :offer="$this->offered(WhatToChange::Setting)"
                    drawn="link"
                />
            @endif

            {{-- Beside the value, on every card, so that reading what a
                 setting is and reading who set it are one act. Every arm says
                 something, including the ordinary one: a card silent about its
                 origin is read as a default, and *nobody could establish this*
                 is the one attribution that must never be mistaken for the
                 stack's own. --}}
            <x-operator::came-from :from="$setting->from" :said="$setting->from->came->ofASetting()" />
            {{-- What a plugin's change replaced, and where that came from, so
                 putting it back is known to return to a default or to
                 somebody's choice. --}}
            @if ($setting->from->before !== null)
                <x-design::note>{{ __($setting->from->before->said, ['value' => $setting->from->before->value]) }}</x-design::note>
                <x-operator::came-from :from="$setting->from->before->from" :said="$setting->from->before->from->came->ofASetting()" />
            @endif
        </x-design::card>
    @empty
        {{-- A stack with nothing set is an answer, and not the same screen as
             a stack that could not be asked. Saying so in as many words is
             what tells the two apart — they are otherwise the same blank. --}}
        <x-design::body>{{ __('config.nothing_is_set') }}</x-design::body>
        <x-design::note>{{ __('config.nothing_is_set_action') }}</x-design::note>
    @endforelse

    {{-- Putting every edited file and connection back to lemonfiber's own is
         its own screen, because what it would revert is read file by file
         before anything is agreed to. Offered on every reading that came
         back: which files differ is the stack's to say, and this listing of
         settings does not. --}}
    <x-design::action :label="__('config.put_it_all_back')" :goes="$this->goes()->to(AStacksScreen::Reset)" tone="tonal" />

    {{-- First-run setup, declined with the reason where somebody looking for
         it would look: a phone cannot perform the act that makes a phone
         able to reach the stack, and saying so is not the same as leaving
         it out. --}}
    <x-design::strong>{{ __('config.first_run_setup') }}</x-design::strong>
    <x-design::body>{{ __('config.first_run_setup_why') }}</x-design::body>

    {{-- Last, under what it is about, for the reason every other reading
         screen puts it there: somebody who has just changed something in the
         stack scrolls to the end of what they were reading, and that is where
         they want to ask whether it took. --}}
    <x-design::section :label="__('without.section')">
        @if ($this->withoutIsBeingRead())
            <x-design::row :headline="__('without.reading')" />
            <x-design::the-next-frame />
        @elseif ($this->without()->went->cameBack())
            @if ($this->without()->ownDirectory !== '')
                <x-design::notice tone="attention">
                    <x-design::strong>{{ __('without.own_stack', ['path' => $this->without()->ownDirectory]) }}</x-design::strong>
                </x-design::notice>
            @endif

            <x-design::body>{{ __('without.runs_without') }}</x-design::body>
            <x-design::verbatim>{{ $this->without()->plainUp }}</x-design::verbatim>
            <x-design::action :label="__('without.copy')" tap="copyTheWords()" tone="tonal" />

            @if ($this->copied !== null)
                <x-design::note>{{ __($this->copied) }}</x-design::note>
            @endif

            <x-design::body>{{ __('without.project', ['path' => $this->without()->project]) }}</x-design::body>
            <x-design::body>{{ __('without.env', ['path' => $this->without()->env]) }}</x-design::body>
            <x-design::body>{{ __('without.non_interactive') }}</x-design::body>
            <x-design::verbatim>{{ $this->without()->nonInteractive }}</x-design::verbatim>

            <x-design::strong>{{ __('without.left_out') }}</x-design::strong>
            @forelse ($this->without()->leftOut as $part)
                {{-- The operator's own words for why the part is left out. --}}
                <x-design::row :headline="$part->part" :supporting="$part->reason" />
            @empty
                <x-design::row :headline="__('without.nothing_left_out')" />
            @endforelse
        @else
            <x-design::row :headline="__('without.unread')" />
        @endif
    </x-design::section>

    <x-design::action :label="__('config.ask_again')" tap="askAgain()" tone="tonal" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :goes="$this->goes()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
