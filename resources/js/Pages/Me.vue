<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';

import { logout, logoutAll, me } from '../api/auth';
import { clearAuthToken, getValidAuthToken } from '../auth/token';

import type { AuthUser } from '../types/auth';

import AuthLayout from '../layouts/AuthLayout.vue';
import RetroFormCard from '../components/ui/RetroFormCard.vue';
import RetroButton from '../components/ui/RetroButton.vue';

const user = ref<AuthUser | null>(null);
const message = ref('');
const loading = ref(true);

async function loadUser(): Promise<void> {
    try {
        const token = await getValidAuthToken();

        if (!token) {
            message.value = 'No access token found.';
            loading.value = false;

            return;
        }

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

async function submitLogout(): Promise<void> {
    const token = await getValidAuthToken();

    try {
        if (token) {
            await logout(token);
        }
    } catch (error) {
        console.error(error);
    } finally {
        clearAuthToken();
        user.value = null;
        message.value = 'You logged out.';
    }
}

async function submitLogoutAll(): Promise<void> {
    const token = await getValidAuthToken();
    try {
        if (token) {
            await logoutAll(token);
        }
    } catch (error) {
        console.error(error);
    } finally {
        clearAuthToken();
        user.value = null;
        message.value = 'You logged out from all devices.';
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
                <div>
                    <p>{{ message }}</p>
                    <pre>{{ JSON.stringify(user, null, 2) }}</pre>
                    <RetroButton type="button" variant="secondary" @click="submitLogout" style="margin-top: 1rem">
                        Logout
                    </RetroButton>
                </div>
                <div>
                    <RetroButton type="button" variant="secondary" @click="submitLogoutAll">
                        Logout from all devices
                    </RetroButton>
                </div>
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
