@if ($said->newest !== null)
    @if ($said->showingAll)
        @forelse ($said->lines as $line)
            <x-design::verbatim>{{ $line }}</x-design::verbatim>
        @empty
            {{-- Drawn only once the job has said a line, so there is always one. --}}
        @endforelse

        <x-design::note>{{ __('stacks.what_it_said.kept', ['count' => $said->kept]) }}</x-design::note>
        @if ($said->letGo > 0)
            <x-design::note>{{ trans_choice('stacks.what_it_said.let_go', $said->letGo) }}</x-design::note>
        @endif

        <x-design::action :label="__('stacks.what_it_said.show_the_newest')" :tap="$said->showTheNewest" tone="tonal" />
    @else
        <x-design::verbatim>{{ $said->newest }}</x-design::verbatim>
        <x-design::action :label="__('stacks.what_it_said.show_all')" :tap="$said->showAll" tone="tonal" />
    @endif
@endif
