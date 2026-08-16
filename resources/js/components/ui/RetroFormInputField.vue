<script setup lang="ts">
import { computed } from 'vue';
type InputType = 'text' | 'email' | 'password';

const props = withDefaults(
    defineProps<{
        id: string;
        label: string;
        type?: InputType;
        name?: string;
        autocomplete?: string;
        placeholder?: string;
        error?: string;
        disabled?: boolean;
        minLength?: number;
        maxLength?: number;
    }>(),
    {
        type: 'text',
        name: undefined,
        autocomplete: undefined,
        placeholder: undefined,
        error: undefined,
        disabled: false,
        minLength: undefined,
        maxLength: undefined,
    },
);

const model = defineModel<string>({
    required: true,
});

const errorId = computed(() => `${props.id}-error`);
</script>
<template>
    <div class="retro-form-field">
        <label class="retro-form-field__label" :for="id">
            {{ label }}
        </label>

        <input
            :id="id"
            v-model="model"
            class="retro-form-field__input"
            :class="{
                'retro-form-field__input--error': error,
            }"
            :type="type"
            :name="name ?? id"
            :autocomplete="autocomplete"
            :placeholder="placeholder"
            :disabled="disabled"
            :minlength="minLength"
            :maxlength="maxLength"
            :aria-invalid="error ? 'true' : 'false'"
            :aria-describedby="error ? errorId : undefined"
        />

        <p v-if="error" :id="errorId" class="retro-form-field__error">
            {{ error }}
        </p>
    </div>
</template>

<style scoped>
.retro-form-field {
    display: flex;
    flex-direction: column;
    gap: var(--space-xs);

    font-family: var(--font-retro);
}

.retro-form-field__label {
    color: var(--color-text);

    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.retro-form-field__input {
    width: 100%;
    min-height: var(--control-height);
    box-sizing: border-box;

    padding: 0 var(--space-md);

    border: 1px solid var(--color-border);
    border-radius: var(--radius-sm);

    outline: none;

    color: var(--color-text);
    background: var(--color-surface-dark);

    font-family: inherit;
    font-size: 0.8125rem;

    transition:
        border-color 180ms ease,
        box-shadow 180ms ease,
        background-color 180ms ease;
}

.retro-form-field__input:hover:not(:disabled):not(.retro-form-field__input--error) {
    border-color: var(--color-primary);

    box-shadow: 0 0 0 1px color-mix(in srgb, var(--color-primary) 12%, transparent);
}

.retro-form-field__input:focus {
    border-color: var(--color-primary);

    background: var(--color-surface);

    box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-primary) 20%, transparent);
}

.retro-form-field__input::placeholder {
    color: var(--color-text-muted);

    opacity: 0.7;
}

.retro-form-field__input:hover:not(:disabled) {
    border-color: var(--color-primary);
}

.retro-form-field__input:focus {
    border-color: var(--color-primary);

    background: var(--color-surface);

    box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-primary) 20%, transparent);
}

.retro-form-field__input--error {
    border-color: var(--color-danger);
}

.retro-form-field__input--error:focus {
    border-color: var(--color-danger);

    box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-danger) 20%, transparent);
}

.retro-form-field__input:disabled {
    cursor: not-allowed;

    opacity: 0.6;
}

.retro-form-field__error {
    margin: 0;

    color: var(--color-danger);

    font-size: 0.6875rem;
    font-weight: 600;
    line-height: 1.4;
}
</style>
