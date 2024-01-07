<script setup lang="ts">
import {computed, onMounted, onUpdated, ref, watch, defineEmits} from 'vue';
import {router, usePage, useForm} from "@inertiajs/vue3";

import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import Dropdown from '@/Components/Dropdown.vue';

import Button from 'primevue/button';
import InputText from 'primevue/InputText';
import Dialog from 'primevue/dialog';
import FileUpload from 'primevue/fileupload';
import Badge from 'primevue/Badge';
import ProgressBar from 'primevue/ProgressBar';
import Toast from 'primevue/toast';
import {useToast} from "primevue/usetoast";

////////////////////////////////////////////////

const page = usePage();
const toast = useToast();

const emit = defineEmits(['filterInput']);

const showingNavigationDropdown = ref(false);
const showFileUploadDialog = ref(false);
const filterInput = ref(null);

const files = ref([]);
const maxFileSize = ref(import.meta.env.VITE_MAX_FILE_SIZE);
const fileForm = useForm({
    files: files.value
});

const fileTypes = ref(['zip', 'tar', 'rar', 'gzip', '7z',
    'mp3', 'mp4', 'mpeg', 'wav', 'ogg', 'opus',
    'jpeg', 'png', 'gif', 'bmp', 'tiff', 'svg',
    'css', 'html', 'php', 'c', 'cpp', 'h', 'hpp', 'js', 'java', 'py',
    'txt', 'pdf', 'log',
    'webm', 'mpeg4', '3gpp', 'mov', 'avi', 'wmv', 'flv', 'ogg',
    'xls', 'xlsx', 'ppt', 'pptx', 'doc', 'docx', 'xps']);

const getFileTypes = computed(() => {
    fileTypes.value = fileTypes.value.map(v => {
        return '.' + v;
    })

    return fileTypes.value.join(', ');
})


const menuItems = ref([
    { label: "Dashboard", url: "dashboard" },
]);



const onRemoveTemplatingFile = (file, removeFileCallback, index) => {
    files.value.splice(index, 1)
    fileForm.cancel();
};

const onSelectedFiles = (event) => {
    files.value = event.files;

    for (let i = 0; i < files.value.length; i++) {
        files.value[i].pending = true;
    }
};

const uploadEvent = () => {
    fileForm.files = files.value;

    fileForm.post('/upload', {
        onError: (error) => {
            for (const e in error) {
                toast.add({ severity: 'error', summary: 'Error', detail: error[e], life: 6000 });
            }
        },
        onSuccess: (response) => {
            files.value.splice(0);
            toast.add({ severity: 'success', summary: 'Success', detail: 'Files uploaded', life: 3000 });
        }
    })
};

