{{-- Closes the column and the container `content` opened, after the slot. --}}
</native:column>
@if ($pulled !== null)
</native:refreshable>
@else
</native:scroll-view>
@endif
