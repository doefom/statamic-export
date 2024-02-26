@extends('statamic::layout')
@section('title', __('Export'))

@section('content')

    <export
        :collections='@json($collections)'
        :file-types='@json($fileTypes)'
    >
        <template #csrf>@csrf</template>
    </export>

@stop
