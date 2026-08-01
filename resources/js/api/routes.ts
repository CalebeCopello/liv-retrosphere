const API_PREFIX = '/api';
const AUTH_PREFIX = `${API_PREFIX}/auth`;

export const apiRoutes = {
    auth: {
        register: `${AUTH_PREFIX}/register`,
        login: `${AUTH_PREFIX}/login`,
        refresh: `${AUTH_PREFIX}/refresh`,
        logout: `${AUTH_PREFIX}/logout`,
        logoutAll: `${AUTH_PREFIX}/logout-all`,
        me: `${API_PREFIX}/me`,
    },
} as const;