<script setup lang="ts">
const {
    type = 'button',
    disabled = false,
    loading = false,
    variant = 'primary',
} = defineProps<{
    type?: 'button' | 'submit' | 'reset';
    disabled?: boolean;
    loading?: boolean;
    variant?: 'primary' | 'secondary';
}>();
</script>
<template>
    <button class="retro-button" :class="`retro-button--${variant}`" :type="type" :disabled="disabled || loading">
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

    transition:
        color  var(--transition-duration) ease,
        background-color  var(--transition-duration) ease,
        border-color  var(--transition-duration) ease,
        box-shadow  var(--transition-duration) ease,
        filter  var(--transition-duration) ease;
}

.retro-button:hover:not(:disabled) {
    filter: brightness(1.08);
}

.retro-button:disabled {
    cursor: wait;
    opacity: 0.65;
}

.retro-button--secondary {
    border-color: var(--color-primary);

    color: var(--color-primary);
    background: var(--color-surface);
}

.retro-button--secondary:hover:not(:disabled) {
    color: var(--color-on-primary);
    background: var(--color-primary);

    box-shadow: 0 0 12px rgb(0 255 128 / 25%);
}

.retro-button--secondary:focus-visible {
    outline: 2px solid var(--color-primary);
    outline-offset: 3px;
}
</style>