const formatBytes = (bytes, decimals = 2) => {
    if (!+bytes) return '0.00 B'

    const k = 1024
    const dm = decimals < 0 ? 0 : decimals
    const sizes = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB']

    const i = Math.floor(Math.log(bytes) / Math.log(k))

    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`
}

const handleFileUploadDialog = () => {
    showFileUploadDialog.value = !showFileUploadDialog.value;
}


</script>

<template>
    <Toast />
    <div class="lg:grid lg:grid-cols-[15%,85%] p-2 text-gray-700">
        <nav class="mr-4">
            <div class="sidebar max-lg:hidden">
                <div class="grid-rows-6">
                    <div class="flex">
                        <div class="self-center">
                            <a href="/dashboard">
                                <ApplicationLogo />
                            </a>
                        </div>
                        <div class="self-center">
                        <span class="xl:text-xl font-medium text-md">
                            <a href="/dashboard">Laravel File Manager</a>
                        </span>
                        </div>
                    </div>
                    <div class="my-2">
                        <Button class="w-full" label="Add File" icon="pi pi-plus" @click="handleFileUploadDialog" />
                    </div>
                    <div class="my-6">
                        <ul>
                            <li :class="{'bg-[#EEF2FF] text-[#4338CC] font-bold' : $page.url.substring(1) === item.url}" class="w-full mb-4 p-2 rounded-lg font-medium cursor-pointer"
                                v-for="item in menuItems" :key="item.url" @click="router.visit(item.url)">
                                {{item.label}}
                            </li>
                        </ul>
                    </div>
                </div>
                <hr class="border-gray-300">
                <div>
                    <ul>
                        <li :class="{'bg-[#EEF2FF] text-[#4338CC] font-bold' : $page.url === '/profile'}" class="w-full mb-2 mt-4 p-2 rounded-lg font-medium cursor-pointer"
                            @click="router.visit('/profile')">
                            Profile
                        </li>
                        <li class="w-full p-2 rounded-lg font-medium cursor-pointer"
                            @click="router.post('/logout')">
                            Logout
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center lg:hidden">
                <button
                    @click="showingNavigationDropdown = !showingNavigationDropdown"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out"
                >
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path
                            :class="{
                                                hidden: showingNavigationDropdown,
                                                'inline-flex': !showingNavigationDropdown,
                                            }"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                        <path
                            :class="{
                                                hidden: !showingNavigationDropdown,
                                                'inline-flex': showingNavigationDropdown,
                                            }"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <!-- Responsive Navigation Menu -->
            <div
                :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }"
                class="lg:hidden bg-white mt-2 rounded-lg"
            >
                <div class="pt-2 pb-3 space-y-1">
                    <ResponsiveNavLink v-for="item in menuItems" :key="item.url" :href="route(item.url)" :active="route().current(item.url)">
                        {{item.label}}
                    </ResponsiveNavLink>
                </div>

                <!-- Responsive Settings Options -->
                <div class="pt-4 pb-1 border-t border-gray-200">
                    <div class="px-4">
                        <div class="font-medium text-base text-gray-800">
                            {{ $page.props.auth.user.name }}
                        </div>
                        <div class="font-medium text-sm text-gray-500">{{ $page.props.auth.user.email }}</div>
                    </div>

                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')"> Profile </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                            Log Out
                        </ResponsiveNavLink>
                    </div>
                </div>
                <div class="p-2">
                    <Button class="w-full" label="Add File" icon="pi pi-plus" @click="handleFileUploadDialog" />
                </div>
            </div>
        </nav>

        <div class="mt-8 ml-4">
            <div>
                <div class="hidden lg:flex">
                    <div class="grow flex" v-if="$page.url !== '/profile'">
                        <InputText class="rounded-lg w-3/4 p-3 font-medium" :disabled="page.props.files.length === 0" v-model="filterInput" @input="emit('filterInput', filterInput)" placeholder="Search for files" />
                    </div>
                    <!-- Settings Dropdown -->
                    <div class="relative ml-auto mr-10 self-center">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
                                            >
                                                {{ $page.props.auth.user.name }}

                                                <svg
                                                    class="ms-2 -me-0.5 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                            </template>

                            <template #content>
                                <DropdownLink :href="route('profile.edit')"> Profile </DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">
                                    Log Out
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </div>

            <main class="lg:mr-10 mr-5">
                <slot></slot>
            </main>
        </div>
    </div>

    <Dialog v-model:visible="showFileUploadDialog" :style="{ width: '50rem' }" modal header="Upload">
        <FileUpload name="files[]" url="/upload" :multiple="true" :accept="getFileTypes" :maxFileSize="maxFileSize" @select="onSelectedFiles">
            <template #header="{ chooseCallback, uploadCallback, clearCallback, files }">
                <div class="flex flex-wrap justify-content-between align-items-center flex-1 gap-2">
                    <div class="flex gap-2">
                        <Button @click="chooseCallback()" icon="pi pi-images" rounded outlined></Button>
                        <Button @click="uploadEvent()" icon="pi pi-cloud-upload" rounded outlined severity="success" :disabled="!files || files.length === 0"></Button>
                        <Button @click="clearCallback(); fileForm.cancel();" icon="pi pi-times" rounded outlined severity="danger" :disabled="!files || files.length === 0"></Button>
                    </div>
                    <ProgressBar v-if="fileForm.progress" :value="fileForm.progress.percentage" :showValue="false" :class="['md:w-20rem h-1rem w-full md:ml-auto']"
                    ></ProgressBar>
                </div>
            </template>
            <template #content="{ files, uploadedFiles, removeUploadedFileCallback, removeFileCallback }">
                <div v-if="files.length > 0">
                    <div class="flex flex-wrap p-0 sm:p-5 gap-5">
                        <div v-for="(file, index) of files" :key="file.name + file.type + file.size" class="card m-0 px-6 flex flex-column border-1 surface-border align-items-center gap-3">
                            <div class="self-center">
                                <img role="presentation" :alt="file.name" :src="file.objectURL" width="100" height="50" class="shadow-2" />
                            </div>
                            <span class="font-semibold self-center max-lg:break-all">{{ file.name }}</span>
                            <div class="self-center">{{ formatBytes(file.size) }}</div>
                            <Badge class="self-center max-lg:hidden" value="Pending" severity="warning" />
                            <Button class="self-center" icon="pi pi-times" @click="onRemoveTemplatingFile(file, removeFileCallback, index)" outlined rounded  severity="danger" />
                        </div>
                    </div>
                </div>

            </template>
            <template #empty>
                <div class="text-[#9CA3AF]">
                    <div class="flex">
                        <div class="mx-auto">
                            <i class="pi pi-cloud-upload border-2 border-[#BFC3CB] rounded-full border-circle p-5 text-8xl text-400 border-400" />
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        Drag and drop files to here to upload.
                    </div>
                </div>
            </template>
        </FileUpload>
    </Dialog>

</template>

