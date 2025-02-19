@extends('statamic::layout')
@section('title', __('Export'))

@section('content')

    <export
        :collections='@json($collections)'
        :field-handles='@json($fieldHandles)'
        :user-field-handles='@json($userFieldHandles)'
        :file-types='@json($fileTypes)'
    >
        <template #csrf>@csrf</template>
    </export>

@stop
