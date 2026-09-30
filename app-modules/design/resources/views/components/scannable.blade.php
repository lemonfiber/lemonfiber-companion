@if ($drawn)
<native:stack :width="$side" :height="$side" class="bg-theme-accent">
@forelse ($runs as $run)
<native:rect :width="$run->width" :height="$square" :translate-x="$run->across" :translate-y="$run->down" class="bg-theme-on-accent" />
@empty
<x-design::note>{{ $missing }}</x-design::note>
@endforelse
</native:stack>
@else
<x-design::note>{{ $missing }}</x-design::note>
@endif
