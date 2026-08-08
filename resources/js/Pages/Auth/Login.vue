<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

import { login } from '../../api/auth';

import type { AuthErrorResponse, LoginCredentials, LoginResponse, RateLimitErrorResponse } from '../../types/auth';

import RetroButton from '../../components/ui/RetroButton.vue';

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
                <RetroButton type="submit" :loading="isSubmitting">
                    {{ isSubmitting ? 'CONNECTING...' : 'START SESSION' }}
                </RetroButton>
            </form>
        </section>
    </main>
</template>

<style scoped>
.login-page {
    --page-background: #10121a;
    --card-background: #1a1d29;
    --input-background: #10121a;
    --border: #393e52;
    --text: #f6f3e8;
    --muted: #a8adbd;
    --accent: #8cff98;
    --accent-text: #102014;
    --danger: #ff9090;
    --success-background: rgb(140 255 152 / 8%);
    --error-background: rgb(255 144 144 / 8%);

    display: grid;
    min-height: 100vh;
    padding: 1rem;
    place-items: center;

    color: var(--text);

    background:
        linear-gradient(rgb(255 255 255 / 2%) 1px, transparent 1px),
        linear-gradient(90deg, rgb(255 255 255 / 2%) 1px, transparent 1px), var(--page-background);

    background-size: 24px 24px;
}

.login-card {
    width: 100%;
    max-width: 28rem;
    box-sizing: border-box;
    padding: 1.25rem;

    border: 1px solid var(--border);
    border-radius: 1rem;

    background: var(--card-background);
    box-shadow: 0 1.5rem 4rem rgb(0 0 0 / 35%);
}

.login-header {
    margin-bottom: 1.5rem;
}

.login-eyebrow {
    margin: 0 0 0.5rem;

    color: var(--accent);

    font-family: monospace;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.16em;
}

.login-header h1 {
    margin: 0;

    font-size: clamp(2rem, 10vw, 3rem);
    line-height: 1;
}

.login-introduction {
    margin: 0.75rem 0 0;

    color: var(--muted);
    line-height: 1.6;
}

.message {
    display: grid;
    gap: 0.25rem;

    margin-bottom: 1rem;
    padding: 0.875rem;

    border: 1px solid;
    border-radius: 0.5rem;

    line-height: 1.5;
}

.message--success {
    border-color: var(--accent);
    color: var(--accent);
    background: var(--success-background);
}

.message--error {
    border-color: var(--danger);
    color: var(--danger);
    background: var(--error-background);
}

.login-form {
    display: grid;
    gap: 1.25rem;
}

.form-field {
    display: grid;
    gap: 0.5rem;
}

.form-field label {
    font-size: 0.875rem;
    font-weight: 700;
}

.form-field input {
    width: 100%;
    min-height: 3rem;
    box-sizing: border-box;
    padding: 0.75rem;

    border: 1px solid var(--border);
    border-radius: 0.5rem;
    outline: none;

    color: var(--text);
    background: var(--input-background);

    font: inherit;
}

.form-field input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgb(140 255 152 / 12%);
}

.form-field input[aria-invalid='true'] {
    border-color: var(--danger);
}

.form-field input:disabled {
    cursor: not-allowed;
    opacity: 0.65;
}

.field-error {
    margin: 0;

    color: var(--danger);

    font-size: 0.8125rem;
    line-height: 1.4;
}

.submit-button {
    min-height: 3rem;
    padding: 0.75rem 1rem;

    border: 1px solid var(--accent);
    border-radius: 0.5rem;

    cursor: pointer;

    color: var(--accent-text);
    background: var(--accent);

    font-family: monospace;
    font-size: 0.875rem;
    font-weight: 800;
    letter-spacing: 0.08em;
}

.submit-button:hover:not(:disabled) {
    filter: brightness(1.08);
    transform: translateY(-1px);
}

.submit-button:disabled {
    cursor: wait;
    opacity: 0.65;
}

@media (min-width: 40rem) {
    .login-page {
        padding: 2rem;
    }

    .login-card {
        padding: 2rem;
    }
}
</style>
