<script>
export default {
    props: ['collections', 'fileTypes', 'fieldHandles'],
    data() {
        return {
            collectionHandle: null,
            fileType: 'xlsx',
            headers: true,
            excludedFields: [],
            errors: {},
            loading: false,
        }
    },
    computed: {
        collectionOptions() {
            return this.collections.map(collection => ({
                label: collection.title,
                value: collection.handle
            }))
        },
        fileTypeOptions() {
            return this.fileTypes.map(fileType => ({
                label: fileType.toUpperCase(),
                value: fileType
            }))
        },
        fieldHandlesForSelectedCollection() {
            return this.fieldHandles[this.collectionHandle] || []
        },
    },
    methods: {
        submit() {
            this.loading = true;
            this.errors = {}; // Reset errors on new submission
            // Submit the form and download the file
            fetch('/!/statamic-export/export', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify({
                    collection_handle: this.collectionHandle,
                    file_type: this.fileType,
                    excluded_fields: this.excludedFields,
                    headers: this.headers
                })
            })
                .then(response => {
                    if (!response.ok) {
                        // If the server responded with a bad status, we throw an error and catch it later
                        return response.json().then(err => Promise.reject(err));
                    }
                    // If the response is ok, we proceed to process it as a blob
                    return response.blob();
                })
                .then(blob => {
                    const url = window.URL.createObjectURL(blob)
                    const a = document.createElement('a')
                    a.href = url
                    a.download = `${this.collectionHandle}.${this.fileType}`
                    document.body.appendChild(a)
                    a.click()
                    a.remove()
                })
                .catch(error => this.errors = error.errors)
                .finally(() => this.loading = false);
        }
    }
}
</script>

<template>

    <div>
        <div class="mb-6">
            <h1 class="mb-2">Export</h1>
            <p class="text-sm text-gray">
                Choose the collection you'd like to export, pick your preferred file type and you're ready to go. It
                really is that easy.
            </p>
        </div>

        <div class="card">
            <form @submit.prevent="submit">
                <slot name="csrf"/>

                <!-- Collection -->
                <div class="select-input-container mb-4">
                    <div class="mb-2">
                        <label class="whitespace-nowrap" for="collection_handle">Collection</label>
                        <p class="text-xs text-gray-700">Select the file type for the export.</p>
                    </div>
                    <select-input v-model="collectionHandle"
                                  :options="collectionOptions"
                                  id="collection_handle"/>
                    <p class="text-red-500 text-xs mt-1" v-if="errors.collection_handle">
                        {{ errors.collection_handle[0] }}
                    </p>
                </div>

                <!-- File Type -->
                <div class="select-input-container mb-4">
                    <div class="mb-2">
                        <label class="whitespace-nowrap" for="file_type">File Type</label>
                        <p class="text-xs text-gray-700">Select the file type for the export.</p>
                    </div>
                    <select-input v-model="fileType"
                                  :options="fileTypeOptions"
                                  id="file_type"
                                  style="min-width: 150px"/>
                    <p class="text-red-500 text-xs mt-1" v-if="errors.file_type">
                        {{ errors.file_type[0] }}
                    </p>
                </div>

                <!-- Excluded Fields -->
                <div class="select-input-container mb-4">
                    <div class="mb-2">
                        <label class="whitespace-nowrap" for="excluded_fields">Excluded Fields</label>
                        <p class="text-xs text-gray-700">Select a collection to choose the handles of the fields you want to exclude from the export.</p>
                    </div>
                    <v-select v-model="excludedFields"
                              id="excluded_fields"
                              :multiple="true"
                              :disabled="fieldHandlesForSelectedCollection.length === 0"
                              :options="fieldHandlesForSelectedCollection">
                    </v-select>
                </div>

                <!-- Include Headers -->
                <div class="mb-4" style="min-width: 150px">
                    <label for="headers">
                        <div class="mb-2">
                            <label class="whitespace-nowrap" for="headers">Include Headers</label>
                            <p class="text-xs text-gray-700">Add a header row as the first row of your exported file
                                which contains the display names of the field for each column.</p>
                        </div>
                        <toggle-input v-model="headers" id="headers"/>
                    </label>
                </div>

                <div class="flex">
                    <!-- Submit -->
                    <button type="submit"
                            class="btn-primary"
                            :disabled="loading || !collectionHandle">
                        {{ __('Export collection') }}
                    </button>
                    <!-- Spinner Container -->
                    <loading-graphic v-if="loading" class="ml-4" inline></loading-graphic>
                </div>

            </form>
        </div>
    </div>

</template>
