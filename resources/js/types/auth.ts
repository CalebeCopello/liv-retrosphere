export type ApiErrors = Record<string, string[]>;

export interface AuthUser {
    id: string;
    username: string;
    display_name: string;
    email: string;
    avatar_path?: string | null;
    bio?: string | null;
    status?: number;
    email_verified_at?: string | null;
    created_at: string;
    updated_at: string;
}

export interface LoginCredentials {
    email: string;
    password: string;
}

export interface RegisterCredentials {
    username: string;
    email: string;
    password: string;
}

export interface TokenAuthData {
    access_token: string;
    token_type: 'bearer';
    expires_in: number;
    user: AuthUser;
}

export interface TokenlessAuthData {
    access_token: null;
    token_type: null;
    expires_in: null;
    user: AuthUser;
}

export interface ApiSuccessResponse<TData> {
    message: string;
    data: TData;
    errors: null;
}

export interface ApiErrorResponse {
    message: string;
    data: null;
    errors: ApiErrors | null;
}

export interface ValidationErrorResponse {
    message: string;
    errors: ApiErrors;
    data?: never;
}

export interface RateLimitErrorResponse {
    message: string;
    retry_after: number;
    data?: never;
    errors?: never;
}

export interface MessageOnlyErrorResponse {
    message: string;
    data?: never;
    errors?: never;
    retry_after?: never;
}

export type AuthErrorResponse = ApiErrorResponse | ValidationErrorResponse | RateLimitErrorResponse | MessageOnlyErrorResponse;

export type LoginResponse = ApiSuccessResponse<TokenAuthData>;

export type RegisterResponse = ApiSuccessResponse<TokenAuthData>;

export type RefreshResponse = ApiSuccessResponse<TokenAuthData>;

export type MeResponse = ApiSuccessResponse<TokenlessAuthData>;

export type LogoutResponse = ApiSuccessResponse<TokenlessAuthData>;

export type LogoutAllResponse = ApiSuccessResponse<TokenlessAuthData>;

export type AuthSuccessResponse = ApiSuccessResponse<TokenAuthData> | ApiSuccessResponse<TokenlessAuthData>;

export type AuthResponse = AuthSuccessResponse | AuthErrorResponse;
