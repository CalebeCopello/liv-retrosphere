import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import type {
    ApiErrorResponse,
    LoginCredentials,
    LoginResponse,
    LogoutResponse,
    LogoutAllResponse,
    RateLimitErrorResponse,
    ValidationErrorResponse,
} from '../types/auth';

import { login, logout, logoutAll } from './auth';
import { apiRoutes } from './routes';

const credentials: LoginCredentials = {
    email: 'testuser@email.com',
    password: 'password123',
};

const authenticatedUser = {
    id: '4d3dd81c-e98d-4cb3-b2e3-f63af12eb084',
    username: 'testUser',
    display_name: 'testUser1234',
    email: 'testuser@email.com',
    created_at: '2026-07-31T22:00:00.000000Z',
    updated_at: '2026-07-31T22:00:00.000000Z',
};

const fetchMock = vi.fn<typeof fetch>();

function jsonResponse(payload: unknown, status: number): Response {
    return new Response(JSON.stringify(payload), {
        status,
        headers: {
            'Content-Type': 'application/json',
        },
    });
}

describe('login API boundary', () => {
    beforeEach(() => {
        fetchMock.mockReset();
        vi.stubGlobal('fetch', fetchMock);
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('returns the successful login response', async () => {
        const responsePayload: LoginResponse = {
            message: 'You are logged in.',
            data: {
                access_token: 'example-jwt-token',
                token_type: 'bearer',
                expires_in: 3600,
                user: authenticatedUser,
            },
            errors: null,
        };

        fetchMock.mockResolvedValue(jsonResponse(responsePayload, 200));

        const result = await login(credentials);

        expect(fetchMock).toHaveBeenCalledOnce();

        expect(fetchMock).toHaveBeenCalledWith(apiRoutes.auth.login, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(credentials),
        });

        expect(result).toEqual(responsePayload);
    });

    it('returns the incorrect-credentials response', async () => {
        const responsePayload: ApiErrorResponse = {
            message: 'The provided credentials are incorrect.',
            data: null,
            errors: {
                credentials: ['The provided credentials are incorrect.'],
            },
        };

        fetchMock.mockResolvedValue(jsonResponse(responsePayload, 401));

        const result = await login({
            ...credentials,
            password: 'wrong-password',
        });

        expect(result).toEqual(responsePayload);
    });

    it('returns the validation failure response', async () => {
        const responsePayload: ValidationErrorResponse = {
            message: 'The email field is required. (and 1 more error)',
            errors: {
                email: ['The email field is required.'],
                password: ['The password field is required.'],
            },
        };

        fetchMock.mockResolvedValue(jsonResponse(responsePayload, 422));

        const result = await login({
            email: '',
            password: '',
        });

        expect(result).toEqual(responsePayload);
        expect('data' in result).toBe(false);
    });

    it('returns the rate-limit response', async () => {
        const responsePayload: RateLimitErrorResponse = {
            message: 'Too many requests.',
            retry_after: 60,
        };

        fetchMock.mockResolvedValue(jsonResponse(responsePayload, 429));

        const result = await login(credentials);

        expect(result).toEqual(responsePayload);

        expect('retry_after' in result ? result.retry_after : undefined).toBe(60);
    });
});

describe('logout API boundary', () => {
    beforeEach(() => {
        fetchMock.mockReset();
        vi.stubGlobal('fetch', fetchMock);
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('sends the token and returns the successful logout response', async () => {
        const token = 'example-jwt-token';
        const responsePayload: LogoutResponse = {
            message: 'You logged out.',
            data: {
                access_token: null,
                token_type: null,
                expires_in: null,
                user: authenticatedUser,
            },
            errors: null,
        };

        fetchMock.mockResolvedValue(jsonResponse(responsePayload, 200));

        const result = await logout(token);

        expect(fetchMock).toHaveBeenCalledOnce();
        expect(fetchMock).toHaveBeenCalledWith(apiRoutes.auth.logout, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });
        expect(result).toEqual(responsePayload);
    });
});

describe('logout-all API boundary', () => {
    beforeEach(() => {
        fetchMock.mockReset();
        vi.stubGlobal('fetch', fetchMock);
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('sends the token and returns the successful logout-all response', async () => {
        const token = 'example-jwt-token';

        const responsePayload: LogoutAllResponse = {
            message: 'You logged out from all devices.',
            data: {
                access_token: null,
                token_type: null,
                expires_in: null,
                user: authenticatedUser,
            },
            errors: null,
        };

        fetchMock.mockResolvedValue(jsonResponse(responsePayload, 200));

        const result = await logoutAll(token);

        expect(fetchMock).toHaveBeenCalledOnce();

        expect(fetchMock).toHaveBeenCalledWith(apiRoutes.auth.logoutAll, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });

        expect(result).toEqual(responsePayload);
    });
});
