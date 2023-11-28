<?php
use App\Models\User;


function getTokenUserId($authorization)
{
    if (!$authorization) {
        return null;
    }

    $token = explode(' ', $authorization);

    if (count($token) !== 2) {
        return null;
    }

    $authUser = User::where('api_token', $token[1])->value('id');

    return $authUser;
}



