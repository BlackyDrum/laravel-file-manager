<script setup lang="ts">
import { onMounted, ref, defineProps, onUpdated, onBeforeUpdate } from "vue";
import { router, usePage } from "@inertiajs/vue3";

import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";

import Button from "primevue/button";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import ConfirmDialog from "primevue/confirmdialog";
import InputText from "primevue/InputText";
import Dialog from "primevue/dialog";
import MultiSelect from "primevue/multiselect";
import OverlayPanel from "primevue/overlaypanel";

const props = defineProps(["filterInput", "files", "errors"]);

const page = usePage();
const confirm = useConfirm();
const toast = useToast();

const maxFileSize = ref(import.meta.env.VITE_MAX_FILE_SIZE);
const maxFileDownloadCount = import.meta.env.VITE_MAX_FILE_DOWNLOAD_COUNT;

const showShareFileDialog = ref(false);
const currentShareEmail = ref("");
const isSharingFiles = ref(false);

const overlayPanel = ref();
const selectedFileContextMenu = ref("");

const tableHeadBackground = ref("#DADADA");
const selectedFiles = ref([]);
const files = ref();
const isDownloading = ref(false);
const isDeleting = ref(false);
const isRenaming = ref({
    status: false,
    identifier: "",
});

const sharePrivileges = ref([
    { name: "Download File", value: "download" },
    { name: "Rename File", value: "rename" },
    { name: "Delete File", value: "delete" },
]);
const selectedSharePrivileges = ref([]);

const filters = ref({
    global: { value: null, matchMode: "contains" },
});

onMounted(() => {
    updateFiles();
});

onUpdated(() => {
    updateFiles();
    filters.value.global.value = props.filterInput;
});

const updateFiles = () => {
    files.value = page.props.files;

    for (let i = 0; i < files.value.length; i++) {
        files.value[i]["modified"] =
            `${calculateLastModified(files.value[i].updated_at)} days ago`;
    }
};

const calculateLastModified = (lastModified) => {
    const lastModifiedDate = new Date(lastModified);
    const currentDate = new Date();

    return Math.round(
        Math.abs(currentDate - lastModifiedDate) / (1000 * 60 * 60 * 24),
    );
};

// Source: https://stackoverflow.com/questions/15900485/correct-way-to-convert-size-in-bytes-to-kb-mb-gb-in-javascript
const formatBytes = (bytes, decimals = 2) => {
    if (!+bytes) return "0.00 B";

    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ["B", "KiB", "MiB", "GiB", "TiB", "PiB", "EiB", "ZiB", "YiB"];

    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
};

const confirmFileDeletion = () => {
    if (selectedFiles.value.length === 0) {
        toast.add({
            severity: "info",
            summary: "Info",
            detail: "You need to provide at least 1 file",
            life: 6000,
        });
        return;
    }

    isDeleting.value = true;

    confirm.require({
        message: "Do you want to delete the selected files?",
        header: "Delete Confirmation",
        icon: "pi pi-info-circle",
        rejectClass: "p-button-text p-button-text",
        acceptClass: "p-button-danger p-button-text",
        accept: () => {
            window.axios
                .delete("/files", {
                    data: {
                        files: selectedFiles.value,
                    },
                })
                .then((response) => {
                    toast.add({
                        severity: "success",
                        summary: "Success",
                        detail: response.data.message,
                        life: 3000,
                    });
                    selectedFiles.value.splice(0);

                    router.reload();
                })
                .catch((error) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: error.response.data.message,
                        life: 6000,
                    });
                })
                .finally(() => {
                    isDeleting.value = false;
                });
        },
        reject: () => {
            isDeleting.value = false;
        },
    });
};

const handleFileDownload = () => {
    if (isDownloading.value) return;

    if (selectedFiles.value.length > maxFileDownloadCount) {
        toast.add({
            severity: "info",
            summary: "Info",
            detail: `You can only download ${maxFileDownloadCount} files at once`,
            life: 6000,
        });
        return;
    } else if (selectedFiles.value.length === 0) {
        toast.add({
            severity: "info",
            summary: "Info",
            detail: "You need to provide at least 1 file",
            life: 6000,
        });
        return;
    }

    let totalSize = 0;
    for (const file of selectedFiles.value) {
        totalSize += file.size;
    }

    if (totalSize > maxFileSize.value) {
        toast.add({
            severity: "info",
            summary: "Info",
            detail: `Total size exceeds the limit of ${Math.floor(maxFileSize.value / 1024 / 1024)}MB`,
            life: 6000,
        });
        return;
    }

    const identifiers = selectedFiles.value.map((item) => ({
        identifier: item.identifier,
    }));

    isDownloading.value = true;

    window.axios
        .post(
            "/download",
            { files: identifiers },
            { responseType: "arraybuffer" },
        )
        .then((response) => {
            const zipBlob = new Blob([response.data], {
                type: "application/zip",
            });

            const link = document.createElement("a");
            link.href = window.URL.createObjectURL(zipBlob);
            link.download = "files.zip";

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        })
        .catch((e) => {
            const error = JSON.parse(
                new TextDecoder("utf-8").decode(
                    new Uint8Array(e.response.data),
                ),
            );
            toast.add({
                severity: "error",
                summary: "Error",
                detail: error.message,
                life: 6000,
            });
        })
        .finally(() => {
            isDownloading.value = false;
        });
};

