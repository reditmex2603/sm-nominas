<script setup lang="ts">
import { User, AlertCircle, Eye, X } from '@lucide/vue';
import { ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';

interface PerfilData {
    tipo_sangre: string | null;
    alergias: string | null;
    padecimientos_cronicos: string | null;
    numero_seguro_social: string | null;
    seguro_social_documento_url: string | null;
    ine_documento_url: string | null;
    curp_documento_url: string | null;
    comprobante_domicilio_documento_url: string | null;
    licencia_conducir_documento_url: string | null;
    datos_bancarios?: Array<{
        id: number;
        banco: string | null;
        beneficiario: string | null;
        clave_interbancaria: string | null;
        numero_tarjeta: string | null;
        alias: string | null;
        comentario: string | null;
    }>;
    [key: string]: any;
}

interface ColaboradorData {
    id: number;
    nombre: string;
    apellidos: string;
    tipo: string;
    categoria: string | null;
    nivel: number | null;
    sueldo_diario: string | null;
    compensacion_pct: number;
    extra_dia_adicional: string | null;
}

interface ApiResponse {
    colaborador: ColaboradorData;
    perfil: PerfilData | null;
}

const tipoLabel: Record<string, string> = {
    'COLABORADOR BASE': 'Base',
    FREELANCE: 'Freelance',
    CONDUCTOR: 'Conductor',
    'CONDUCTOR BASE': 'Conductor base',
};

const tipoClass: Record<string, string> = {
    'COLABORADOR BASE': 'border-blue-300 text-blue-700 bg-blue-50',
    FREELANCE: 'border-purple-300 text-purple-700 bg-purple-50',
    CONDUCTOR: 'border-amber-300 text-amber-700 bg-amber-50',
    'CONDUCTOR BASE': 'border-orange-300 text-orange-700 bg-orange-50',
};

const props = defineProps<{
    colaboradorId: number | null;
}>();

const emit = defineEmits<{ close: [] }>();

const open = ref(false);
const loading = ref(false);
const data = ref<ApiResponse | null>(null);

const fetchPerfil = async (id: number) => {
    loading.value = true;
    data.value = null;
    open.value = true;

    try {
        const res = await fetch(`/colaboradores/${id}/perfil/datos`);

        if (!res.ok) {
            throw new Error('Error al cargar perfil');
        }

        data.value = await res.json();
    } catch {
        data.value = null;
    } finally {
        loading.value = false;
    }
};

watch(
    () => props.colaboradorId,
    (id) => {
        if (id !== null) {
            fetchPerfil(id);
        }
    },
);

const cerrar = () => {
    open.value = false;
    emit('close');
};

const fmt = (val: string | number) =>
    `$${parseFloat(String(val)).toLocaleString('es-MX', { minimumFractionDigits: 2 })}`;

const documentoLabel: Record<string, string> = {
    seguro_social: 'Seguro Social',
    ine: 'INE',
    curp: 'CURP',
    comprobante_domicilio: 'Comprobante de Domicilio',
    licencia_conducir: 'Licencia de Conducir',
};
</script>

<template>
    <Dialog
        :open="open"
        @update:open="
            (v) => {
                if (!v) cerrar();
            }
        "
    >
        <DialogContent class="max-h-[85vh] max-w-2xl overflow-y-auto">
            <DialogHeader>
                <DialogTitle v-if="data" class="flex items-center gap-2">
                    <User class="size-5 text-slate-500" />
                    {{ data.colaborador.apellidos }},
                    {{ data.colaborador.nombre }}
                </DialogTitle>
                <DialogDescription v-if="loading"
                    >Cargando perfil…</DialogDescription
                >
            </DialogHeader>

            <Spinner v-if="loading" class="py-10" />

            <div v-else-if="data" class="space-y-5">
                <!-- Info general -->
                <div class="rounded-lg border bg-slate-50 p-4">
                    <p
                        class="mb-2 text-xs font-semibold tracking-wide text-slate-500 uppercase"
                    >
                        Información general
                    </p>
                    <div
                        class="grid grid-cols-1 gap-x-6 gap-y-2 text-sm sm:grid-cols-2"
                    >
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium text-slate-500"
                                >Tipo</span
                            >
                            <Badge
                                variant="outline"
                                :class="tipoClass[data.colaborador.tipo] ?? ''"
                            >
                                {{
                                    tipoLabel[data.colaborador.tipo] ??
                                    data.colaborador.tipo
                                }}
                            </Badge>
                        </div>
                        <div v-if="data.colaborador.categoria">
                            <span class="text-xs font-medium text-slate-500"
                                >Categoría</span
                            >
                            <p class="text-slate-800">
                                {{ data.colaborador.categoria }}
                            </p>
                        </div>
                        <div
                            v-if="
                                data.colaborador.nivel !== null &&
                                data.colaborador.nivel !== undefined
                            "
                        >
                            <span class="text-xs font-medium text-slate-500"
                                >Nivel</span
                            >
                            <p class="text-slate-800">
                                {{ data.colaborador.nivel }}
                            </p>
                        </div>
                        <div v-if="data.colaborador.sueldo_diario">
                            <span class="text-xs font-medium text-slate-500"
                                >Sueldo diario</span
                            >
                            <p class="text-slate-800">
                                {{ fmt(data.colaborador.sueldo_diario) }}
                            </p>
                        </div>
                        <div v-if="data.colaborador.compensacion_pct > 0">
                            <span class="text-xs font-medium text-slate-500"
                                >Compensación</span
                            >
                            <p class="text-slate-800">
                                {{ data.colaborador.compensacion_pct }}%
                            </p>
                        </div>
                        <div v-if="data.colaborador.extra_dia_adicional">
                            <span class="text-xs font-medium text-slate-500"
                                >Extra día adicional</span
                            >
                            <p class="text-slate-800">
                                {{ fmt(data.colaborador.extra_dia_adicional) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Datos de perfil -->
                <div v-if="data.perfil" class="space-y-4">
                    <div class="rounded-lg border bg-slate-50 p-4">
                        <p
                            class="mb-2 text-xs font-semibold tracking-wide text-slate-500 uppercase"
                        >
                            Datos de emergencia
                        </p>
                        <div
                            class="grid grid-cols-1 gap-x-6 gap-y-2 text-sm sm:grid-cols-2"
                        >
                            <div>
                                <span class="text-xs font-medium text-slate-500"
                                    >Tipo de sangre</span
                                >
                                <p class="font-medium text-slate-800">
                                    {{ data.perfil.tipo_sangre ?? '—' }}
                                </p>
                            </div>
                            <div>
                                <span class="text-xs font-medium text-slate-500"
                                    >NSS</span
                                >
                                <p class="text-slate-800">
                                    {{
                                        data.perfil.numero_seguro_social ?? '—'
                                    }}
                                </p>
                            </div>
                            <div
                                v-if="data.perfil.alergias"
                                class="col-span-2 flex items-start gap-2"
                            >
                                <AlertCircle
                                    class="mt-0.5 size-4 flex-shrink-0 text-amber-400"
                                />
                                <div>
                                    <span
                                        class="text-xs font-medium text-slate-500"
                                        >Alergias</span
                                    >
                                    <p class="text-slate-800">
                                        {{ data.perfil.alergias }}
                                    </p>
                                </div>
                            </div>
                            <div
                                v-if="data.perfil.padecimientos_cronicos"
                                class="col-span-2 flex items-start gap-2"
                            >
                                <AlertCircle
                                    class="mt-0.5 size-4 flex-shrink-0 text-orange-400"
                                />
                                <div>
                                    <span
                                        class="text-xs font-medium text-slate-500"
                                        >Padecimientos crónicos</span
                                    >
                                    <p class="text-slate-800">
                                        {{ data.perfil.padecimientos_cronicos }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Datos bancarios -->
                    <div
                        v-if="
                            data.perfil.datos_bancarios &&
                            data.perfil.datos_bancarios.length > 0
                        "
                        class="rounded-lg border bg-slate-50 p-4"
                    >
                        <p
                            class="mb-2 text-xs font-semibold tracking-wide text-slate-500 uppercase"
                        >
                            Datos bancarios
                        </p>
                        <div class="space-y-2 text-sm">
                            <div
                                v-for="b in data.perfil.datos_bancarios"
                                :key="b.id"
                                class="rounded border bg-white px-3 py-2"
                            >
                                <p class="font-medium text-slate-800">
                                    {{
                                        b.alias ||
                                        b.banco ||
                                        'Registro bancario'
                                    }}
                                </p>
                                <div
                                    class="mt-1 grid grid-cols-1 gap-x-4 gap-y-0.5 text-xs text-slate-600 sm:grid-cols-2"
                                >
                                    <p v-if="b.banco">
                                        <span class="text-slate-400"
                                            >Banco:</span
                                        >
                                        {{ b.banco }}
                                    </p>
                                    <p v-if="b.beneficiario">
                                        <span class="text-slate-400"
                                            >Beneficiario:</span
                                        >
                                        {{ b.beneficiario }}
                                    </p>
                                    <p v-if="b.clave_interbancaria">
                                        <span class="text-slate-400"
                                            >CLABE:</span
                                        >
                                        {{ b.clave_interbancaria }}
                                    </p>
                                    <p v-if="b.numero_tarjeta">
                                        <span class="text-slate-400"
                                            >Tarjeta:</span
                                        >
                                        {{ b.numero_tarjeta }}
                                    </p>
                                    <p
                                        v-if="b.comentario"
                                        class="sm:col-span-2"
                                    >
                                        <span class="text-slate-400"
                                            >Comentario:</span
                                        >
                                        {{ b.comentario }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Documentos -->
                    <div class="rounded-lg border bg-slate-50 p-4">
                        <p
                            class="mb-2 text-xs font-semibold tracking-wide text-slate-500 uppercase"
                        >
                            Documentos
                        </p>
                        <div class="space-y-1.5 text-sm">
                            <div
                                v-for="(label, campo) in documentoLabel"
                                :key="campo"
                                class="flex items-center justify-between rounded px-2 py-1 hover:bg-white"
                            >
                                <span class="text-slate-600">{{ label }}</span>
                                <a
                                    v-if="data.perfil[`${campo}_documento_url`]"
                                    :href="
                                        data.perfil[`${campo}_documento_url`]
                                    "
                                    target="_blank"
                                    class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:text-blue-800"
                                >
                                    <Eye class="size-3.5" />
                                    Ver documento
                                </a>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 text-xs text-slate-400"
                                >
                                    <X class="size-3.5" />
                                    Sin adjuntar
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="rounded-lg border border-dashed p-6 text-center text-sm text-slate-500"
                >
                    Este colaborador no tiene perfil registrado (datos de
                    emergencia y documentos).
                </div>
            </div>

            <div v-else class="py-6 text-center text-sm text-red-500">
                No se pudo cargar la información del perfil.
            </div>
        </DialogContent>
    </Dialog>
</template>
