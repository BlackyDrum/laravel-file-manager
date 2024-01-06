<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

import FileTable from '@/Components/FileTable.vue';
import {ref} from "vue";

const filterInput = ref("");

const handleFilterChange = input => {
    filterInput.value = input;
}

</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout @filterInput="(n) => handleFilterChange(n)">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        </template>

        <div class="my-12">
            <FileTable :filterInput="filterInput"/>
        </div>
    </AuthenticatedLayout>
</template>
