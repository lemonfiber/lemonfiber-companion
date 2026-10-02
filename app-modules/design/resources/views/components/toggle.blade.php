<native:column class="w-full px-4 py-2">
<native:toggle native:key="{{ $named }}" label="{{ $label }}" a11y-label="{{ $named }}" :value="$on" @change="{{ $tap }}" />
</native:column>
