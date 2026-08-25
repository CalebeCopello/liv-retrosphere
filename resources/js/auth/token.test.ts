import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import { refresh } from '../api/auth';

import type {
    AuthErrorResponse,
    RefreshResponse,
} from '../types/auth';

import {
    getAuthToken,
    getTokenExpiresAt,
    getValidAuthToken,
    setAuthToken,
} from './token';

vi.mock('../api/auth', () => ({
    refresh: vi.fn(),
}));

const refreshMock = vi.mocked(refresh);

const NOW = new Date(
    '2026-08-24T12:00:00.000Z',
).getTime();

const authenticatedUser = {
    id: '4d3dd81c-e98d-4cb3-b2e3-f63af12eb084',
    username: 'testUser',
    display_name: 'testUser1234',
    email: 'testuser@email.com',
    created_at: '2026-07-31T22:00:00.000000Z',
    updated_at: '2026-07-31T22:00:00.000000Z',
};

function createLocalStorageMock(): Storage {
    const values = new Map<string, string>();

    return {
        get length(): number {
            return values.size;
        },

        clear(): void {
            values.clear();
        },

        getItem(key: string): string | null {
            return values.get(key) ?? null;
        },

        key(index: number): string | null {
            return Array.from(values.keys())[index] ?? null;
        },

        removeItem(key: string): void {
            values.delete(key);
        },

        setItem(key: string, value: string): void {
            values.set(key, String(value));
        },
    };
}

describe('authentication token lifecycle', () => {
    beforeEach(() => {
        vi.stubGlobal(
            'localStorage',
            createLocalStorageMock(),
        );

        refreshMock.mockReset();

        vi.spyOn(Date, 'now').mockReturnValue(NOW);
    });

    afterEach(() => {
        vi.restoreAllMocks();
        vi.unstubAllGlobals();
    });

    it('returns null when no token exists', async () => {
        const result = await getValidAuthToken();

        expect(result).toBeNull();
        expect(refreshMock).not.toHaveBeenCalled();
    });

    it('clears storage and returns null when the token is expired', async () => {
        localStorage.setItem(
            'access_token',
            'expired-token',
        );

        localStorage.setItem(
            'access_token_expires_at',
            String(NOW - 1),
        );

        const result = await getValidAuthToken();

        expect(result).toBeNull();
        expect(getAuthToken()).toBeNull();
        expect(getTokenExpiresAt()).toBeNull();
        expect(refreshMock).not.toHaveBeenCalled();
    });

    it('returns the existing token when it is far from expiry', async () => {
        const expiresInThirtyDays =
            30 * 24 * 60 * 60;

        setAuthToken(
            'existing-token',
            expiresInThirtyDays,
        );

        const result = await getValidAuthToken();

        expect(result).toBe('existing-token');
        expect(getAuthToken()).toBe(
            'existing-token',
        );

        expect(refreshMock).not.toHaveBeenCalled();
    });

    it('refreshes, stores, and returns a token near expiry', async () => {
        const expiresInOneHour = 60 * 60;

        setAuthToken(
            'existing-token',
            expiresInOneHour,
        );

        const responsePayload: RefreshResponse = {
            message: 'Your token was refreshed.',
            data: {
                access_token: 'refreshed-token',
                token_type: 'bearer',
                expires_in: 30 * 24 * 60 * 60,
                user: authenticatedUser,
            },
            errors: null,
        };

        refreshMock.mockResolvedValue(
            responsePayload,
        );

        const result = await getValidAuthToken();

        expect(refreshMock).toHaveBeenCalledOnce();

        expect(refreshMock).toHaveBeenCalledWith(
            'existing-token',
        );

        expect(result).toBe('refreshed-token');

        expect(getAuthToken()).toBe(
            'refreshed-token',
        );

        expect(getTokenExpiresAt()).toBe(
            NOW +
                responsePayload.data.expires_in *
                    1000,
        );
    });

    it('clears storage and returns null when refresh fails', async () => {
        const expiresInOneHour = 60 * 60;

        setAuthToken(
            'existing-token',
            expiresInOneHour,
        );

        const responsePayload: AuthErrorResponse = {
            message:
                'Token could not be refreshed.',
            data: null,
            errors: {
                token: [
                    'Token could not be refreshed.',
                ],
            },
        };

        refreshMock.mockResolvedValue(
            responsePayload,
        );

        const result = await getValidAuthToken();

        expect(refreshMock).toHaveBeenCalledOnce();

        expect(refreshMock).toHaveBeenCalledWith(
            'existing-token',
        );

        expect(result).toBeNull();
        expect(getAuthToken()).toBeNull();
        expect(getTokenExpiresAt()).toBeNull();
    });
});