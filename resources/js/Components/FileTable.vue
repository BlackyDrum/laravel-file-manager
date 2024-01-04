<script setup lang="ts">
import { onMounted, ref, defineProps } from 'vue';
import {usePage} from "@inertiajs/vue3";

import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';


interface File {
    name: string,
    size: number,
    owner_id: number,
    updated_at: string,
    created_at: string
}

defineProps<{
    files: File
}>();

const page = usePage();

const tableHeadBackground = ref("#DADADA");
const selectedFiles = ref([]);

onMounted(() => {
    files.value = page.props.files;

    for (let i = 0; i < files.value.length; i++) {
        files.value[i]['modified'] = `${calculateLastModified(files.value[i].updated_at)} days ago`;
    }
})

const calculateLastModified = (lastModified) => {
    const lastModifiedDate = new Date(lastModified);
    const currentDate = new Date();

    return Math.round(Math.abs(currentDate - lastModifiedDate) / (1000 * 60 *60 * 24))
}

const onRowReorder = (event) => {
    files.value = event.value;
}

// Source: https://stackoverflow.com/questions/15900485/correct-way-to-convert-size-in-bytes-to-kb-mb-gb-in-javascript
const formatBytes = (bytes, decimals = 2) => {
    if (!+bytes) return '0.00 B'

    const k = 1024
    const dm = decimals < 0 ? 0 : decimals
    const sizes = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB']

    const i = Math.floor(Math.log(bytes) / Math.log(k))

    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`
}


const files = ref();

</script>

<template>
    <div class="flex">
        <div class="flex gap-3 ml-auto">
            <Button class="text-black border-gray-300 bg-white font-medium" label="Share" icon="pi pi-share-alt" />
            <Button class="font-medium" label="Download" icon="pi pi-download" />
            <Button class="text-black border-gray-300 bg-white font-medium" label="Delete" icon="pi pi-trash" />
        </div>
    </div>
    <div class="mt-4">
        <DataTable class="font-sans shadow-lg" @rowReorder="onRowReorder" v-model:selection="selectedFiles" :value="files" tableStyle="min-width: 50rem">
            <Column rowReorder :headerStyle="{background: tableHeadBackground, width: '3rem'}" />
            <Column selectionMode="multiple" :headerStyle="{background: tableHeadBackground}"></Column>
            <Column field="name" header="Name" sortable :headerStyle="{background: tableHeadBackground}"></Column>
            <Column field="owner_id" header="Owner" sortable :headerStyle="{background: tableHeadBackground}">
                <template #body="{data, field}">
                    {{data["username"]}} {{data[field] === $page.props.auth.user.id ? '(me)' : data[field]}}
                </template>
            </Column>
            <Column field="modified" header="Last Modified" sortable :headerStyle="{background: tableHeadBackground}"></Column>
            <Column field="size" header="Size" sortable :headerStyle="{background: tableHeadBackground}">
                <template #body="{data, field}">
                    {{formatBytes(data[field])}}
                </template>
            </Column>
        </DataTable>
    </div>
</template>

<style>
.p-highlight {
    background-color: #D4DBFF;
    color: black;
}

.p-checkbox .p-highlight  {
    background-color: #007BFF;
}
</style>
