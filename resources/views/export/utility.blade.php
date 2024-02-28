@extends('statamic::layout')
@section('title', __('Export'))

@section('content')

    <export
        :collections='@json($collections)'
        :field-handles='@json($fieldHandles)'
        :file-types='@json($fileTypes)'
    >
        <template #csrf>@csrf</template>
    </export>

@stop
