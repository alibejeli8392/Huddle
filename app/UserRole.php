<?php

namespace App;

enum UserRole: string
{
    case Employee = 'employee';
    case Manager = 'manager';
}
