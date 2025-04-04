<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Jumbojett\OpenIDConnectClient;

class OktaOIDCController extends Controller
{
    public function login()
    {
        $oidc = new OpenIDConnectClient(
            env('OKTA_BASE_URL'),
            env('OKTA_CLIENT_ID'),
            env('OKTA_CLIENT_SECRET')
        );

        $oidc->setRedirectURL(env('OKTA_REDIRECT_URI'));
        $oidc->addScope(['openid', 'profile', 'email']);
        $oidc->authenticate(); // Will redirect or handle token

        $userInfo = $oidc->requestUserInfo();

        // Handle login/registration
        $user = User::firstOrCreate(
            ['email' => $userInfo->email],
            ['name' => $userInfo->name ?? $userInfo->email]
        );

        Auth::login($user);

        return redirect()->intended('/dashboard');
    }
}