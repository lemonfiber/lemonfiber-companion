@use('Modules\Kernel\Api\ExtendingIt')
<x-wayfinding::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-operator::heading>{{ __('plugins.heading') }}</x-operator::heading>

@if ($this->answer()->isWorking)
    @if ($this->answer()->installing)
        <x-design::body>{{ __('plugins.installing') }}</x-design::body>
    @else
        <x-design::body>{{ __('plugins.rehearsing') }}</x-design::body>
    @endif
@elseif ($this->answer()->refused !== null)
    {{-- The stack's answer, in its words: a source holding no plugin, a
         plugin this build refuses, a value left unapproved or an offer that
         moved. Nothing is offered to install beneath it. --}}
    @if ($this->answer()->installing)
        <x-operator::emphasis>{{ __('plugins.install_refused') }}</x-operator::emphasis>
    @else
        <x-operator::emphasis>{{ __('plugins.rehearsal_refused') }}</x-operator::emphasis>
    @endif
    <x-operator::refused-in-its-words :refused="$this->answer()->refused" />
    <x-operator::quiet-action label="{{ __('plugins.back') }}" tap="backToThePlugins()" />
@elseif ($this->answer()->hasEnded)
    {{-- Not a failure and not a refusal: the stack has no outcome for the
         work any more, which is not the same as it not having happened. --}}
    <x-operator::emphasis>{{ __('plugins.no_outcome') }}</x-operator::emphasis>
    <x-operator::quiet-action label="{{ __('plugins.back') }}" tap="backToThePlugins()" />
