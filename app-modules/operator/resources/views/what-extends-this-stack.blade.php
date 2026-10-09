@use('Modules\Kernel\Api\ExtendingIt')
<x-wayfinding::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-operator::heading>{{ __('plugins.heading') }}</x-operator::heading>

@if ($this->answer()->isWorking)
    <x-design::body>{{ __($this->answer()->workingSaid) }}</x-design::body>
@elseif ($this->answer()->refused !== null)
    {{-- The stack's answer, in its words: a source holding no plugin, a
         plugin this build refuses, a value left unapproved or an offer that
         moved. Nothing is offered to do beneath it. --}}
    <x-operator::emphasis>{{ __($this->answer()->refusedSaid) }}</x-operator::emphasis>
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

    <x-operator::the-plugins-account :install="$this->answer()->install" />

    @if ($this->answer()->install->agreeable)
        {{-- The yes to the install, apart from every approval above. --}}
        <x-operator::note>{{ __('plugins.approvals_apart') }}</x-operator::note>
        <x-operator::offered-action label="{{ __('plugins.install') }}" tap="agree()" :offer="$this->offered(ExtendingIt::Install)" />
        <x-operator::note>{{ __('plugins.inputs_elsewhere') }}</x-operator::note>
    @endif

    <x-operator::quiet-action label="{{ __('plugins.back') }}" tap="backToThePlugins()" />
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
    @if ($this->answer()->update->restoredSaid !== '')
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

    @if ($this->answer()->update->install->agreeable)
        <x-operator::note>{{ __('plugins.approvals_apart') }}</x-operator::note>
        <x-operator::offered-action label="{{ __('plugins.update') }}" tap="agree()" :offer="$this->offered(ExtendingIt::Update)" />
        <x-operator::note>{{ __('plugins.inputs_elsewhere') }}</x-operator::note>
    @endif

    <x-operator::quiet-action label="{{ __('plugins.back') }}" tap="backToThePlugins()" />
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
        <x-operator::offered-action label="{{ __('plugins.remove') }}" tap="agree()" :offer="$this->offered(ExtendingIt::Remove)" />
    @endif

    <x-operator::quiet-action label="{{ __('plugins.back') }}" tap="backToThePlugins()" />
@else
    @forelse ($this->answer()->installed as $at => $plugin)
        <x-operator::the-plugin :plugin="$plugin" />
        @if ($plugin->updatable)
            <x-operator::offered-action label="{{ __('plugins.update_it', ['name' => $plugin->name]) }}" tap="updateOne({{ $at }})" :offer="$this->offered(ExtendingIt::Update)" />
        @else
            <x-operator::note>{{ __('plugins.no_source_to_update') }}</x-operator::note>
        @endif
        <x-operator::offered-action label="{{ __('plugins.remove_it', ['name' => $plugin->name]) }}" tap="removeOne({{ $at }})" :offer="$this->offered(ExtendingIt::Remove)" />
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
         After a yes, whether it happened could not be read, and that is said
         first. --}}
    @if ($this->answer()->afterTheYes)
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
