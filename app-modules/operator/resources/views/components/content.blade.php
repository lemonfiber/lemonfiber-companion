{{-- Opened before the slot and closed by `content-closes` after it, so the
     slot is drawn inside this column, and the column inside a scroll view: a
     screen taller than the phone is read by scrolling it, not cut off at the
     fold. It opens at its end when the screen asked for that, and at its top
     otherwise. --}}
<native:scroll-view class="w-full" :scroll-anchor="$anchor">
<native:column class="w-full gap-4 px-6 py-4">
