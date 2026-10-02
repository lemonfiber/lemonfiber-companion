{{-- Files the operator edited, which the stack left as they set them. Drawn
     as a fact and not as a problem: no tone, no remedy and nothing offered,
     because a file somebody edited is theirs. Where the file differs in lines a
     diff can show, what lemonfiber would have written is shown under it, with
     what the marks mean. --}}
@forelse ($edits as $edit)
    <x-operator::entry>
        @if ($loop->first)
            <x-design::heading>{{ __('stacks.edits.heading') }}</x-design::heading>
        @endif

        <x-design::body>{{ __('stacks.edits.kept', ['path' => $edit->path]) }}</x-design::body>

        @forelse ($edit->lines as $line)
            @if ($loop->first)
                <x-operator::note>{{ __('stacks.edits.would_change') }}</x-operator::note>
            @endif

            <x-design::verbatim>{{ __($line->said, ['line' => $line->line]) }}</x-design::verbatim>

            @if ($loop->last)
                <x-operator::note>{{ __('stacks.edits.legend') }}</x-operator::note>
            @endif
        @empty
            {{-- A file whose difference no line can show: that it is kept is
                 the whole of what there is to say. --}}
        @endforelse
    </x-operator::entry>
@empty
    {{-- No file the operator edited, which is the ordinary case and nothing to
         say about the run. --}}
@endforelse
