@extends('statamic::layout')
@section('title', __('Export'))

@section('content')

    <export
        :collections='@json(\Statamic\Facades\Collection::all())'
        :file-types='@json(\Doefom\StatamicExport\Enums\FileType::all())'
    >
        <template #csrf>@csrf</template>
    </export>

@stop
