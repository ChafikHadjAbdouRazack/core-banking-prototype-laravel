<?php

namespace App\Values;

enum UserRoles: string {
    case BUSINESS = 'business';
    case PRIVATE  = 'private';
    case ADMIN    = 'admin';
}
