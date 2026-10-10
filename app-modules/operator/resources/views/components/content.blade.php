{{-- Opened before the slot and closed by `content-closes` after it, so the
     slot is drawn inside this column, and the column inside a scroll view: a
     screen taller than the phone is read by scrolling it, not cut off at the
     fold. It opens at its end when the screen asked for that, and at its top
     otherwise.

     The scroll view is as tall as the screen leaves it (`h-full`), not as tall
     as what it holds. Its parent is the column the screen's top-level elements
     are drawn in, and that column gives a child with no height of its own the
     height of its content: on iOS a scroll view that tall is never shorter
     than what it scrolls, so it does not move.

     Where pulling it down asks again, the container is the one that is
     pulled: it scrolls as the scroll view does. --}}
@if ($pulled !== null)
<native:refreshable class="w-full h-full" @refresh="{{ $pulled }}">
@else
<native:scroll-view class="w-full h-full" :scroll-anchor="$anchor">
@endif
<native:column class="w-full gap-4 px-6 py-4">
