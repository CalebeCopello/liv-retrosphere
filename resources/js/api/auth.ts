import type {
    AuthErrorResponse,
    LoginCredentials,
    LoginResponse,
    LogoutResponse,
    LogoutAllResponse,
    MeResponse,
    RefreshResponse,
    RegisterCredentials,
    RegisterResponse,
} from '../types/auth';

import { apiRoutes } from './routes';

interface ApiRequestOptions<TBody> {
    method?: 'GET' | 'POST';
    token?: string;
    body?: TBody;
}

async function authRequest<TResponse, TBody = never>(url: string, options: ApiRequestOptions<TBody> = {}): Promise<TResponse | AuthErrorResponse> {
    const headers: Record<string, string> = {
        Accept: 'application/json',
    };

    if (options.token) {
        headers.Authorization = `Bearer ${options.token}`;
    }

    if (options.body !== undefined) {
        headers['Content-Type'] = 'application/json';
    }

    const response = await fetch(url, {
        method: options.method ?? 'POST',
        headers,
        body: options.body === undefined ? undefined : JSON.stringify(options.body),
    });

    return response.json() as Promise<TResponse | AuthErrorResponse>;
}

export function login(credentials: LoginCredentials): Promise<LoginResponse | AuthErrorResponse> {
    return authRequest<LoginResponse, LoginCredentials>(apiRoutes.auth.login, {
        body: credentials,
    });
}

export function register(credentials: RegisterCredentials): Promise<RegisterResponse | AuthErrorResponse> {
    return authRequest<RegisterResponse, RegisterCredentials>(apiRoutes.auth.register, {
        body: credentials,
    });
}

export function refresh(token: string): Promise<RefreshResponse | AuthErrorResponse> {
    return authRequest<RefreshResponse>(apiRoutes.auth.refresh, {
        token,
    });
}

export function me(token: string): Promise<MeResponse | AuthErrorResponse> {
    return authRequest<MeResponse>(apiRoutes.auth.me, {
        method: 'GET',
        token,
    });
}

export function logout(token: string): Promise<LogoutResponse | AuthErrorResponse> {
    return authRequest<LogoutResponse>(apiRoutes.auth.logout, {
        token,
    });
}

export function logoutAll(token: string): Promise<LogoutAllResponse | AuthErrorResponse> {
    return authRequest<LogoutAllResponse>(apiRoutes.auth.logoutAll, {
        token,
    });
}
