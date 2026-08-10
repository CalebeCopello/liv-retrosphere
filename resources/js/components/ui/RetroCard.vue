<script setup lang="ts">
withDefaults(
    defineProps<{
        maxWidth?: string;
    }>(),
    {
        maxWidth: '28rem',
    },
);
</script>

<template>
    <section
        class="retro-card"
        :style="{
            maxWidth,
        }"
    >

        <header
            v-if="$slots.header"
            class="retro-card__header"
        >
            <slot name="header" />
        </header>

        <div class="retro-card__content">
            <slot />
        </div>
        <footer
            v-if="$slots.footer"
            class="retro-card__footer"
        >
            <slot name="footer" />
        </footer>
    </section>
</template>

<style scoped>
.retro-card {
    width: 100%;
    box-sizing: border-box;

    padding: 1.25rem;

    border:
        1px solid
        var(--color-border);

    border-radius: var(--radius-md);

    background: var(--color-surface);

    box-shadow: var(--shadow-card);
}

.retro-card__header {
    margin-bottom: var(--space-lg);
}

.retro-card__content {
    /*
     * The content area itself does not force a layout.
     *
     * The page or form inside controls its own structure.
     */
}

.retro-card__footer {
    margin-top: var(--space-lg);
}

@media (min-width: 40rem) {
    .retro-card {
        padding: var(--space-xl);
    }
}
</style>