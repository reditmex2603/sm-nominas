<script setup lang="ts">
interface PerfilBancario {
    banco: string | null;
    beneficiario: string | null;
    clave_interbancaria: string | null;
}

const props = defineProps<{ perfil: PerfilBancario | null }>();
</script>

<template>
    <div
        v-if="props.perfil && (props.perfil.banco || props.perfil.beneficiario || props.perfil.clave_interbancaria)"
        class="rounded-xl border p-4"
    >
        <p class="text-muted-foreground mb-2 text-xs font-medium uppercase tracking-wide">Datos bancarios</p>
        <dl class="grid grid-cols-1 gap-2 text-sm sm:grid-cols-3">
            <div>
                <dt class="text-muted-foreground text-xs">Banco</dt>
                <dd class="mt-0.5">{{ props.perfil.banco ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-muted-foreground text-xs">Beneficiario</dt>
                <dd class="mt-0.5">{{ props.perfil.beneficiario ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-muted-foreground text-xs">CLABE</dt>
                <dd class="mt-0.5 tabular-nums">{{ props.perfil.clave_interbancaria ?? '—' }}</dd>
            </div>
        </dl>
    </div>
</template>