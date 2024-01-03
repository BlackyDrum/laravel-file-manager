<script setup lang="ts">
import { onMounted, ref } from 'vue';


import Button from 'primevue/button';


</script>

<template>
    <div class="flex">
        <div class="flex gap-3 ml-auto">
            <Button class="text-black border-gray-300 bg-white font-medium" label="Share" icon="pi pi-share-alt" />
            <Button class="font-medium" label="Download" icon="pi pi-download" />
            <Button class="text-black border-gray-300 bg-white font-medium" label="Delete" icon="pi pi-trash" />
        </div>
    </div>
    <div>

    </div>
</template>
