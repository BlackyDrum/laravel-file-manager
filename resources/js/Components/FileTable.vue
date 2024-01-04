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

    return Math.ceil(Math.abs(currentDate - lastModifiedDate) / (1000 * 60 *60 * 24))
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
        <DataTable class="font-medium" v-model:selection="selectedFiles" :value="files" tableStyle="min-width: 50rem">
            <Column selectionMode="multiple" :headerStyle="{background: tableHeadBackground}"></Column>
            <Column field="name" header="Name" :headerStyle="{background: tableHeadBackground}"></Column>
            <Column field="owner_id" header="Owner" :headerStyle="{background: tableHeadBackground}"></Column>
            <Column field="modified" header="Last Modified" :headerStyle="{background: tableHeadBackground}"></Column>
            <Column field="size" header="Size" :headerStyle="{background: tableHeadBackground}"></Column>
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