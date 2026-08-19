const TOKEN_KEY = 'access_token';
const EXPIRES_AT_KEY = 'access_token_expires_at';

export function setAuthToken(token: string, expiresIn: number): void {
    const expiresAt = Date.now() + expiresIn * 1000;
    localStorage.setItem(TOKEN_KEY, token);
    localStorage.setItem(EXPIRES_AT_KEY, expiresAt.toString());
}

export function getAuthToken(): string | null {
    return localStorage.getItem(TOKEN_KEY);
}

export function getTokenExpiresAt(): number | null {
    const value = localStorage.getItem(EXPIRES_AT_KEY);

    if (!value) {
        return null;
    }

    return Number(value);
}

export function clearAuthToken(): void {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(EXPIRES_AT_KEY);
}