const handleFileShare = () => {
    if (isSharingFiles.value) return;

    if (currentShareEmail.value === page.props.auth.user.email) {
        toast.add({
            severity: "info",
            summary: "Info",
            detail: "You cannot share files with yourself",
            life: 6000,
        });
        return;
    }

    const identifiers = selectedFiles.value.map((item) => ({
        identifier: item.identifier,
    }));

    isSharingFiles.value = true;

    window.axios
        .post("/share", {
            files: identifiers,
            email: currentShareEmail.value,
            privileges: selectedSharePrivileges.value,
        })
        .then((response) => {
            toast.add({
                severity: "success",
                summary: "Success",
                detail: response.data.message,
                life: 3000,
            });
            showShareFileDialog.value = false;

            currentShareEmail.value = "";
            selectedSharePrivileges.value.splice(0);
        })
        .catch((error) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: error.response.data.message,
                life: 6000,
            });
        })
        .finally(() => {
            isSharingFiles.value = false;
        });
};

const handleFileShareDialogOpen = () => {
    for (const file of selectedFiles.value) {
        if (file.owner_id !== page.props.auth.user.id) {
            toast.add({
                severity: "info",
                summary: "Info",
                detail: "You can only share your own files",
                life: 6000,
            });
            return;
        }
    }

    if (selectedFiles.value.length === 0) {
        toast.add({
            severity: "info",
            summary: "Info",
            detail: "You need to provide at least 1 file",
            life: 6000,
        });
        return;
    }

    showShareFileDialog.value = true;
};

const handleFileShareDialogClose = () => {
    showShareFileDialog.value = false;
    currentShareEmail.value = "";
};

const renameFile = (e) => {
    let { data, newValue } = e;

    if (data.name === newValue) {
        return;
    }

    const identifier = data.identifier;

    isRenaming.value.status = true;
    isRenaming.value.identifier = identifier;

    window.axios
        .patch("/files/rename", {
            identifier: identifier,
            filename: newValue,
        })
        .then((response) => {
            for (let i = 0; i < files.value.length; i++) {
                if (files.value[i].identifier === response.data.id) {
                    files.value[i].name = response.data.name;
                    files.value[i]["modified"] = `0 days ago`;
                    break;
                }
            }
        })
        .catch((error) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: error.response.data.message,
                life: 6000,
            });
        })
        .finally(() => {
            isRenaming.value.status = false;
            isRenaming.value.identifier = "";
        });
};

const handleFileContextMenu = (identifier, event) => {
    selectedFileContextMenu.value = identifier;
    overlayPanel.value.toggle(event);
};

const handleFileContextMenuItemClick = (operation) => {
    selectedFiles.value.splice(0);
    selectedFiles.value.push(
        files.value.find((f) => f.identifier === selectedFileContextMenu.value),
    );
    if (operation === "download") {
        handleFileDownload();
    } else if (operation === "delete") {
        confirmFileDeletion();
    }
};
</script>

