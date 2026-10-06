@if ($goes !== '')
<native:list-item native:key="{{ $named }}" headline="{{ $headline }}" :leadingIcon="$glyph" :leadingIconIos="$iosGlyph" :leadingIconColor="$colour" :supporting="$supporting === '' ? null : $supporting" :badge="$badge === '' ? null : $badge" trailingIcon="chevron_right" trailingIconIos="chevron.right" a11y-label="{{ $named }}" @navigate="$goes, $carries" />
@elseif ($tap !== '')
<native:list-item native:key="{{ $named }}" headline="{{ $headline }}" :leadingIcon="$glyph" :leadingIconIos="$iosGlyph" :leadingIconColor="$colour" :supporting="$supporting === '' ? null : $supporting" :badge="$badge === '' ? null : $badge" trailingIcon="chevron_right" trailingIconIos="chevron.right" a11y-label="{{ $named }}" @press="{{ $tap }}" />
@else
<native:list-item headline="{{ $headline }}" :leadingIcon="$glyph" :leadingIconIos="$iosGlyph" :leadingIconColor="$colour" :supporting="$supporting === '' ? null : $supporting" :trailingText="$trailing === '' ? null : $trailing" />
@endif
