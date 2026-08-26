<script setup lang="ts">
import { AlertCircle, FileText, ImageIcon, X } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';

const props = withDefaults(
    defineProps<{
        accept?: string;
        mimes?: string[];
        maxMb?: number;
        error?: string;
        preview?: boolean;
    }>(),
    {
        accept: '',
        mimes: undefined,
        maxMb: 5,
        error: '',
        preview: true,
    },
);

const model = defineModel<File | null>({ default: null });

const inputRef = ref<HTMLInputElement | null>(null);
const localError = ref('');
const previewUrl = ref<string | null>(null);

const isImage = computed(() => model.value?.type.startsWith('image/') ?? false);

const formatoMb = (bytes: number): string => `${(bytes / (1024 * 1024)).toFixed(1)} MB`;

const validar = (file: File): string => {
    if (props.mimes && props.mimes.length > 0 && !props.mimes.includes(file.type)) {
        return 'Formato no permitido. Usa JPG, PNG o PDF.';
    }

    if (props.maxMb && file.size > props.maxMb * 1024 * 1024) {
        return `El archivo excede los ${props.maxMb} MB permitidos.`;
    }

    return '';
};

const onChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null;

    if (!file) {
        return;
    }

    const err = validar(file);

    if (err) {
        localError.value = err;

        if (inputRef.value) {
            inputRef.value.value = '';
        }

        return;
    }

    localError.value = '';

    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }

    previewUrl.value = file.type.startsWith('image/') ? URL.createObjectURL(file) : null;
    model.value = file;
};

const quitar = () => {
    localError.value = '';

    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }

    previewUrl.value = null;
    model.value = null;

    if (inputRef.value) {
        inputRef.value.value = '';
    }
};

watch(() => props.error, (v) => {
    if (v) {
        localError.value = v;
    }
});

watch(model, (file) => {
    if (!file && previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }
});

onBeforeUnmount(() => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
});
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <label class="peer cursor-pointer">
            <input
                ref="inputRef"
                type="file"
                class="sr-only"
                :accept="accept"
                @change="onChange"
            />
            <Button type="button" size="sm" variant="outline" as-child>
                <span>Seleccionar archivo</span>
            </Button>
        </label>

        <template v-if="model">
            <div
                v-if="preview && isImage && previewUrl"
                class="overflow-hidden rounded-md border"
            >
                <img
                    :src="previewUrl"
                    alt="Vista previa del archivo"
                    class="h-16 w-16 object-cover transition-opacity duration-200"
                />
            </div>
            <div
                v-else
                class="flex items-center gap-1.5 rounded-md border bg-muted/40 px-2 py-1 text-xs"
            >
                <ImageIcon v-if="isImage" class="size-3.5 text-muted-foreground" />
                <FileText v-else class="size-3.5 text-muted-foreground" />
                <span class="max-w-40 truncate font-medium">{{ model.name }}</span>
                <span class="text-muted-foreground whitespace-nowrap">{{ formatoMb(model.size) }}</span>
                <button
                    type="button"
                    class="text-muted-foreground transition-colors hover:text-destructive"
                    :aria-label="`Quitar ${model.name}`"
                    @click="quitar"
                >
                    <X class="size-3.5" />
                </button>
            </div>
        </template>
    </div>

    <p
        v-if="localError"
        class="text-destructive mt-1 flex items-center gap-1 text-xs"
        role="alert"
    >
        <AlertCircle class="size-3.5 flex-shrink-0" />
        {{ localError }}
    </p>
</template>