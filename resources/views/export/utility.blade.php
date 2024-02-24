@extends('statamic::layout')
@section('title', __('Export'))

@section('content')
    <div class="mb-6">
        <h1 class="mb-2">{{ __('Export') }}</h1>
        <p class="text-sm text-gray">{{ __("Choose the collection you'd like to export, pick your preferred file type and you're ready to go. It really is that easy.") }}</p>
    </div>

    <div class="card">
        <form method="POST" action="{{ route('statamic.statamic-export.export') }}">
            @csrf

            <div class="flex gap-3 mb-4">

                <!-- Collection -->
                <div class="select-input-container relative w-full">
                    <label class="mb-2 whitespace-nowrap" for="collection_handle">{{ __('Collection') }}</label>
                    <select class="pr-4" id="collection_handle" name="collection_handle">
                        <option value="" selected disabled>-</option>
                        @foreach(\Statamic\Facades\Collection::all() as $collection)
                            <option value="{{ $collection->id() }}">{{ $collection->title() }}</option>
                        @endforeach
                    </select>
                    @error('collection_handle')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- File Type -->
                <div class="select-input-container">
                    <label class="mb-2 whitespace-nowrap" for="file_type">{{ __('File Type') }}</label>
                    <select class="pr-4" id="file_type" name="file_type" style="min-width: 70px">
                        @foreach(\Doefom\StatamicExport\Enums\FileType::all() as $fileType)
                            <option value="{{ $fileType }}">{{ strtoupper($fileType) }}</option>
                        @endforeach
                    </select>
                    @error('file_type')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Include Headers -->
                <div class="select-input-container">
                    <label class="mb-2 whitespace-nowrap" for="headers">{{ __('Include Headers') }}</label>
                    <select class="pr-4" id="headers" name="headers" style="min-width: 70px">
                        <option value="true" selected>{{ __('Yes') }}</option>
                        <option value="false">{{ __('No') }}</option>
                    </select>
                </div>

            </div>

            <!-- Submit -->
            <button type="submit" class="btn-primary">{{ __('Export collection') }}</button>
        </form>
    </div>
@stop
