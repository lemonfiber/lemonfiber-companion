@if ($goes !== '')
<native:list-item native:key="{{ $named }}" headline="{{ $headline }}" :supporting="$supporting === '' ? null : $supporting" trailingIcon="chevron_right" trailingIconIos="chevron.right" a11y-label="{{ $named }}" @navigate="$goes" />
@elseif ($tap !== '')
<native:list-item native:key="{{ $named }}" headline="{{ $headline }}" :supporting="$supporting === '' ? null : $supporting" trailingIcon="chevron_right" trailingIconIos="chevron.right" a11y-label="{{ $named }}" @press="{{ $tap }}" />
@else
<native:list-item headline="{{ $headline }}" :supporting="$supporting === '' ? null : $supporting" :trailingText="$trailing === '' ? null : $trailing" />
@endif
