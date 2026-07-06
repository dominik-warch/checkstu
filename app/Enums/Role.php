<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';   // parents — full access
    case Member = 'member'; // kids — shared pool, no admin actions
    case Guest = 'guest';   // helper — sees/completes ONLY tasks assigned to them
}
