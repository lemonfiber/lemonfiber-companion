@use('Modules\Operator\Api\HowTheColumnScrolls')
<x-operator::screen-opens :title="__('household.guide.title')" :back="$this->hasAWayBack()" />

<x-operator::content :scrolls="HowTheColumnScrolls::PulledDownToReadAgain">

@if ($this->guide()->cameBack)
    <x-household::beside-a-channel :came="$this->guide()->came" />

    @if ($this->guide()->rows === [])
        <x-design::section>
            <x-design::row :headline="__('household.guide.none')" :supporting="__('household.guide.none_action')" />
        </x-design::section>
    @else
        {{-- The channels stay put on the left; the hours pan sideways, every
             row with them, so a column of the grid is one moment. Down the
             channels is the screen's own scroll. --}}
        <native:row class="w-full gap-2">
            <native:column class="w-28 gap-1">
                <native:column class="h-8" />
                @forelse ($this->guide()->rows as $row)
                <native:pressable class="w-28 h-16 min-h-12 justify-center" a11y-label="{{ __('household.guide.channel_named', ['channel' => $row->shown]) }}" :press-opacity="0.6" @press="{{ $row->tap }}">
                    <native:text class="text-[12] font-medium text-theme-muted" font="DMMono-Medium" :fixed-size="true" :max-lines="1">{{ $row->number }}</native:text>
                    <native:text class="text-[15] font-extrabold text-theme-text" font="GolosText-ExtraBold" :fixed-size="true" :max-lines="2">{{ $row->name }}</native:text>
                </native:pressable>
                @empty
                {{-- Not reached: a guide with no channel is said above instead. --}}
                @endforelse
            </native:column>
            <native:scroll-view class="flex-1" horizontal :shows-indicators="false">
            <native:column class="gap-1">
                <native:row class="h-8">
                    @forelse ($this->guide()->marks as $mark)
                    <native:text class="text-[12] font-medium text-theme-muted" font="DMMono-Medium" :width="$mark->width" :fixed-size="true" :max-lines="1">{{ $mark->time }}</native:text>
                    @empty
                    {{-- No programme reaches past now, so no half hour is drawn. --}}
                    @endforelse
                </native:row>
                @forelse ($this->guide()->rows as $row)
                <native:row class="h-16">
                    @forelse ($row->cells as $cell)
                        @if ($cell->isAGap())
                        <native:column class="h-16" :width="$cell->width" />
                        @else
                        <x-household::guide-cell :cell="$cell" :channel="$row->name" />
                        @endif
                    @empty
                    {{-- A channel whose guide holds nothing across the window: an empty row beside its name. --}}
                    @endforelse
                </native:row>
                @empty
                {{-- Not reached, for the channel column's reason. --}}
                @endforelse
            </native:column>
            </native:scroll-view>
        </native:row>
    @endif

    <x-design::action :label="__('household.ask_again')" tap="askAgain()" tone="tonal" />
@elseif ($this->guide()->stopped !== null)
    <x-design::notice tone="unknown">
        <x-household::what-stood-in-the-way :stopped="$this->guide()->stopped" />
    </x-design::notice>

    <x-design::action :label="__('household.ask_again')" tap="askAgain()" />
    @if ($this->guide()->stopped->isPutRightInTheAppsSettings())
        <x-design::action :label="__('connection.open_settings')" tap="openTheAppsSettings()" />
        @if ($this->theSettingsWouldNotOpen)
            <x-design::note>{{ __('connection.settings_would_not_open') }}</x-design::note>
        @endif
    @endif
@else
    <x-design::body>{{ __('household.signed_out') }}</x-design::body>
    <x-design::action :label="__('connection.sign_in')" :goes="$this->signIn()" />
@endif
</x-operator::content>

{{-- A later programme's title, times and certificate, with nothing to press:
     the guide neither tunes ahead nor reminds. --}}
<native:bottom-sheet :visible="$this->guide()->about !== null" detents="medium" @dismiss="closeTheProgramme()">
    <native:column class="w-full gap-4 p-4">
        @if ($this->guide()->about !== null)
            <x-design::title>{{ $this->guide()->about->title }}</x-design::title>
            <x-design::body>{{ $this->guide()->about->channel }}</x-design::body>
            <x-design::note>{{ $this->guide()->about->times }}</x-design::note>
            @if ($this->guide()->about->certificate !== '')
                <x-design::note>{{ __('household.title.certificate', ['certificate' => $this->guide()->about->certificate]) }}</x-design::note>
            @endif
        @endif
    </native:column>
</native:bottom-sheet>

<x-household::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
