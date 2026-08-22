import { reactive } from 'vue';

interface ConfirmOptions {
    title?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    variant?: 'destructive' | 'default';
}

const state = reactive({
    open: false,
    title: 'Confirmar acción',
    message: '',
    confirmLabel: 'Confirmar',
    cancelLabel: 'Cancelar',
    variant: 'destructive' as 'destructive' | 'default',
    resolve: null as ((v: boolean) => void) | null,
});

export function useConfirm() {
    const confirm = (message: string, options?: ConfirmOptions): Promise<boolean> => {
        state.open = true;
        state.message = message;
        state.title = options?.title ?? 'Confirmar acción';
        state.confirmLabel = options?.confirmLabel ?? 'Confirmar';
        state.cancelLabel = options?.cancelLabel ?? 'Cancelar';
        state.variant = options?.variant ?? 'destructive';

        return new Promise((resolve) => {
            state.resolve = resolve;
        });
    };

    const accept = () => {
        state.open = false;
        state.resolve?.(true);
        state.resolve = null;
    };

    const dismiss = () => {
        state.open = false;
        state.resolve?.(false);
        state.resolve = null;
    };

    return { confirmState: state, confirm, accept, dismiss };
}
