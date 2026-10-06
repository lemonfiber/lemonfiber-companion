{{-- One row of what stopped moving, as a port row: the item or shared cause
     as its name, and the kind, how many share it, the service's words and how
     long on the line under it. A row standing for one item leads to where that
     item got to; one standing for several leads nowhere, because no single
     trace is its. --}}
<x-operator::port-row :tone="$tone" :name="$row->name" :said="$said" :goes="$trace" :answers-to="$named" />
