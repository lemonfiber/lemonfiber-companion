@use('Modules\Kernel\Api\ExtendingIt')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->stands->went->cameBack())
<x-operator::content>
    <x-operator::heading>{{ __('plugins.heading') }}</x-operator::heading>

    <x-operator::guards-moved :guards="$this->answer()->guards" />

@if ($this->answer()->stands->isWorking())
    <x-design::body>{{ __($this->answer()->working) }}</x-design::body>
@elseif ($this->answer()->refused !== null)
    {{-- The stack's answer, in its words: a source holding no plugin, a
         plugin this build refuses, a value left unapproved or an offer that
         moved, and each service the yes did not allow what it needs. Nothing
         is offered to do beneath it. --}}
    <x-operator::emphasis>{{ __($this->answer()->refused->said) }}</x-operator::emphasis>
    <x-operator::refused-in-its-words :refused="$this->answer()->refused->words" />
    @forelse ($this->answer()->refused->unapproved as $shape)
        <x-operator::entry>
            <x-operator::emphasis>{{ __('plugins.shape_unapproved', ['service' => $shape->service]) }}</x-operator::emphasis>
            <x-design::body>{{ __($shape->neededFor) }}</x-design::body>
        </x-operator::entry>
    @empty
        {{-- Every service taking a shape was allowed it, or none takes one. --}}
    @endforelse
    <x-operator::quiet-action :label="__('plugins.back')" tap="backToThePlugins()" />
@elseif ($this->answer()->stands->hasEnded())
    <x-operator::why-it-ended :stands="$this->answer()->stands" />
    {{-- Not a failure and not a refusal: the stack has no outcome for the
         work any more, which is not the same as it not having happened. --}}
    <x-operator::emphasis>{{ __('plugins.no_outcome') }}</x-operator::emphasis>
    <x-operator::quiet-action :label="__('plugins.back')" tap="backToThePlugins()" />
