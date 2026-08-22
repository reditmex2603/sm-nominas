<script setup lang="ts">
import { useBranding } from '@/composables/useBranding';

const { branding } = useBranding();
</script>

<template>
    <img
        v-if="branding.logo_url"
        :src="branding.logo_url"
        :alt="branding.nombre ?? 'Logo'"
        class="h-8 w-auto"
    />
    <span v-else class="text-sm font-semibold">SM</span>
</template>