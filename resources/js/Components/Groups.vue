<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
</script>

<template>
    <div>
        <div class="p-6 ml-4 bg-white border-b border-gray-200">
            <div class="text-2xl">Current Email</div>
        </div>

        <div class="bg-gray-200 bg-opacity-25 grid grid-cols-1 md:grid-cols-2">
            <div class="p-6 border-t border-gray-200 md:border-l">
                <div class="flex items-center">
                    <div
                        class="ml-4 text-lg text-gray-600 leading-7 font-semibold"
                    >
                        Available
                    </div>
                </div>

                <div class="ml-12">
                    <div class="mt-2 text-sm text-gray-500"></div>
                </div>
            </div>
            <div class="p-6 border-t border-gray-200 md:border-l">
                <div class="flex items-center">
                    <div
                        class="ml-4 text-lg text-gray-600 leading-7 font-semibold"
                    >
                        Member Of
                    </div>
                </div>

                <div class="ml-12">
                    <div class="mt-2 text-sm text-gray-500"></div>
                </div>
            </div>
        </div>
    </div>
</template>
