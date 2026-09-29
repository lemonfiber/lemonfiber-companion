{{-- Opened before the slot and closed by `content-closes` after it, so the
     slot is drawn inside this column, and the column inside a scroll view: a
     screen taller than the phone is read by scrolling it, not cut off at the
     fold. --}}
<native:scroll-view class="w-full">
<native:column class="w-full gap-4 px-6 py-4">
