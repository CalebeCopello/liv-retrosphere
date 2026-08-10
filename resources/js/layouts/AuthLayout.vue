<template>
    <main class="auth-layout">
        <slot />
    </main>
</template>

<style scoped>
.auth-layout {
    display: grid;
    min-height: 100vh;
    padding: var(--space-md);
    box-sizing: border-box;
    place-items: center;
}

@media (min-width: 40rem) {
    .auth-layout {
        padding: var(--space-xl);
    }
}
</style>
