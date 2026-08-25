import { refresh } from '../api/auth';

import type { RefreshResponse } from '../types/auth';

const TOKEN_KEY = 'access_token';
const EXPIRES_AT_KEY = 'access_token_expires_at';
const REFRESH_THRESHOLD_MS = 24 * 60 * 60 * 1000;

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

    const expiresAt = Number(value);

    return Number.isFinite(expiresAt) ? expiresAt : null;
}

export function clearAuthToken(): void {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(EXPIRES_AT_KEY);
}

function isRefreshSuccess(response: unknown): response is RefreshResponse {
    if (typeof response !== 'object' || response === null) {
        return false;
    }

    const result = response as Partial<RefreshResponse>;

    return (
        result.data !== null &&
        result.data !== undefined &&
        typeof result.data.access_token === 'string' &&
        typeof result.data.expires_in === 'number'
    );
}

export async function getValidAuthToken(): Promise<string | null> {
    const token = getAuthToken();
    const expiresAt = getTokenExpiresAt();

    if (!token || expiresAt === null) {
        clearAuthToken();
        return null;
    }

    const remainingTime = expiresAt - Date.now();

    if (remainingTime <= 0) {
        clearAuthToken();

        return null;
    }

    if (remainingTime <= REFRESH_THRESHOLD_MS) {
        try {
            const response = await refresh(token);

            if (!isRefreshSuccess(response)) {
                clearAuthToken();

                return null;
            }

            setAuthToken(response.data.access_token, response.data.expires_in);

            return response.data.access_token;
        } catch {
            clearAuthToken();

            return null;
        }
    }

    return token;
}
