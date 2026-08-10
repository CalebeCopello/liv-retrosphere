<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

import { login } from '../../api/auth';

import type { AuthErrorResponse, LoginCredentials, LoginResponse, RateLimitErrorResponse } from '../../types/auth';

import AuthLayout from '../../layouts/AuthLayout.vue';
import RetroButton from '../../components/ui/RetroButton.vue';
import RetroCard from '../../components/ui/RetroCard.vue';

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
const authenticatedUsername = ref('');

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
    authenticatedUsername.value = '';
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
            authenticatedUsername.value = result.data?.user.username;
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
    <AuthLayout>
        <RetroCard>
            <template #header>
                <p class="login-eyebrow">PLAYER ACCESS</p>

                <h1 class="login-title">Log in</h1>

                <p class="login-introduction">Continue your journey through retro gaming.</p>
            </template>
            <div v-if="successMessage" class="message message--success" role="status">
                <strong>
                    {{ successMessage }}
                </strong>

                <span>
                    Authenticated as
                    {{ authenticatedUsername }}.
                </span>
            </div>

            <div v-if="generalMessage" class="message message--error" role="alert">
                {{ generalMessage }}
            </div>
            <form class="login-form" novalidate @submit.prevent="submit">
                <div class="form-field">
                    <label for="email"> Email </label>

                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        name="email"
                        autocomplete="email"
                        placeholder="player@example.com"
                        :disabled="isSubmitting"
                        :aria-invalid="fieldErrors.email ? 'true' : 'false'"
                        :aria-describedby="fieldErrors.email ? 'email-error' : undefined"
                    />

                    <p v-if="fieldErrors.email" id="email-error" class="field-error">
                        {{ fieldErrors.email }}
                    </p>
                </div>
                <div class="form-field">
                    <label for="password"> Password </label>

                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
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
                <RetroButton type="submit" :loading="isSubmitting">
                    {{ isSubmitting ? 'CONNECTING...' : 'START SESSION' }}
                </RetroButton>
            </form>
        </RetroCard>
    </AuthLayout>
</template>

<style scoped>
.login-eyebrow {
    margin: 0 0 var(--space-sm);

    color: var(--color-primary);

    font-family: monospace;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.16em;
}

.login-title {
    margin: 0;

    font-size: clamp(2rem, 10vw, 3rem);

    line-height: 1;
}

.login-introduction {
    margin: 0.75rem 0 0;

    color: var(--color-text-muted);

    line-height: 1.6;
}

.message {
    display: grid;

    gap: var(--space-xs);

    margin-bottom: var(--space-md);

    padding: 0.875rem;

    border: 1px solid;

    border-radius: var(--radius-sm);

    line-height: 1.5;
}

.message--success {
    border-color: var(--color-primary);

    color: var(--color-primary);

    background: var(--color-success-background);
}

.message--error {
    border-color: var(--color-danger);

    color: var(--color-danger);

    background: var(--color-danger-background);
}
.login-form {
    display: grid;

    gap: 1.25rem;
}

.form-field {
    display: grid;

    gap: var(--space-sm);
}

.form-field label {
    font-size: 0.875rem;

    font-weight: 700;
}

.form-field input {
    width: 100%;

    min-height: var(--control-height);

    box-sizing: border-box;

    padding: 0.75rem;

    border: 1px solid var(--color-border);

    border-radius: var(--radius-sm);

    outline: none;

    color: var(--color-text);

    background: var(--color-surface-dark);

    font: inherit;
}

/*
 * Custom keyboard/mouse focus indicator.
 */
.form-field input:focus {
    border-color: var(--color-primary);

    box-shadow: 0 0 0 3px rgb(140 255 152 / 12%);
}

.form-field input[aria-invalid='true'] {
    border-color: var(--color-danger);
}

.form-field input:disabled {
    cursor: not-allowed;

    opacity: 0.65;
}

.field-error {
    margin: 0;

    color: var(--color-danger);

    font-size: 0.8125rem;

    line-height: 1.4;
}
</style>