@elseif (! $this->answer()->stands->wasAsked())
    {{-- What the stack's verified catalogue offers, above the typed source.
         Choosing an entry rehearses it as a typed name is rehearsed. --}}
    @if ($this->catalogue()->listed)
        <x-operator::heading>{{ __('plugins.catalogue.heading') }}</x-operator::heading>
        @if ($this->catalogue()->published->said !== '')
            <x-operator::note>{{ __('plugins.catalogue.published', ['when' => trans_choice($this->catalogue()->published->said, $this->catalogue()->published->count)]) }}</x-operator::note>
        @endif
        @forelse ($this->catalogue()->entries as $at => $entry)
            <x-design::card>
                <x-design::strong>{{ __('plugins.named', ['name' => $entry->name, 'version' => $entry->version]) }}</x-design::strong>
                <x-design::body>{{ $entry->description }}</x-design::body>
                <x-design::body>{{ __('plugins.catalogue.without_it', ['without' => $entry->withoutIt]) }}</x-design::body>
                @if ($entry->installed !== '')
                    <x-operator::note>{{ __('plugins.catalogue.installed', ['version' => $entry->installed]) }}</x-operator::note>
                @endif
                @if ($entry->newer)
                    <x-operator::note>{{ __('plugins.catalogue.newer', ['version' => $entry->version]) }}</x-operator::note>
                @endif
            </x-design::card>
            @if ($entry->installed !== '')
                <x-operator::quiet-action :label="__('plugins.settings.open', ['name' => $entry->name])" :goes="$this->goes()->aPluginsSettings($entry->id)" />
            @else
                <x-operator::offered-action :label="__('plugins.catalogue.choose', ['name' => $entry->name])" :tap="$this->tapping('choose', $at)" :offer="$this->offered(ExtendingIt::Install)" />
            @endif
        @empty
            <x-operator::note>{{ __('plugins.catalogue.empty') }}</x-operator::note>
        @endforelse
        @if ($this->catalogue()->unreadable > 0)
            <x-operator::note>{{ __('plugins.catalogue.unreadable', ['count' => $this->catalogue()->unreadable]) }}</x-operator::note>
        @endif
    @elseif (! $this->catalogue()->isBeingRead())
        {{-- Off, not reached, or not asked: said in the stack's words. The
             typed source below still installs from a directory or git. --}}
        <x-design::notice tone="unknown">
            <x-design::strong>{{ __($this->catalogue()->said, $this->catalogue()->filling) }}</x-design::strong>
            @if ($this->catalogue()->remedy !== '')
                <x-design::body>{{ __($this->catalogue()->remedy, $this->catalogue()->filling) }}</x-design::body>
            @endif
        </x-design::notice>
        @if ($this->catalogue()->reach === '')
            <x-operator::quiet-action :label="__('plugins.catalogue.again')" tap="catalogueAgain()" />
        @elseif ($this->changing() === $this->catalogue()->reach)
            {{-- The setting the stack named, through the review every
                 config key takes: nothing is switched without it. --}}
            <x-operator::the-review :key="$this->catalogue()->reach" :proposal="$this->proposalFor($this->catalogue()->reach)" :waiting="$this->landing !== null" :landing-said="$this->landingSaid" agreeing="agreeToTheChange" />
        @else
            <x-design::action :label="__('plugins.catalogue.turn_on')" :answers-to="__('config.what_would_happen_to', ['key' => $this->catalogue()->reach])" :tap="$this->tapping('change', $this->catalogue()->reach)" />
        @endif
    @endif
    @if ($this->waitsForTheNextFrame())
        <x-design::the-next-frame />
    @endif
    <native:outlined-text-input native:model="source" label="{{ __('plugins.where_from') }}" supporting="{{ __('plugins.source_is') }}" />
    @if ($this->nothingWasScanned())
        <x-design::notice tone="unknown">
            <x-design::strong>{{ __($this->whyNothingCameBack()) }}</x-design::strong>
            <x-design::body>{{ __($this->remedyForTheCamera()) }}</x-design::body>
        </x-design::notice>
    @endif
    <x-operator::note>{{ __('device.camera_reason') }}</x-operator::note>
    <x-design::action :label="__('plugins.scan_where_from')" tap="scanWhereItComesFrom()" tone="tonal" />
    {{-- The three shapes a source can take, under the one field. Which one
         was typed is the stack's to tell. --}}
    <x-operator::note>{{ __('plugins.source_catalogue') }}</x-operator::note>
    <x-operator::note>{{ __('plugins.source_directory') }}</x-operator::note>
    <x-operator::note>{{ __('plugins.source_git') }}</x-operator::note>
    <x-operator::offered-action :label="__('plugins.rehearse')" tap="rehearse()" :offer="$this->offered(ExtendingIt::Install)" />
    <x-operator::quiet-action :label="__('plugins.back')" tap="backToThePlugins()" />
@elseif ($this->answer()->install !== null)
    @if ($this->answer()->install->isAReading)
        {{-- Said before anything else: nothing below has happened. --}}
        <x-operator::emphasis>{{ __('plugins.rehearsal') }}</x-operator::emphasis>
    @else
        <x-operator::emphasis>{{ __($this->answer()->install->headline) }}</x-operator::emphasis>
    @endif

    <x-operator::the-plugins-account :install="$this->answer()->install" />

    @if ($this->answer()->install->agreeable)
        {{-- The yes to the install, apart from every approval above. --}}
        <x-operator::note>{{ __('plugins.approvals_apart') }}</x-operator::note>
        <x-operator::offered-action :label="__('plugins.install')" tap="agree()" :offer="$this->offered(ExtendingIt::Install)" />
        <x-operator::note>{{ __('plugins.inputs_elsewhere') }}</x-operator::note>
    @endif

    <x-operator::quiet-action :label="__('plugins.back')" tap="backToThePlugins()" />
@elseif ($this->answer()->update !== null)
    @if ($this->answer()->update->isAReading)
        <x-operator::emphasis>{{ __('plugins.rehearsal') }}</x-operator::emphasis>
    @else
        <x-operator::emphasis>{{ __($this->answer()->update->headline) }}</x-operator::emphasis>
    @endif
    <x-design::body>{{ __('plugins.from_to', ['from' => $this->answer()->update->from, 'to' => $this->answer()->update->to]) }}</x-design::body>

    {{-- Every service that stops, named before any does. --}}
    <x-operator::heading>{{ __('plugins.stops') }}</x-operator::heading>
    @forelse ($this->answer()->update->interrupts as $service)
        <x-design::body>{{ $service }}</x-design::body>
    @empty
        <x-operator::note>{{ __('plugins.stops_nothing') }}</x-operator::note>
    @endforelse

    @if ($this->answer()->update->stopped !== '')
        <x-operator::emphasis>{{ __('plugins.stopped', ['why' => $this->answer()->update->stopped]) }}</x-operator::emphasis>
    @endif
    @if ($this->answer()->update->restoredSaid !== null)
        {{-- Where the new version did not hold: which version is on the
             machine now, and how far putting it back got. --}}
        <x-operator::emphasis>{{ __($this->answer()->update->restoredSaid, ['version' => $this->answer()->update->restoredVersion]) }}</x-operator::emphasis>
    @endif

    {{-- The installed version's changes come off before the new version's
         go on, so what that would come to is said first. --}}
    <x-operator::heading>{{ __('plugins.what_goes_back') }}</x-operator::heading>
    <x-operator::what-going-back-came-to :report="$this->answer()->update->wentBack" />

    <x-operator::heading>{{ __('plugins.new_version') }}</x-operator::heading>
    <x-operator::the-plugins-account :install="$this->answer()->update->install" />

    <x-operator::the-files-reshipped :files="$this->answer()->update->reshipped" />

    @if ($this->answer()->update->install->agreeable)
        <x-operator::note>{{ __('plugins.approvals_apart') }}</x-operator::note>
        <x-operator::offered-action :label="__('plugins.update')" tap="agree()" :offer="$this->offered(ExtendingIt::Update)" />
        <x-operator::note>{{ __('plugins.inputs_elsewhere') }}</x-operator::note>
    @endif

    <x-operator::quiet-action :label="__('plugins.back')" tap="backToThePlugins()" />
@elseif ($this->answer()->removal !== null)
    @if ($this->answer()->removal->isAReading)
        <x-operator::emphasis>{{ __('plugins.rehearsal') }}</x-operator::emphasis>
    @else
        <x-operator::emphasis>{{ __($this->answer()->removal->headline) }}</x-operator::emphasis>
    @endif

    {{-- Every service it stops and every capability it leaves unfilled,
         said before the yes rather than found out after it. --}}
    <x-operator::heading>{{ __('plugins.stops') }}</x-operator::heading>
    @forelse ($this->answer()->removal->interrupts as $service)
        <x-design::body>{{ $service }}</x-design::body>
    @empty
        <x-operator::note>{{ __('plugins.stops_nothing') }}</x-operator::note>
    @endforelse

    <x-operator::heading>{{ __('plugins.unfilled') }}</x-operator::heading>
    @forelse ($this->answer()->removal->leaves as $unfilled)
        <x-design::body>{{ __('plugins.unfilled_line', ['capability' => $unfilled->target, 'plugin' => $unfilled->because]) }}</x-design::body>
    @empty
        <x-operator::note>{{ __('plugins.unfilled_nothing') }}</x-operator::note>
    @endforelse

    {{-- What taking its changes off the machine would come to, or came to. --}}
    <x-operator::heading>{{ __('plugins.what_goes_back') }}</x-operator::heading>
    <x-operator::what-going-back-came-to :report="$this->answer()->removal->wentBack" />

    @if ($this->answer()->removal->agreeable)
        <x-operator::offered-action :label="__('plugins.remove')" tap="agree()" :offer="$this->offered(ExtendingIt::Remove)" />
    @endif

    <x-operator::quiet-action :label="__('plugins.back')" tap="backToThePlugins()" />
@elseif ($this->answer()->proof !== null)
    <x-operator::emphasis>{{ __($this->answer()->proof->headline) }}</x-operator::emphasis>

    {{-- Which capability a pass would clear, and, where the proof did not
         hold, the stack's reason it stays unfilled. --}}
    <x-operator::heading>{{ $this->answer()->proof->isAReading ? __('plugins.a_pass_clears') : __('plugins.kept_against_it') }}</x-operator::heading>
    @forelse ($this->answer()->proof->kept as $answer)
        <x-design::body>{{ __('plugins.answered_out_of_contract', ['capability' => $answer->capability, 'operation' => $answer->operation, 'why' => $answer->why]) }}</x-design::body>
    @empty
        <x-operator::note>{{ __('plugins.nothing_kept_against_it') }}</x-operator::note>
    @endforelse

    <x-operator::the-proofs :proofs="$this->answer()->proof->proofs" />

    @if ($this->answer()->proof->agreeable)
        <x-operator::offered-action :label="__('plugins.prove')" tap="agree()" :offer="$this->offered(ExtendingIt::Prove)" />
    @endif

    <x-operator::quiet-action :label="__('plugins.back')" tap="backToThePlugins()" />
@elseif ($this->answer()->run !== null)
    {{-- Said before anything else: a rehearsal says nothing below has run. --}}
    <x-operator::emphasis>{{ __($this->answer()->run->headline, ['title' => $this->answer()->run->recipe->title]) }}</x-operator::emphasis>
    @if ($this->answer()->run->isAReading)
        <x-operator::the-recipe :recipe="$this->answer()->run->recipe" :agreeable="$this->answer()->run->agreeable" />
    @else
        @if ($this->answer()->run->why !== null && $this->answer()->run->why !== '')
            <x-design::body>{{ $this->answer()->run->why }}</x-design::body>
        @endif
        {{-- Every step in the recipe's order with what it came to, a step
             the stack held among them with its reason. --}}
        @forelse ($this->answer()->run->ran as $step)
            <x-operator::entry>
                <x-design::body>{{ __('plugins.run.step', ['step' => $step->step, 'to' => $step->to]) }}</x-design::body>
                <x-operator::emphasis>{{ __($step->cameSaid) }}</x-operator::emphasis>
                @if ($step->why !== '')
                    <x-operator::note>{{ $step->why }}</x-operator::note>
                @endif
            </x-operator::entry>
        @empty
            <x-operator::note>{{ __('plugins.no_steps') }}</x-operator::note>
        @endforelse
    @endif

    @if ($this->answer()->run->agreeable)
        {{-- The yes to the run, apart from every approval above; an
             approval given at install covers none of them. --}}
        <x-operator::note>{{ __('plugins.run.approvals_each_time') }}</x-operator::note>
        <x-operator::offered-action :label="__('plugins.run.run')" tap="agree()" :offer="$this->offered(ExtendingIt::Run)" />
    @endif

    <x-operator::quiet-action :label="__('plugins.back')" tap="backToThePlugins()" />
@else
    @if (! $this->catalogue()->listed && ! $this->catalogue()->isBeingRead())
        {{-- An unread catalogue never reads as every plugin up to date. --}}
        <x-operator::note>{{ __('plugins.catalogue.newer_unread', ['why' => __($this->catalogue()->said, $this->catalogue()->filling)]) }}</x-operator::note>
    @endif
    @if ($this->waitsForTheNextFrame())
        <x-design::the-next-frame />
    @endif
    @forelse ($this->answer()->installed as $at => $plugin)
        <x-operator::the-plugin :plugin="$plugin" />
        @if ($this->catalogue()->newerFor($plugin->id) !== '')
            <x-operator::note>{{ __('plugins.catalogue.newer', ['version' => $this->catalogue()->newerFor($plugin->id)]) }}</x-operator::note>
        @endif
        <x-operator::quiet-action :label="__('plugins.settings.open', ['name' => $plugin->name])" :goes="$this->goes()->aPluginsSettings($plugin->id)" />
        @if ($plugin->updatable)
            <x-operator::offered-action :label="__('plugins.update_it', ['name' => $plugin->name])" :tap="$this->tapping('actOnTheRow', 'update', $at)" :offer="$this->offered(ExtendingIt::Update)" />
        @else
            <x-operator::note>{{ __('plugins.no_source_to_update') }}</x-operator::note>
        @endif
        <x-operator::offered-action :label="__('plugins.remove_it', ['name' => $plugin->name])" :tap="$this->tapping('actOnTheRow', 'remove', $at)" :offer="$this->offered(ExtendingIt::Remove)" />
        @unless ($plugin->outOfContract === [])
            <x-operator::offered-action :label="__('plugins.prove_it', ['name' => $plugin->name])" :tap="$this->tapping('actOnTheRow', 'prove', $at)" :offer="$this->offered(ExtendingIt::Prove)" />
        @endunless
        {{-- Each recipe the plugin runs only when asked, with its run; one
             that asks the operator for a value says what it asks for and
             where it is run instead. --}}
        @forelse ($plugin->recipes as $place => $recipe)
            @if ($recipe->onDemand)
                <x-operator::entry>
                    <x-operator::emphasis>{{ $recipe->title }}</x-operator::emphasis>
                    @if ($recipe->why !== '')
                        <x-design::body>{{ $recipe->why }}</x-design::body>
                    @endif
                    @forelse ($recipe->asks as $ask)
                        <x-operator::note>{{ __('plugins.run.asks_for', ['ask' => $ask]) }}</x-operator::note>
                    @empty
                        <x-operator::offered-action :label="__('plugins.run.run_it', ['title' => $recipe->title])" :tap="$this->tapping('runOnTheRow', $at, $place)" :offer="$this->offered(ExtendingIt::Run)" />
                    @endforelse
                    @if ($recipe->asks !== [])
                        <x-operator::note>{{ __('plugins.run.elsewhere') }}</x-operator::note>
                    @endif
                </x-operator::entry>
            @endif
        @empty
            {{-- Nothing: the plugin declares no recipe. --}}
        @endforelse
    @empty
        {{-- The record answered and holds nothing. A record that could not be
             read never reaches this branch. --}}
        <x-design::body>{{ __('plugins.none') }}</x-design::body>
    @endforelse

    <x-operator::offered-action :label="__('plugins.install_one')" tap="installOne()" :offer="$this->offered(ExtendingIt::Install)" />
    <x-operator::quiet-action :label="__('health.ask_again')" tap="askAgain()" />
@endif
</x-operator::content>
@else
    {{-- Reading what is installed, or following the work, met something.
         After a yes, whether it happened could not be read, and that is said
         first. --}}
    @if ($this->agreed)
        <x-operator::content>
            <x-operator::emphasis>{{ __('plugins.unread_after_yes') }}</x-operator::emphasis>
        </x-operator::content>
    @endif
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->stands->went"
        :goes="$this->goes()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
