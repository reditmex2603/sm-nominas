<script setup lang="ts">
import { useBranding } from '@/composables/useBranding';

const { branding } = useBranding();
</script>

<template>
    <div class="flex shrink-0 items-center">
        <img
            v-if="branding.logo_url"
            :src="branding.logo_url"
            :alt="branding.nombre ?? 'Logo'"
            class="max-h-10 w-auto"
        />
        <div v-else class="flex size-10 items-center justify-center rounded-lg bg-slate-800 text-sm font-bold uppercase text-white">
            {{ branding.nombre ? branding.nombre.slice(0, 2) : 'SM' }}
        </div>
    </div>
</template>