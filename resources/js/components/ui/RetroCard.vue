<script setup lang="ts">
withDefaults(
    defineProps<{
        maxWidth?: string;
        padding?: 'sm' | 'md' | 'lg';
    }>(),
    {
        maxWidth: '28rem',
        padding: 'md',
    },
);
</script>

<template>
    <section
        class="retro-card"
        :class="`retro-card--padding-${padding}`"
        :style="{
            maxWidth,
        }"
    >
        <header v-if="$slots.header" class="retro-card__header">
            <slot name="header" />
        </header>

        <div class="retro-card__content">
            <slot />
        </div>
        <footer v-if="$slots.footer" class="retro-card__footer">
            <slot name="footer" />
        </footer>
    </section>
</template>

<style scoped>
.retro-card {
    width: 100%;
    box-sizing: border-box;

    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);

    background: var(--color-surface);

    box-shadow: var(--shadow-card);
}
.retro-card--padding-sm {
    padding: var(--space-md);
}

.retro-card--padding-md {
    padding: 1.25rem;
}

.retro-card--padding-lg {
    padding: var(--space-xl);
}

.retro-card__header {
    margin-bottom: var(--space-lg);
}

.retro-card__footer {
    margin-top: var(--space-lg);
}

@media (min-width: 40rem) {
    .retro-card--padding-md {
        padding: var(--space-xl);
    }
}
</style>
