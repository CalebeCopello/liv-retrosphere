import type {
    AuthErrorResponse,
    LoginCredentials,
    LoginResponse,
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
