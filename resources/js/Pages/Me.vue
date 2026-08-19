<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';

import { me } from '../api/auth';
import { getAuthToken } from '../auth/token';

import type { AuthUser } from '../types/auth';

import AuthLayout from '../layouts/AuthLayout.vue';
import RetroFormCard from '../components/ui/RetroFormCard.vue';

const user = ref<AuthUser | null>(null);
const message = ref('');
const loading = ref(true);

async function loadUser(): Promise<void> {
    const token = getAuthToken();

    if (!token) {
        message.value = 'No access token found.';
        loading.value = false;

        return;
    }

    try {
        const response = await me(token);

        if (!response.data) {
            message.value = response.message;

            return;
        }

        user.value = response.data.user;
        message.value = response.message;
    } catch (error) {
        message.value = 'Something went wrong while loading the user.';
        console.error(error);
    } finally {
        loading.value = false;
    }
}

onMounted(loadUser);
</script>

<template>
    <Head title="Me" />

    <AuthLayout>
        <RetroFormCard
            max-width="36rem"
            eyebrow="AUTH TEST"
            title="/me"
            description="Testing the current authentication token."
        >
            <p v-if="loading">Loading...</p>

            <div v-else-if="user">
                <p>{{ message }}</p>

                <pre>{{ JSON.stringify(user, null, 2) }}</pre>
            </div>

            <p v-else>
                {{ message }}
            </p>
        </RetroFormCard>
    </AuthLayout>
</template>

<style scoped>
pre {
    overflow-x: auto;

    padding: var(--space-md);

    border: 1px solid var(--color-border);
    border-radius: var(--radius-sm);

    color: var(--color-text);
    background: var(--color-surface);

    font-family: monospace;
    font-size: 0.75rem;
}

p {
    color: var(--color-text);

    font-family: monospace;
}
</style>