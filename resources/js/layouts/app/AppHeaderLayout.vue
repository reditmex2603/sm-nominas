<script setup lang="ts">
import { onMounted } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppHeader from '@/components/AppHeader.vue';
import AppShell from '@/components/AppShell.vue';
import { Toaster } from '@/components/ui/sonner';
import { aplicarMarca, useBranding } from '@/composables/useBranding';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const { branding } = useBranding();

onMounted(() => {
    aplicarMarca(branding.value);
});
</script>

<template>
    <AppShell variant="header">
        <AppHeader :breadcrumbs="breadcrumbs" />
        <AppContent variant="header">
            <slot />
        </AppContent>
        <Toaster />
    </AppShell>
</template>
