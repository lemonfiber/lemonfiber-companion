<native:column>
@forelse ($rows as $row)
<native:row class="bg-theme-accent">
@forelse ($row as $dark)
@if ($dark)
<native:rect :width="4" :height="4" class="bg-theme-on-accent" />
@else
<native:rect :width="4" :height="4" class="bg-theme-accent" />
@endif
@empty
<x-design::note>{{ $missing }}</x-design::note>
@endforelse
</native:row>
@empty
<x-design::note>{{ $missing }}</x-design::note>
@endforelse
</native:column>
