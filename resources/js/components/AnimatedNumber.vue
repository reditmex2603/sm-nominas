<script setup lang="ts">
import { computed } from 'vue';
import { useCountUp } from '@/composables/useCountUp';

const props = defineProps<{
    value: number;
    format?: (n: number) => string;
    duration?: number;
    delay?: number;
}>();

const display = useCountUp(() => props.value, {
    duration: props.duration,
    delay: props.delay,
});

const text = computed(() =>
    props.format
        ? props.format(display.value)
        : Math.round(display.value).toString(),
);
</script>

<template>
    <span class="tabular-nums">{{ text }}</span>
</template>
