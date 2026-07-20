<?php

namespace App\Enums\Auth;

enum SessionRevocationReason: int
{
    case LOGOUT = 1;
    case LOGOUT_ALL = 2;
    case PASSWORD_CHANGED = 3;
    case ADMIN_REVOKED = 4;
    case SECURITY_EVENT = 5;
    case TOKEN_ROTATED = 6;
}