<template>
    <ConfirmDialog />
    <OverlayPanel ref="overlayPanel">
        <div
            class="flex hover:bg-gray-100 cursor-pointer p-2"
            @click="handleFileContextMenuItemClick('download')"
        >
            <div class="mr-4">
                <i
                    :class="
                        isDownloading
                            ? 'pi pi-spin pi-spinner'
                            : 'pi pi-download'
                    "
                />
            </div>
            <div class="font-sans">Download</div>
        </div>

        <hr class="my-2" />

        <div
            class="flex hover:bg-gray-100 cursor-pointer p-2"
            @click="handleFileContextMenuItemClick('delete')"
        >
            <div class="mr-4">
                <i
                    :class="
                        isDeleting ? 'pi pi-spin pi-spinner' : 'pi pi-trash'
                    "
                />
            </div>
            <div class="font-sans">Delete</div>
        </div>
    </OverlayPanel>

    <div class="flex">
        <div v-if="$page.props.files.length !== 0" class="flex gap-3 ml-auto">
            <Button
                class="text-black border-gray-300 bg-white font-medium"
                :class="{ 'cursor-not-allowed': selectedFiles.length === 0 }"
                label="Share"
                icon="pi pi-share-alt"
                @click="handleFileShareDialogOpen"
            />
            <Button
                class="font-medium"
                :class="{ 'cursor-not-allowed': selectedFiles.length === 0 }"
                label="Download"
                :icon="
                    isDownloading ? 'pi pi-spin pi-spinner' : 'pi pi-download'
                "
                @click="handleFileDownload"
            />
            <Button
                class="text-black border-gray-300 bg-white font-medium"
                :class="{ 'cursor-not-allowed': selectedFiles.length === 0 }"
                label="Delete"
                :icon="isDeleting ? 'pi pi-spin pi-spinner' : 'pi pi-trash'"
                @click="confirmFileDeletion"
            />
        </div>
    </div>
    <div class="mt-4">
        <DataTable
            v-if="$page.props.files.length !== 0"
            :filters="filters"
            class="font-sans shadow-lg"
            v-model:selection="selectedFiles"
            :value="files"
            tableStyle="min-width: 50rem"
            scrollable
            scrollHeight="40rem"
            editMode="cell"
            @cell-edit-complete="renameFile"
        >
            <template #empty> No File found </template>
            <Column
                selectionMode="multiple"
                :headerStyle="{ background: tableHeadBackground }"
            ></Column>
            <Column
                class="w-1"
                v-if="isRenaming.status"
                :headerStyle="{ background: tableHeadBackground }"
            >
                <template #body="{ data }">
                    <i
                        v-if="data.identifier === isRenaming.identifier"
                        class="pi pi-spin pi-spinner"
                    />
                </template>
            </Column>
            <Column
                field="name"
                header="Name"
                sortable
                :headerStyle="{ background: tableHeadBackground }"
            >
                <template #editor="{ data, field }">
                    <InputText
                        class="w-full"
                        :disabled="isRenaming.status"
                        v-model="data[field]"
                        autofocus
                    />
                </template>
            </Column>
            <Column
                field="owner"
                header="Owner"
                sortable
                :headerStyle="{ background: tableHeadBackground }"
            >
                <template #body="{ data, field }">
                    {{ data["owner"] }}
                    {{
                        data["owner_id"] === $page.props.auth.user.id
                            ? "(me)"
                            : ""
                    }}
                </template>
            </Column>
            <Column
                field="modified"
                header="Last Modified"
                sortable
                :headerStyle="{ background: tableHeadBackground }"
            ></Column>
            <Column
                style="width: 15%"
                field="size"
                header="Size"
                sortable
                :headerStyle="{ background: tableHeadBackground }"
            >
                <template #body="{ data, field }">
                    {{ formatBytes(data[field]) }}
                </template>
            </Column>
            <Column
                style="width: 4%"
                :headerStyle="{ background: tableHeadBackground }"
            >
                <template #body="{ data, field }">
                    <i
                        class="pi pi-ellipsis-v cursor-pointer p-3 rounded-full hover:bg-gray-100"
                        @click="handleFileContextMenu(data.identifier, $event)"
                    />
                </template>
            </Column>
        </DataTable>
        <div v-else>
            <div class="flex flex-col">
                <div
                    class="pi pi-cloud-upload mx-auto my-20 text-[10rem]"
                ></div>
                <div class="mx-auto text-2xl">Upload Files to get started!</div>
            </div>
        </div>
    </div>

    <Dialog
        v-model:visible="showShareFileDialog"
        modal
        header="Share Files"
        :style="{ width: '25rem' }"
    >
        <InputText
            class="w-full"
            placeholder="E-Mail"
            v-model="currentShareEmail"
        />
        <MultiSelect
            v-model="selectedSharePrivileges"
            :options="sharePrivileges"
            optionLabel="name"
            placeholder="Select Privileges"
            class="w-full my-4"
        />
        <div class="flex gap-4 mt-2">
            <Button
                class="ml-auto text-black border-gray-300 bg-white font-medium"
                label="Cancel"
                icon="pi pi-times"
                @click="handleFileShareDialogClose"
            />
            <Button
                class="font-medium"
                label="Share"
                :icon="
                    isSharingFiles ? 'pi pi-spin pi-spinner' : 'pi pi-share-alt'
                "
                @click="handleFileShare"
            />
        </div>
    </Dialog>
</template>

<style>
.p-highlight {
    background-color: #d4dbff;
    color: black;
}

.p-checkbox .p-highlight {
    background-color: #007bff;
}

.p-overlaypanel .p-overlaypanel-content {
    padding: 0.25rem;
}
</style>
