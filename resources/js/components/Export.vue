<script>
export default {
    props: ['collections', 'fileTypes', 'fieldHandles', 'userFieldHandles'],
    data() {
        return {
            type: 'collections',
            collectionHandle: null,
            fileType: 'xlsx',
            headers: true,
            excludedFields: [],
            errors: {},
            loading: false
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
        fieldHandlesForSelectedType() {
            if (this.type === 'users') {
                return this.userFieldHandles || []
            }

            return this.fieldHandles[this.collectionHandle] || []
        }
    },
    methods: {
        submit() {
            this.loading = true
            this.errors = {} // Reset errors on new submission
            // Submit the form and download the file
            fetch('/!/statamic-export/export', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify({
                    type: this.type,
                    collection_handle: this.collectionHandle,
                    file_type: this.fileType,
                    excluded_fields: this.excludedFields,
                    headers: this.headers
                })
            })
                .then(response => {
                    if (!response.ok) {
                        // If the server responded with a bad status, we throw an error and catch it later
                        return response.json().then(err => Promise.reject(err))
                    }
                    // If the response is ok, we proceed to process it as a blob
                    return response.blob()
                })
                .then(blob => {
                    const url = window.URL.createObjectURL(blob)
                    const a = document.createElement('a')
                    a.href = url
                    a.download = `${this.type === 'users' ? 'users' : this.collectionHandle}.${this.fileType}`
                    document.body.appendChild(a)
                    a.click()
                    a.remove()
                })
                .catch(error => (this.errors = error.errors))
                .finally(() => (this.loading = false))
        },
        typeChanged() {
            this.excludedFields = []
        }
    }
}
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="mb-2">Export</h1>
            <p class="text-sm text-gray">
                Choose what you'd like to export, pick your preferred file type and you're ready to go. It really is
                that easy.
            </p>
        </div>

        <div class="card">
            <form @submit.prevent="submit">
                <slot name="csrf"/>

                <!-- Type -->
                <div class="mb-4">
                    <div class="mb-2">
                        <label class="whitespace-nowrap">Export Type</label>
                        <p class="text-xs text-gray-700">Choose what you want to export.</p>
                    </div>
                    <div class="button-group-fieldtype-wrapper">
                        <div class="btn-group">
                            <button
                                v-for="option in [
                                    {
                                        value: 'collections',
                                        label: 'Collections'
                                    },
                                    { value: 'users', label: 'Users' }
                                ]"
                                :key="option.value"
                                type="button"
                                class="btn px-4"
                                :class="{ active: type === option.value }"
                                @click="
                                    type = option.value
                                    typeChanged()
                                "
                            >
                                {{ option.label }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Collection -->
                <div v-if="type === 'collections'" class="select-input-container mb-4">
                    <div class="mb-2">
                        <label class="whitespace-nowrap" for="collection_handle">Collection</label>
                        <p class="text-xs text-gray-700">Select the collection you want to export.</p>
                    </div>
                    <select-input v-model="collectionHandle" :options="collectionOptions" id="collection_handle"/>
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
                    <select-input
                        v-model="fileType"
                        :options="fileTypeOptions"
                        id="file_type"
                        style="min-width: 150px"
                    />
                    <p class="text-red-500 text-xs mt-1" v-if="errors.file_type">
                        {{ errors.file_type[0] }}
                    </p>
                </div>

                <!-- Excluded Fields -->
                <div class="select-input-container mb-4">
                    <div class="mb-2">
                        <label class="whitespace-nowrap" for="excluded_fields">Excluded Fields</label>
                        <p class="text-xs text-gray-700">
                            Select the fields you want to exclude from the export. If you don't select any fields, all
                            fields will be included.
                        </p>
                    </div>
                    <v-select
                        v-model="excludedFields"
                        id="excluded_fields"
                        :multiple="true"
                        :disabled="fieldHandlesForSelectedType.length === 0"
                        :options="fieldHandlesForSelectedType"
                    >
                    </v-select>
                </div>

                <!-- Include Headers -->
                <div class="mb-4" style="min-width: 150px">
                    <label for="headers">
                        <div class="mb-2">
                            <label class="whitespace-nowrap" for="headers">Include Headers</label>
                            <p class="text-xs text-gray-700">
                                Add a header row as the first row of your exported file which contains the display names
                                of the field for each column.
                            </p>
                        </div>
                        <toggle-input v-model="headers" id="headers"/>
                    </label>
                </div>

                <div class="flex">
                    <!-- Submit -->
                    <button
                        type="submit"
                        class="btn-primary"
                        :disabled="loading || (type === 'collections' && !collectionHandle)"
                    >
                        {{ __(`Export ${type === 'users' ? 'users' : 'collection'}`) }}
                    </button>
                    <!-- Spinner Container -->
                    <loading-graphic v-if="loading" class="ml-4" inline></loading-graphic>
                </div>
            </form>
        </div>
    </div>
</template>
