<script setup lang="ts">
import { onMounted, ref } from 'vue';

import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

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
        <DataTable tableStyle="min-width: 50rem">
            <Column field="name" header="Name"></Column>
            <Column field="owner" header="Owner"></Column>
            <Column field="last_modified" header="Last Modified"></Column>
            <Column field="size" header="Size"></Column>
        </DataTable>
    </div>
</template>
