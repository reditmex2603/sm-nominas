<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Printer, ArrowLeft } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import PrintLogo from '@/components/PrintLogo.vue';

type TipoColaborador = 'COLABORADOR BASE' | 'FREELANCE' | 'CONDUCTOR' | 'CONDUCTOR BASE';

interface Colaborador {
    id: number;
    nombre: string;
    apellidos: string;
    tipo: TipoColaborador;
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

const campos = [
    { clave: 'seguro_social_documento_url', label: 'Documento de seguro social' },
    { clave: 'ine_documento_url', label: 'INE' },
    { clave: 'curp_documento_url', label: 'CURP' },
    { clave: 'comprobante_domicilio_documento_url', label: 'Comprobante de domicilio' },
    { clave: 'licencia_conducir_documento_url', label: 'Licencia de conducir' },
] as const;

const documentos = computed(() =>
    campos
        .map((c) => ({ ...c, url: props.perfil?.[c.clave] ?? null }))
        .filter((d): d is typeof d & { url: string } => d.url !== null),
);

const listo = ref(false);
const doPrint = () => window.print();

onMounted(() => {
    const imgs = Array.from(document.querySelectorAll<HTMLImageElement>('img[data-documento]'));
    const cargarImg = (img: HTMLImageElement) =>
        img.complete && img.naturalWidth > 0
            ? Promise.resolve()
            : new Promise<void>((resolve) => {
                  img.addEventListener('load', () => resolve(), { once: true });
                  img.addEventListener('error', () => resolve(), { once: true });
              });
    const esperar = Promise.all(imgs.map(cargarImg)).then(() => new Promise((r) => setTimeout(r, 250)));
    const tope = new Promise((resolve) => setTimeout(resolve, 8000));
    Promise.race([esperar, tope]).finally(() => {
        listo.value = true;
        setTimeout(doPrint, 150);
    });
});
</script>

<template>
    <Head :title="`Documentos — ${colaborador.nombre} ${colaborador.apellidos}`" />

    <div class="print-container min-h-screen bg-slate-100 p-4 print:m-0 print:bg-white print:p-0">
        <!-- Toolbar (solo pantalla) -->
        <div class="mb-3 flex items-center justify-between print:hidden">
            <Link
                :href="`/colaboradores/${colaborador.id}/perfil`"
                class="inline-flex items-center gap-1.5 text-sm text-slate-600 hover:text-slate-900"
            >
                <ArrowLeft class="size-4" />
                Volver al perfil
            </Link>
            <button
                class="inline-flex items-center gap-1.5 rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
                @click="doPrint"
            >
                <Printer class="size-4" />
                Imprimir
            </button>
            <span v-if="!listo" class="text-xs text-slate-500">Preparando documentos para imprimir…</span>
        </div>

        <!-- Documento -->
        <div class="mx-auto max-w-4xl bg-white shadow-lg print:shadow-none">
            <!-- Header -->
            <div class="flex items-center justify-between border-b-2 border-slate-800 px-6 py-3">
                <div class="flex items-center gap-4">
                    <PrintLogo />
                    <div>
                        <h1 class="text-2xl font-bold uppercase tracking-tight text-slate-900">
                            {{ colaborador.apellidos }}, {{ colaborador.nombre }}
                        </h1>
                        <p class="text-sm font-semibold text-slate-700">Documentos del colaborador</p>
                    </div>
                </div>
                <span class="rounded-md bg-slate-100 px-3 py-1 text-xs font-medium text-slate-500">
                    {{ documentos.length }} documento(s)
                </span>
            </div>

            <!-- Documentos -->
            <section v-if="documentos.length" class="px-6 py-4">
                <div
                    v-for="(doc, i) in documentos"
                    :key="doc.clave"
                    class="mb-4 break-inside-avoid"
                    :class="i > 0 ? 'print:break-before-page' : ''"
                >
                    <div class="mb-1 flex items-baseline gap-2 border-b border-slate-200 pb-1">
                        <span class="text-xs font-bold uppercase tracking-wide text-slate-700">{{ i + 1 }}. {{ doc.label }}</span>
                    </div>
                    <img
                        v-if="!doc.url.toLowerCase().endsWith('.pdf')"
                        data-documento
                        :src="doc.url"
                        :alt="doc.label"
                        class="max-h-[80vh] w-full border border-slate-200 object-contain print:max-h-[calc(100vh-190px)]"
                    />
                    <embed
                        v-else
                        :src="doc.url"
                        type="application/pdf"
                        class="h-[80vh] w-full border border-slate-200 print:h-[calc(100vh-190px)]"
                    />
                </div>
            </section>

            <!-- Sin documentos -->
            <section v-else class="px-6 py-16 text-center">
                <p class="text-sm text-slate-500">Este colaborador no tiene documentos registrados.</p>
            </section>

            <!-- Footer -->
            <div class="border-t px-6 pb-3 pt-2">
                <p class="text-center text-[10px] text-slate-400">
                    Este documento fue generado electrónicamente. Colaborador: {{ colaborador.nombre }} {{ colaborador.apellidos }}
                </p>
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    @page {
        margin: 8mm 10mm;
        size: letter portrait;
    }
    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .print-container {
        padding: 0 !important;
        background: white !important;
    }
}
</style>