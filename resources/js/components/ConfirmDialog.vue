<script setup lang="ts">
import { useConfirm } from '@/composables/useConfirm';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

const { confirmState, accept, dismiss } = useConfirm();
</script>

<template>
    <Dialog :open="confirmState.open" @update:open="(v) => { if (!v) dismiss(); }">
        <DialogContent class="z-[100] max-w-sm" overlay-class="z-[100]">
            <DialogHeader>
                <DialogTitle>{{ confirmState.title }}</DialogTitle>
                <DialogDescription>{{ confirmState.message }}</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="dismiss">{{ confirmState.cancelLabel }}</Button>
                <Button :variant="confirmState.variant" @click="accept">{{ confirmState.confirmLabel }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
