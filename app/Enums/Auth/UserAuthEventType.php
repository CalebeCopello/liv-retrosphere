<?php

namespace App\Enums\Auth;

enum UserAuthEventType: int
{
    case LOGIN = 1;
    case LOGOUT = 2;
    case FAILED_LOGIN = 3;
    case PASSWORD_CHANGED = 4;
    case PASSWORD_RESET = 5;
}
