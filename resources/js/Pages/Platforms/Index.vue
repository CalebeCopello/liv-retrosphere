<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

interface Platform {
    id: string;
    name: string;
    slug: string;
    shortname: string | null;
    type: string;
    generation: number;
    intial_release_year: number;
    description: string;
    image: string;
    source_url: string;
}

defineProps<{
    platforms: Platform[];
}>();
</script>

<template>
    <Head title="Platforms" />

    <main class="platforms-page">
        <header>
            <p class="eyebrow">RETROSPHERE CATALOG</p>
            <h1>Platforms</h1>
            <p>Explore the hardware that shaped video games.</p>
        </header>

        <section v-if="platforms.length" class="platform-grid">
            <article v-for="platform in platforms" :key="platform.id" class="platform-card">
                <p v-if="platform.shortname">
                    {{ platform.shortname }}
                </p>

                <img v-if="platform.image" :src="`/storage/platforms/${platform.image}`" :alt="platform.name" />

                <h2>{{ platform.name }}</h2>

                <dl>
                    <div>
                        <dt>Type</dt>
                        <dd>{{ platform.type }}</dd>
                    </div>

                    <div v-if="platform.generation">
                        <dt>Generation</dt>
                        <dd>{{ platform.generation }}</dd>
                    </div>

                    <div v-if="platform.intial_release_year">
                        <dt>Released</dt>
                        <dd>{{ platform.intial_release_year }}</dd>
                    </div>
                </dl>
            </article>
        </section>

        <p v-else>No platforms are available yet.</p>
    </main>
</template>

<style scoped>
.platforms-page {
    max-width: 72rem;
    margin: 0 auto;
    padding: var(--space-xl);
}

.eyebrow {
    color: var(--color-primary);
    font-family: var(--font-retro);
    font-size: 0.75rem;
}

.platform-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));
    gap: var(--space-lg);
    margin-top: var(--space-xl);
}

.platform-card {
    padding: var(--space-lg);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-surface);
    box-shadow: var(--shadow-card);
}

dt {
    color: var(--color-text-muted);
}

dd {
    margin: 0;
}
</style>
