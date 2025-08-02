<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Jumbojett\OpenIDConnectClient;
use Jumbojett\OpenIDConnectClientException;

class OktaOIDCController extends Controller
{
    public function login()
    {

        try {
            $oidc = new OpenIDConnectClient(
                env('OKTA_BASE_URL','https://frontiertowersphilippines.okta.com'),
                env('OKTA_CLIENT_ID','0oaqb5gvgvAgEpK89697'),
                env('OKTA_CLIENT_SECRET','N1TFa5JzzQTDXGOqzJxPVwSw36MegDXYSkAGq_MbYE-ibOnBrV_za5kQAMxDB1Kt')
            );


            $oidc->setRedirectURL(env('OKTA_REDIRECT_URI', 'https://toolbox.frontiertowersphilippines.com/login/okta'));
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


        } catch (OpenIDConnectClientException $e) {
            Log::error('Okta Auth Error: ' . $e->getMessage());

            // Optional: Pass error to the view
            return response()->view('errors.okta', [
                'error' => $e->getMessage(),
            ], 403);
        }
    }
}