@elseif ($this->answer()->typing)
    <native:outlined-text-input native:model="source" label="{{ __('plugins.where_from') }}" supporting="{{ __('plugins.source_is') }}" />
    {{-- The three shapes a source can take, under the one field. Which one
         was typed is the stack's to tell. --}}
    <x-operator::note>{{ __('plugins.source_catalogue') }}</x-operator::note>
    <x-operator::note>{{ __('plugins.source_directory') }}</x-operator::note>
    <x-operator::note>{{ __('plugins.source_git') }}</x-operator::note>
    <x-operator::offered-action label="{{ __('plugins.rehearse') }}" tap="rehearse()" :offer="$this->offered(ExtendingIt::Install)" />
    <x-operator::quiet-action label="{{ __('plugins.back') }}" tap="backToThePlugins()" />
@elseif ($this->answer()->install !== null)
    @if ($this->answer()->install->isAReading)
        {{-- Said before anything else: nothing below has happened. --}}
        <x-operator::emphasis>{{ __('plugins.rehearsal') }}</x-operator::emphasis>
    @else
        <x-operator::emphasis>{{ __($this->answer()->install->headline) }}</x-operator::emphasis>
    @endif

    <x-operator::the-plugin :plugin="$this->answer()->install->plugin" />

    <x-operator::heading>{{ __('plugins.changes') }}</x-operator::heading>
    @forelse ($this->answer()->install->changes as $change)
        <x-operator::entry>
            <x-design::body>{{ $change->path }}</x-design::body>
            <x-operator::note>{{ __($change->putsSaid) }}</x-operator::note>
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('plugins.no_changes') }}</x-operator::note>
    @endforelse

    {{-- Not asked is never passed, and could not conclude is never failed:
         each proof says which it came to in its own words. --}}
    <x-operator::heading>{{ __('plugins.proofs') }}</x-operator::heading>
    @forelse ($this->answer()->install->proofs as $proof)
        <x-operator::entry>
            <x-operator::emphasis>{{ $proof->establishes }}</x-operator::emphasis>
            <x-operator::note>{{ $proof->asks }}</x-operator::note>
            @if ($proof->why !== '')
                <x-design::body>{{ $proof->why }}</x-design::body>
            @endif
            <x-design::body>{{ __($proof->cameToSaid) }}</x-design::body>
            @forelse ($proof->said as $said)
                <x-operator::note>{{ $said }}</x-operator::note>
            @empty
                {{-- Nothing said with it, which is a proof not asked or one that held. --}}
            @endforelse
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('plugins.no_proofs') }}</x-operator::note>
    @endforelse

    <x-operator::heading>{{ __('plugins.overrides') }}</x-operator::heading>
    @forelse ($this->answer()->install->overrides as $override)
        <x-operator::entry>
            <x-operator::emphasis>{{ $override->target }}</x-operator::emphasis>
            <x-design::body>{{ $override->because }}</x-design::body>
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('plugins.no_overrides') }}</x-operator::note>
    @endforelse

    {{-- Choosing between the claimants is the Connections screen's. --}}
    <x-operator::heading>{{ __('plugins.contests') }}</x-operator::heading>
    @forelse ($this->answer()->install->contests as $contest)
        <x-operator::entry>
            <x-operator::emphasis>{{ $contest->capability }}</x-operator::emphasis>
            @if ($contest->by !== '')
                <x-operator::note>{{ __('plugins.asked_by', ['by' => $contest->by]) }}</x-operator::note>
            @endif
            @forelse ($contest->claimants as $claimant)
                <x-design::body>{{ __('plugins.claimant', ['claimant' => $claimant]) }}</x-design::body>
            @empty
                <x-operator::note>{{ __('plugins.no_claimants') }}</x-operator::note>
            @endforelse
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('plugins.no_contests') }}</x-operator::note>
    @endforelse

    {{-- Every recipe as its steps in order, and every value it would carry
         elsewhere as its own line with its own switch. --}}
    <x-operator::heading>{{ __('plugins.recipes') }}</x-operator::heading>
    @forelse ($this->answer()->install->plugin->recipes as $recipe)
        <x-operator::entry>
            <x-operator::emphasis>{{ $recipe->title }}</x-operator::emphasis>
            @if ($recipe->why !== '')
                <x-design::body>{{ $recipe->why }}</x-design::body>
            @endif
            @forelse ($recipe->steps as $step)
                <x-design::body>{{ __('plugins.step', ['method' => $step->method, 'to' => $step->to, 'path' => $step->path]) }}</x-design::body>
                @if ($step->adapter !== '')
                    <x-operator::note>{{ __('plugins.adapter', ['adapter' => $step->adapter]) }}</x-operator::note>
                @endif
            @empty
                <x-operator::note>{{ __('plugins.no_steps') }}</x-operator::note>
            @endforelse
            @forelse ($recipe->pairs as $pair)
                <x-design::body>{{ __('plugins.pair', ['value' => $pair->value, 'to' => $pair->to]) }}</x-design::body>
                @if ($pair->origin !== '')
                    <x-operator::note>{{ __('plugins.origin', ['origin' => $pair->origin]) }}</x-operator::note>
                @endif
                @if ($pair->release !== '')
                    <x-operator::note>{{ __('plugins.release', ['from' => $pair->from, 'why' => $pair->release]) }}</x-operator::note>
                @endif
                @if ($pair->approval !== null && $this->answer()->install->agreeable)
                    <x-design::toggle :label="__('plugins.approve', ['value' => $pair->value, 'to' => $pair->to])" :on="$pair->approved" tap="approve({{ $pair->approval }})" />
                @elseif ($pair->approval === null)
                    <x-operator::note>{{ __('plugins.stays_here') }}</x-operator::note>
                @endif
            @empty
                <x-operator::note>{{ __('plugins.sends_nothing') }}</x-operator::note>
            @endforelse
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('plugins.no_recipes') }}</x-operator::note>
    @endforelse

    @if ($this->answer()->install->agreeable)
        {{-- The yes to the install, apart from every approval above. --}}
        <x-operator::note>{{ __('plugins.approvals_apart') }}</x-operator::note>
        <x-operator::offered-action label="{{ __('plugins.install') }}" tap="install()" :offer="$this->offered(ExtendingIt::Install)" />
        <x-operator::note>{{ __('plugins.inputs_elsewhere') }}</x-operator::note>
    @elseif (! $this->answer()->install->isAReading)
        {{-- What the stack's own checks made of it, which is half of what makes
             an install one. Unsettled is said and never read as broken. --}}
        @if ($this->answer()->install->checked)
            <x-operator::heading>{{ __('plugins.broke') }}</x-operator::heading>
            @forelse ($this->answer()->install->broke as $check)
                <x-design::body>{{ $check }}</x-design::body>
            @empty
                <x-operator::note>{{ __('plugins.broke_nothing') }}</x-operator::note>
            @endforelse
            <x-operator::heading>{{ __('plugins.unsettled') }}</x-operator::heading>
            @forelse ($this->answer()->install->unsettled as $check)
                <x-design::body>{{ $check }}</x-design::body>
            @empty
                <x-operator::note>{{ __('plugins.all_settled') }}</x-operator::note>
            @endforelse
        @else
            <x-operator::note>{{ __('plugins.not_checked') }}</x-operator::note>
        @endif

        @if ($this->answer()->install->putBack !== null)
            {{-- What went back and what did not, each with its reason. --}}
            <x-operator::heading>{{ __('plugins.what_went_back') }}</x-operator::heading>
            @forelse ($this->answer()->install->putBack->left as $left)
                <x-operator::entry>
                    <x-operator::emphasis>{{ $left->target }}</x-operator::emphasis>
                    <x-design::body>{{ $left->because }}</x-design::body>
                </x-operator::entry>
            @empty
                <x-operator::note>{{ __('plugins.nothing_left') }}</x-operator::note>
            @endforelse
            @forelse ($this->answer()->install->putBack->reversed as $reversed)
                <x-operator::entry>
                    <x-design::body>{{ $reversed->target }}</x-design::body>
                    <x-operator::note>{{ __($reversed->doesSaid) }}</x-operator::note>
                </x-operator::entry>
            @empty
                <x-operator::note>{{ __('plugins.nothing_reversed') }}</x-operator::note>
            @endforelse
        @endif
    @endif

    <x-operator::quiet-action label="{{ __('plugins.back') }}" tap="backToThePlugins()" />
@else
    @forelse ($this->answer()->installed as $plugin)
        <x-operator::the-plugin :plugin="$plugin" />
    @empty
        {{-- The record answered and holds nothing. A record that could not be
             read never reaches this branch. --}}
        <x-design::body>{{ __('plugins.none') }}</x-design::body>
    @endforelse

    <x-operator::offered-action label="{{ __('plugins.install_one') }}" tap="installOne()" :offer="$this->offered(ExtendingIt::Install)" />
    <x-operator::quiet-action label="{{ __('health.ask_again') }}" tap="askAgain()" />
@endif
</x-operator::content>
@else
    {{-- Reading what is installed, or following the work, met something.
         After an install, whether it happened could not be read, and that is
         said first. --}}
    @if ($this->answer()->installing)
        <x-operator::content>
            <x-operator::emphasis>{{ __('plugins.unread_after_yes') }}</x-operator::emphasis>
        </x-operator::content>
    @endif
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :goes="$this->goes()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
