<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

import { login } from '../../api/auth';

import type { AuthErrorResponse, LoginCredentials, LoginResponse, RateLimitErrorResponse } from '../../types/auth';

type LoginResult = LoginResponse | AuthErrorResponse;

type LoginFieldErrors = Partial<Record<keyof LoginCredentials, string>>;

const form = reactive<LoginCredentials>({
    email: '',
    password: '',
});

const isSubmitting = ref(false);

const fieldErrors = ref<LoginFieldErrors>({});

const generalMessage = ref('');
const successMessage = ref('');
const authenticatedUserName = ref('');

function isLoginSuccess(result: LoginResult): result is LoginResponse {
    return 'data' in result && result.data !== null && result.data !== undefined;
}

function isRateLimitError(result: LoginResult): result is RateLimitErrorResponse {
    return 'retry_after' in result && typeof result.retry_after === 'number';
}

function clearMessages(): void {
    fieldErrors.value = {};
    generalMessage.value = '';
    successMessage.value = '';
    authenticatedUserName.value = '';
}

function applyApiErrors(errors: Record<string, string[]>, fallbackMessage: string): void {
    const emailError = errors.email?.[0];
    const passwordError = errors.password?.[0];
    const credentialsError = errors.credentials?.[0];

    if (emailError) {
        fieldErrors.value.email = emailError;
    }

    if (passwordError) {
        fieldErrors.value.password = passwordError;
    }

    if (credentialsError) {
        generalMessage.value = credentialsError;
        return;
    }

    const hasFieldErrors = Boolean(emailError) || Boolean(passwordError);

    if (!hasFieldErrors) {
        generalMessage.value = fallbackMessage;
    }
}

async function submit(): Promise<void> {
    if (isSubmitting.value) {
        return;
    }

    clearMessages();

    isSubmitting.value = true;

    try {
        const result = await login({
            email: form.email.trim().toLocaleLowerCase(),
            password: form.password,
        });

        if (isLoginSuccess(result)) {
            successMessage.value = result.message;
            authenticatedUserName.value = result.data?.user.username;
            return;
        }

        if (isRateLimitError(result)) {
            generalMessage.value = `${result.message} Try again in ${result.retry_after} seconds.`;

            return;
        }

        if ('errors' in result && result.errors) {
            applyApiErrors(result.errors, result.message);

            return;
        }

        generalMessage.value = result.message;
    } catch (error) {
        generalMessage.value = 'The server could not be reached. Please try again.';
        console.error(error);
    } finally {
        isSubmitting.value = false;
    }
}
</script>
<template>
    <Head title="Log in" />
    <main class="login-page">
        <section class="login-card">
            <header class="login-header">
                <p class="login-eyebrow">PLAYER ACCESS</p>
                <h1>Log in</h1>
                <p class="login-introduction">Continue your journey through retro gaming.</p>
            </header>
            <div v-if="successMessage" class="message message--success" role="status">
                <strong>{{ successMessage }}</strong>
                <span> Authenticated as {{ authenticatedUserName }} </span>
            </div>
            <div v-if="generalMessage" class="message message--error" role="alert">{{ generalMessage }}</div>
            <form class="login-form" novalidate @submit.prevent="submit">
                <div class="form-field">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        v-model="form.email"
                        name="email"
                        autocomplete="email"
                        placeholder="player@example.com"
                        :disabled="isSubmitting"
                        :aria-invalid="fieldErrors.email ? 'true' : 'false'"
                        :aria-describedby="fieldErrors.email ? 'email-error' : undefined"
                    />
                    <p v-if="fieldErrors.email" id="email-error" class="field-error">{{ fieldErrors.email }}</p>
                </div>
                <div class="form-field">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        v-model="form.password"
                        name="password"
                        autocomplete="current-password"
                        placeholder="Enter your password"
                        :disabled="isSubmitting"
                        :aria-invalid="fieldErrors.password ? 'true' : 'false'"
                        :aria-describedby="fieldErrors.password ? 'password-error' : undefined"
                    />
                    <p v-if="fieldErrors.password" id="password-error" class="field-error">
                        {{ fieldErrors.password }}
                    </p>
                </div>
                <button type="submit" class="submit-button" :disabled="isSubmitting">
                    {{ isSubmitting ? 'CONNECTING...' : 'START SESSION' }}
                </button>
            </form>
        </section>
    </main>
</template>
