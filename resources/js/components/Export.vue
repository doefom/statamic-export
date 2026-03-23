<script setup>
import { Head } from '@statamic/cms/inertia';
import { Button, Header, PublishContainer } from '@statamic/cms/ui';
import { ref } from 'vue'; 

const props = defineProps({
    title: String,
    blueprint: Object,
    values: Object,
    meta: Object,
    excludedFieldsOptions: Object,
    errors: Object
});
const values = ref(props.values);
let downloading = ref(false);
let currentType = '';
let currentCollection = '';

function submit() {
    downloading.value = true;
    fetch('/!/statamic-export/export', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': StatamicConfig.csrfToken
        },
        body: JSON.stringify({
            type: props.values.type,
            collection_handle: props.values.collection_handle,
            file_type: props.values.file_type,
            excluded_fields: props.values.excluded_fields,
            headers: props.values.headers
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
        a.download = `${props.values.type === 'users' ? 'users' : props.values.collection_handle}.${props.values.file_type}`
        document.body.appendChild(a)
        a.click()
        a.remove()
    })
    .catch((e) => {
        props.errors = e.errors;
    })
    .finally(() => {
        downloading.value = false;
    })
}

function updateValues(values)
{
    let update = false;
    props.errors = {};

    if (
        currentType !== props.values.type || 
        currentCollection !== props.values.collection_handle
    ) {
        update = true;
    }

    if (update) {
        props.meta.excluded_fields.options = [];
        props.values.excluded_fields = [];

        if (props.values.type === 'users') {
            props.meta.excluded_fields.options = props.excludedFieldsOptions['users'];
        } else if (props.values.collection_handle) {
            props.meta.excluded_fields.options = props.excludedFieldsOptions[props.values.collection_handle];
        } else {
            props.meta.excluded_fields.options = [];
        }

        update = false;
    }

    currentType = props.values.type;
    currentCollection = props.values.collection_handle;
}
</script>

<template>
    <Head :title="title" />
    <Header :title="title" icon="download">
        <Button 
            type="submit" 
            variant="primary" 
            :text="__(`Export ${props.values.type === 'users' ? 'users' : 'collection'}`)" 
            :disabled="downloading" 
            @click="submit" 
        /> 
	</Header>

    <PublishContainer 
        ref="container"
        v-model="values"
        :blueprint="blueprint"
        :meta="meta"
        :errors="errors"
        @update:modelValue="updateValues"
    />
</template>