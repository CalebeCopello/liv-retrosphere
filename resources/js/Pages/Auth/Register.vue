<script setup lang="ts">
import { computed, reactive, ref } from 'vue';

import { register } from '../../api/auth';

import type { ApiErrors, AuthErrorResponse, RegisterCredentials, RegisterResponse } from '../../types/auth';

import AuthLayout from '../../layouts/AuthLayout.vue';
import RetroButton from '../../components/ui/RetroButton.vue';
import RetroCard from '../../components/ui/RetroCard.vue';

const form = reactive<RegisterCredentials>({
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const errors = ref<ApiErrors>({});
const message = ref<string | null>(null);
const loading = ref(false);

const passwordsMatch = computed(() => {
    return !form.password_confirmation || !form.password || form.password === form.password_confirmation;
});

function isSuccess(response: RegisterResponse | AuthErrorResponse): response is RegisterResponse {
    return (
        'data' in response && response.data !== null && response.data !== undefined && 'access_token' in response.data
    );
}

async function submit(): Promise<void> {
    errors.value = {};
    message.value = null;

    if (form.password !== form.password_confirmation) {
        errors.value.password_confirmation = ['The password confirmation does not match.'];

        return;
    }

    loading.value = true;

    try {
        const response = await register(form);

        if (!isSuccess(response)) {
            if ('errors' in response && response.errors) {
                errors.value = response.errors;
            } else {
                message.value = response.message;
            }

            return;
        }
        const token = response.data.access_token;

        localStorage.setItem('access_token', token);

        window.location.href = '/';
    } catch (error) {
        message.value = 'Something went wrong while creating your account. Please try again.';
        console.error(error);
    } finally {
        loading.value = false;
    }
}
</script>
<template>
    <AuthLayout>
        <RetroCard class="register-card">
            <div class="register-card__header">
                <span class="register-card__eyebrow"> NEW PLAYER </span>
                <h1 class="register-card__title">Create account</h1>
                <p class="register-card__description">
                    Join the community and start building your retro gaming profile.
                </p>
            </div>

            <form class="register-form" novalidate @submit.prevent="submit">
                <div class="register-form__field">
                    <label for="username" class="register-form__label"> Username </label>
                    <input
                        type="text"
                        id="username"
                        v-model="form.username"
                        class="register-form__input"
                        :class="{ 'register-form__input--error': errors.username }"
                        name="username"
                        autocomplete="username"
                        minlength="4"
                        maxlength="32"
                        placeholder="playerOne"
                    />
                    <p class="register-form__error" v-if="errors.username">{{ errors.username[0] }}</p>
                </div>
                <div class="register-form__field">
                    <label for="email" class="register-form__label"> Email </label>
                    <input
                        type="email"
                        id="email"
                        v-model="form.email"
                        class="register-form__input"
                        :class="{ 'register-form__input--error': errors.email }"
                        name="email"
                        autocomplete="email"
                        minlength="4"
                        maxlength="255"
                        placeholder="player@example.com"
                    />
                    <p class="register-form__error" v-if="errors.email">{{ errors.email[0] }}</p>
                </div>
                <div class="register-form__field">
                    <label for="password" class="register-form__label"> Password </label>
                    <input
                        type="password"
                        id="password"
                        v-model="form.password"
                        class="register-form__input"
                        :class="{ 'register-form__input--error': errors.password }"
                        name="password"
                        autocomplete="new-password"
                        minlength="8"
                        maxlength="32"
                        placeholder="password"
                    />
                    <p class="register-form__error" v-if="errors.password">{{ errors.password[0] }}</p>
                </div>
                <div class="register-form__field">
                    <label for="password-confirmation" class="register-form__label"> Confirm password </label>
                    <input
                        type="password"
                        id="password-confirmation"
                        v-model="form.password_confirmation"
                        class="register-form__input"
                        :class="{
                            'register-form__input--error': errors.password_confirmation || !passwordsMatch,
                        }"
                        name="password_confirmation"
                        autocomplete="new-password"
                        minlength="8"
                        maxlength="32"
                        placeholder="confirm password"
                    />
                    <p v-if="errors.password_confirmation" class="register-form__error">
                        {{ errors.password_confirmation[0] }}
                    </p>

                    <p v-else-if="!passwordsMatch" class="register-form__error">Passwords do not match.</p>
                </div>
                <div class="register-form__message" role="alert" v-if="message">{{ message }}</div>
                <RetroButton type="submit" :loading="loading" :disabled="loading" class="register-form__submit">
                    {{ loading ? 'CREATING PLAYER...' : 'CREATE ACCOUNT' }}</RetroButton
                >
            </form>
            <div class="register-card__footer">
                <span>Already registered?</span>
                <a href="/login" class="register-card__link">LOGIN</a>
            </div>
        </RetroCard>
    </AuthLayout>
</template>
<style scoped>
.register-card {
    width: 100%;
    max-width: 30rem;
}

.register-card__header {
    display: flex;
    flex-direction: column;
    gap: var(--space-sm);

    margin-bottom: var(--space-xl);
}

.register-card__eyebrow {
    color: var(--color-primary);

    font-family: monospace;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.12em;
}

.register-card__title {
    margin: 0;

    color: var(--color-text);

    font-size: clamp(1.75rem, 8vw, 2.5rem);
    line-height: 1;
}

.register-card__description {
    margin: 0;

    color: var(--color-text-muted);

    font-size: 0.875rem;
    line-height: 1.6;
}

.register-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-lg);
}

.register-form__field {
    display: flex;
    flex-direction: column;
    gap: var(--space-xs);
}

.register-form__label {
    color: var(--color-text);

    font-family: monospace;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.register-form__input {
    width: 100%;
    min-height: var(--control-height);

    padding: 0 var(--space-md);

    border: 1px solid var(--color-border);
    border-radius: var(--radius-sm);

    outline: none;

    color: var(--color-text);
    background: var(--color-surface);

    font: inherit;

    transition:
        border-color 120ms ease,
        box-shadow 120ms ease;
}

.register-form__input::placeholder {
    color: var(--color-text-muted);
}

.register-form__input:hover {
    border-color: var(--color-primary);
}

.register-form__input:focus {
    border-color: var(--color-primary);

    box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-primary) 20%, transparent);
}

.register-form__input--error {
    border-color: var(--color-danger);
}

.register-form__error {
    margin: 0;

    color: var(--color-danger);

    font-family: monospace;
    font-size: 0.75rem;
}

.register-form__message {
    padding: var(--space-md);

    border: 1px solid var(--color-danger);
    border-radius: var(--radius-sm);

    color: var(--color-danger);
    background: color-mix(in srgb, var(--color-danger) 8%, transparent);

    font-family: monospace;
    font-size: 0.75rem;
}

.register-form__submit {
    width: 100%;
}

.register-card__footer {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: var(--space-xs);

    margin-top: var(--space-xl);

    color: var(--color-text-muted);

    font-size: 0.8rem;
}

.register-card__link {
    color: var(--color-primary);

    font-family: monospace;
    font-weight: 800;
    text-decoration: none;
}

.register-card__link:hover {
    text-decoration: underline;
}

@media (min-width: 40rem) {
    .register-card__description {
        font-size: 0.9375rem;
    }
}
</style>
