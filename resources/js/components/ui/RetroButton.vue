<script setup lang="ts">
const {
    type = 'button',
    disabled = false,
    loading = false,
} = defineProps<{
    type?: 'button' | 'submit' | 'reset';
    disabled?: boolean;
    loading?: boolean;
}>();
</script>
<template>
    <button class="retro-button" :type="type" :disabled="disabled || loading">
        <slot />
    </button>
</template>
<style scoped>
.retro-button {
    min-height: var(--control-height);
    padding: 0.75rem var(--space-md);
    border: 1px solid var(--color-primary);

    border-radius: var(--radius-sm);

    cursor: pointer;

    color: var(--color-on-primary);
    background: var(--color-primary);

    font-family: monospace;
    font-size: 0.875rem;
    font-weight: 800;
    letter-spacing: 0.08em;
}

.retro-button:hover:not(:disabled) {
    filter: brightness(1.08);
    transform: translateY(-1px);
}

.retro-button:disabled {
    cursor: wait;
    opacity: 0.65;
}
</style>
