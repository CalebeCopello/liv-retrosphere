<?php

namespace App\Enums\Auth;

enum UserAuthEventType: int
{
    case REGISTER = 1;
    case LOGIN = 2;
    case LOGOUT = 3;
    case FAILED_LOGIN = 4;
    case PASSWORD_CHANGED = 5;
    case PASSWORD_RESET = 6;
    case TOKEN_REFRESH = 7;
    case LOGOUT_ALL = 8;
}
