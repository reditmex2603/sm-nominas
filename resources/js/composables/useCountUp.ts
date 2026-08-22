import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

type UseCountUpOptions = {
    duration?: number;
    delay?: number;
};

export function useCountUp(
    target: () => number,
    options: UseCountUpOptions = {},
) {
    const { duration = 1100, delay = 0 } = options;
    const value = ref(0);
    let raf: number | null = null;

    const animate = () => {
        if (raf !== null) {
            cancelAnimationFrame(raf);
        }

        const from = value.value;
        const to = target();
        const startTime = performance.now() + delay;

        const tick = (now: number) => {
            const elapsed = Math.max(0, now - startTime);
            const progress = Math.min(1, elapsed / duration);
            const eased = 1 - Math.pow(1 - progress, 3);

            value.value = from + (to - from) * eased;

            if (progress < 1) {
                raf = requestAnimationFrame(tick);
            } else {
                value.value = to;
                raf = null;
            }
        };

        raf = requestAnimationFrame(tick);
    };

    onMounted(animate);
    watch(target, animate);

    onBeforeUnmount(() => {
        if (raf !== null) {
            cancelAnimationFrame(raf);
        }
    });

    return value;
}
