<script setup lang="ts">
withDefaults(
    defineProps<{
        maxWidth?: string;
        padding?: 'sm' | 'md' | 'lg';
        eyebrow?: string;
        title?: string;
        description?: string;
    }>(),
    {
        maxWidth: '28rem',
        padding: 'md',
        eyebrow: '',
        title: '',
        description: '',
    },
);
</script>

<template>
    <section
        class="retro-form-card"
        :class="`retro-form-card--padding-${padding}`"
        :style="{
            maxWidth,
        }"
    >
        <div v-if="eyebrow" class="retro-form-card__topbar">
            <span class="retro-form-card__eyebrow">
                {{ eyebrow }}
            </span>

            <span class="retro-form-card__topbar-decoration" aria-hidden="true">
                <i></i>
                <i></i>
                <i></i>
            </span>
        </div>
        <div class="retro-form-card__body">
            <header v-if="title || description || $slots.header" class="retro-form-card__header">
                <h1 v-if="title" class="retro-form-card__title">{{ title }}</h1>
                <p v-if="description" class="retro-form-card__description">{{ description }}</p>
                <slot name="header" />
            </header>

            <div class="retro-form-card__content">
                <slot />
            </div>
            <footer v-if="$slots.footer" class="retro-form-card__footer">
                <slot name="footer" />
            </footer>
        </div>
    </section>
</template>

<style scoped>
.retro-form-card {
    width: 100%;
    box-sizing: border-box;

    overflow: hidden;

    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);

    background: var(--color-surface);

    font-family: var(--font-retro);

    box-shadow: var(--shadow-card);

    color: var(--color-text);
}

/*
 * Window/dialog title bar.
 */
.retro-form-card__topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-md);

    min-height: 2.25rem;

    padding: 0 var(--space-md);

    border-bottom: 1px solid var(--color-border);

    background: var(--color-surface-dark);
}

.retro-form-card__eyebrow {
    color: var(--color-primary);

    font-size: 0.6875rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}

.retro-form-card__topbar-decoration {
    display: flex;
    gap: 0.35rem;
}

.retro-form-card__topbar-decoration i {
    width: 0.4rem;
    height: 0.4rem;

    border: 1px solid var(--color-primary);

    opacity: 0.65;
}

.retro-form-card__body {
    box-sizing: border-box;
}

.retro-form-card--padding-sm .retro-form-card__body {
    padding: var(--space-md);
}

.retro-form-card--padding-md .retro-form-card__body {
    padding: 1.25rem;
}

.retro-form-card--padding-lg .retro-form-card__body {
    padding: var(--space-xl);
}

.retro-form-card__header {
    margin-bottom: var(--space-xl);
}

.retro-form-card__title {
    color: var(--color-text);

    font-size: clamp(1.5rem, 6vw, 2rem);
    font-weight: 800;
    line-height: 1.15;
    letter-spacing: -0.03em;
}

.retro-form-card__description {
    max-width: 22rem;

    margin: var(--space-sm) 0 0 var(--space-md);

    color: var(--color-text-muted);

    font-size: 0.75rem;
    line-height: 1.5;
}

.retro-form-card__footer {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: var(--space-xs);

    margin-top: var(--space-lg);

    color: var(--color-text-muted);

    font-size: 0.8rem;
}

.retro-form-card__footer :deep(a) {
    color: var(--color-primary);

    font-weight: 800;
    text-decoration: none;
}

.retro-form-card__footer :deep(a:hover) {
    text-decoration: underline;
}

@media (min-width: 40rem) {
    .retro-form-card--padding-md .retro-form-card__body {
        padding: var(--space-xl);
    }
    .retro-form-card__description {
        margin-left: var(--space-md);
    }
}
</style>
