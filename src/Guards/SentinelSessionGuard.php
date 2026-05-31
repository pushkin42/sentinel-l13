<?php

namespace App\Guards;

use Illuminate\Auth\SessionGuard;
use Cartalyst\Sentinel\Sentinel;

class SentinelSessionGuard extends SessionGuard
{
    protected $sentinel;
    
    public function __construct($name, Sentinel $sentinel, $provider, $session, $request)
    {
        parent::__construct($name, $provider, $session, $request);
        $this->sentinel = $sentinel;
    }
    
    public function user()
    {
        $user = parent::user();
        
        if ($user && !$this->sentinel->check()) {
            $this->sentinel->login($user);
        } elseif (!$user && $this->sentinel->check()) {
            $this->sentinel->logout();
        }
        
        return $user;
    }
    
    public function attempt(array $credentials = [], $remember = false)
    {
        if ($user = $this->sentinel->authenticate($credentials, $remember)) {
            $this->login($user, $remember);
            return true;
        }
        return false;
    }
}
