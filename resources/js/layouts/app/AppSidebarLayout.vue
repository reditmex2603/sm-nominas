<script setup lang="ts">
import { onMounted } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
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
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        <Toaster />
        <ConfirmDialog />
    </AppShell>
</template>
