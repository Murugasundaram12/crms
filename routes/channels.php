<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('employee-tracking', function ($user) {
    return $user->can('employees-list') || $user->hasRole('Super Admin') || (int) $user->id === 1;
});
