<script>
export default {
    props: ['collections', 'fileTypes'],
    data() {
        return {
            collectionHandle: null,
            fileType: 'xlsx',
            headers: true,
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
        }
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

                <div class="lg:flex gap-3 mb-4">

                    <!-- Collection -->
                    <div class="select-input-container relative w-full">
                        <label class="mb-2 whitespace-nowrap" for="collection_handle">Collection</label>
                        <select-input v-model="collectionHandle"
                                      :options="collectionOptions"/>
                        <p class="text-red-500 text-xs mt-1" v-if="errors.collection_handle">
                            {{ errors.collection_handle[0] }}
                        </p>
                    </div>

                    <!-- File Type -->
                    <div class="select-input-container">
                        <label class="mb-2 whitespace-nowrap" for="file_type">File Type</label>
                        <select-input v-model="fileType"
                                      :options="fileTypeOptions"
                                      id="file_type"
                                      style="min-width: 150px"/>
                    </div>

                    <!-- Include Headers -->
                    <div style="min-width: 150px">
                        <label for="headers">
                            <div class=whitespace-nowrap>Include Headers</div>
                            <toggle-input v-model="headers" id="headers"/>
                        </label>
                    </div>

                </div>

                <div class="flex">
                    <!-- Submit -->
                    <button type="submit" class="btn-primary" :disabled="loading">{{ __('Export collection') }}</button>
                    <!-- Spinner Container -->
                    <loading-graphic v-if="loading" class="ml-4" inline></loading-graphic>
                </div>

            </form>
        </div>
    </div>

</template>
