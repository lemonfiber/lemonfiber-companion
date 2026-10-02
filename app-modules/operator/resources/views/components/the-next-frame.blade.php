{{-- Asks for the next frame at once, for a screen that has read its stack on
     this one and owes a second reading: a frame reads a stack once. Sixteen
     milliseconds is one frame at sixty a second. It draws nothing. --}}
<native:column class="w-full" native:poll="16ms" />
