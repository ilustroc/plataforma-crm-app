<?php

namespace App\Application\Auth;

use Illuminate\Contracts\Auth\Factory as AuthFactory;

class LoginUser
{
    public function __construct(private readonly AuthFactory $auth)
    {
    }

    public function handle(array $credentials, bool $remember = false): bool
    {
        return $this->auth->guard()->attempt($credentials, $remember);
    }
}