<script setup lang="ts">
import { AlertCircle } from '@lucide/vue';

defineProps<{
    message?: string;
}>();
</script>

<template>
    <div v-show="message" class="text-red-600 dark:text-red-500">
        <p class="flex items-center gap-1 text-xs">
            <AlertCircle class="size-3.5 flex-shrink-0" />
            {{ message }}
        </p>
    </div>
</template>
