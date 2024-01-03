<script setup lang="ts">
import { ref } from 'vue';
import {router, usePage} from "@inertiajs/vue3";

import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import Dropdown from '@/Components/Dropdown.vue';

import Button from 'primevue/button';
import InputText from 'primevue/InputText';

////////////////////////////////////////////////

const page = usePage();

const showingNavigationDropdown = ref(false)


const menuItems = ref([
    { label: "Dashboard", url: "dashboard" },
    { label: "Shared with me", url: "shared-with-me" },
    { label: "Shared by me", url: "shared-by-me" },
]);

</script>

<template>
    <div class="lg:grid lg:grid-cols-[15%,85%] gap-8 p-2 text-gray-700">
        <nav>
            <div class="sidebar max-lg:hidden">
                <div class="grid-rows-6">
                    <div class="flex">
                        <div class="self-center">
                            <ApplicationLogo />
                        </div>
                        <div class="self-center">
                        <span class="lg:text-xl font-medium text-xs">
                            File Manager
                        </span>
                        </div>
                    </div>
                    <div class="my-2">
                        <Button class="w-full" label="Add File" icon="pi pi-plus" />
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
                class="lg:hidden"
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
            </div>
        </nav>

        <div class="mt-8">
            <div>
                <div class="hidden lg:flex">
                    <div class="grow flex">
                        <InputText class="rounded-lg w-3/4 p-3 font-medium" placeholder="Search for files and folders" />
                    </div>
                    <!-- Settings Dropdown -->
                    <div class="ms-3 relative ml-auto mr-10 self-center">
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

            <main>
                <slot></slot>
            </main>
        </div>
    </div>

</template>
