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
        })

        if (isLoginSuccess(result)) {
            successMessage.value = result.message;
            authenticatedUserName.value = result.data?.user.username;
            return;
        }

        if(isRateLimitError(result)) {
            generalMessage.value = `${result.message} Try again in ${result.retry_after} seconds.`

            return;
        }

        if ('errors' in result && result.errors) {
            applyApiErrors(result.errors, result.message);

            return;
        }

        generalMessage.value = result.message;
    } catch (error) {
        generalMessage.value = 'The server could not be reached. Please try again.';
        console.error(error)
    } finally {
        isSubmitting.value = false;
    }
}
</script>
