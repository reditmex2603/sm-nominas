<script setup lang="ts">
import { computed, ref } from 'vue';
import {
    Dialog,
    DialogDescription,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/components/ui/dialog';

interface Colaborador {
    id: number;
    nombre: string;
    apellidos: string;
}

interface Perfil {
    seguro_social_documento_url: string | null;
    ine_documento_url: string | null;
    curp_documento_url: string | null;
    comprobante_domicilio_documento_url: string | null;
    licencia_conducir_documento_url: string | null;
}

const props = defineProps<{
    colaborador: Colaborador;
    perfil: Perfil | null;
}>();

const emit = defineEmits<{ close: [] }>();

const open = ref(true);

const cerrar = () => {
    open.value = false;
    emit('close');
};

const campos = [
    { clave: 'seguro_social_documento_url', label: 'Documento de seguro social' },
    { clave: 'ine_documento_url', label: 'INE' },
    { clave: 'curp_documento_url', label: 'CURP' },
    { clave: 'comprobante_domicilio_documento_url', label: 'Comprobante de domicilio' },
    { clave: 'licencia_conducir_documento_url', label: 'Licencia de conducir' },
] as const;

interface Documento {
    clave: (typeof campos)[number]['clave'];
    label: string;
    url: string;
}

const esPdf = (url: string) => url.toLowerCase().endsWith('.pdf');

const documentos = computed<Documento[]>(() => {
    const docs: Documento[] = [];

    for (const c of campos) {
        const url = props.perfil?.[c.clave];

        if (url) {
            docs.push({ clave: c.clave, label: c.label, url });
        }
    }

    return docs;
});
</script>

<template>
    <Dialog :open="open" @update:open="(v) => { if (!v) cerrar() }">
        <DialogScrollContent class="flex max-h-[85vh] max-w-4xl flex-col overflow-hidden">
            <DialogHeader>
                <DialogTitle>Documentos de {{ colaborador.apellidos }}, {{ colaborador.nombre }}</DialogTitle>
                <DialogDescription>
                    Vista previa de los documentos registrados. Use el botón de impresión del propio visor en cada documento.
                </DialogDescription>
            </DialogHeader>

            <span class="text-muted-foreground shrink-0 text-sm">{{ documentos.length }} documento(s)</span>

            <div v-if="documentos.length" class="min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
                <div v-for="(doc, i) in documentos" :key="doc.clave" class="min-w-0 overflow-hidden rounded-lg border p-3">
                    <p class="mb-2 text-sm font-semibold">{{ i + 1 }}. {{ doc.label }}</p>
                    <img
                        v-if="!esPdf(doc.url)"
                        :src="doc.url"
                        :alt="doc.label"
                        class="max-h-[45vh] w-full object-contain bg-white"
                    />
                    <embed v-else :src="doc.url" type="application/pdf" class="h-[45vh] w-full bg-white" />
                </div>
            </div>
            <p v-else class="text-muted-foreground shrink-0 text-sm">Este colaborador no tiene documentos registrados.</p>
        </DialogScrollContent>
    </Dialog>
</template>