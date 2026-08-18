import type {
    AuthErrorResponse,
    LoginCredentials,
    LoginResponse,
    RefreshResponse,
    RegisterCredentials,
    RegisterResponse,
} from '../types/auth';

import { apiRoutes } from './routes';

export async function login(credentials: LoginCredentials): Promise<LoginResponse | AuthErrorResponse> {
    const response = await fetch(apiRoutes.auth.login, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(credentials),
    });

    const payload = (await response.json()) as LoginResponse | AuthErrorResponse;

    if (response.ok) {
        return payload as LoginResponse;
    }

    return payload as AuthErrorResponse;
}

export async function register(credentials: RegisterCredentials): Promise<RegisterResponse | AuthErrorResponse> {
    const response = await fetch(apiRoutes.auth.register, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(credentials),
    });

    const payload = (await response.json()) as RegisterResponse | AuthErrorResponse;

    if (response.ok) {
        return payload as RegisterResponse;
    }

    return payload as AuthErrorResponse;
}

export async function refresh(token: string): Promise<RefreshResponse | AuthErrorResponse> {
    const response = await fetch(apiRoutes.auth.refresh, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
        },
    });

    const payload = (await response.json()) as RefreshResponse | AuthErrorResponse;

    if (response.ok) {
            return payload as RefreshResponse;
    }

    return payload as AuthErrorResponse;
}

export async function me(token: string): Promise<RefreshResponse | AuthErrorResponse> {
    const response = await fetch(apiRoutes.auth.me, {
        method: 'GET',
        headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
        },
    });

    const payload = (await response.json()) as RefreshResponse | AuthErrorResponse;

    if (response.ok) {
            return payload as RefreshResponse;
    }

    return payload as AuthErrorResponse;
}
