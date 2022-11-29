<?php

namespace App\Http\Controllers;

use App\Models\Stream;
use App\Models\StreamTag;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Facades\Socialite;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * Initiate Twitch login and redirect appropriately
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function loginInitiate(string $driver): RedirectResponse
    {
        // Only Twitch driver is implemented

        return Socialite::driver('twitch')->scopes([
            'user:read:email', 
            'user:read:follows', 
            'channel:read:subscriptions',
        ])->redirect();
    }

    /**
     * Verify login via Twitch, and login as user
     *
     * @param string $driver 
     * @return \Illuminate\Http\JsonResponse
     */
    public function loginCallback(string $driver): RedirectResponse
    {
        // Only Twitch driver is implemented

        try {
            $twitchUser = Socialite::driver($driver)->user();

            if ($user = User::where('twitch_id', $twitchUser->id)->first()) {
                // Update relevant details in user table, in case they have changed
                $user->update([
                    'username' => $twitchUser->getNickname(),
                    'name' => $twitchUser->getName(),
                    'avatar' => $twitchUser->getAvatar(),
                ]);
            } else {
                $user = new User([
                    'twitch_id' => $twitchUser->getId(),
                    'username' => $twitchUser->getNickname(),
                    'name' => $twitchUser->getName(),
                    'avatar' => $twitchUser->getAvatar(),
                    'email' => $twitchUser->getEmail(),
                    'email_verified_at' => now(),
                    'password' => '-', // Cannot login with traditional username and password, since the hash cannot resolve to '-'
                ]);

                $user->save();
            }

            $token = Auth::loginUsingId($user->id);

            if (
                ! Auth::guard('web')->loginUsingId($user->id) 
                || !($authUser = Auth::guard('web')->user()) 
                || ! ($tokenData = $authUser->createToken(config('app.name')))
            ) {
                throw new \Exception('Could not log in the user. Try again later.');
            }

            // Save token to cache, but token should expire about same time as the user token
            Cache::put('user.accessToken.' . $user->twitch_id, $twitchUser->accessTokenResponseBody['access_token'], now()->addMinutes(config('auth.passwords.users.expire'))); 

            $nonce = bin2hex(random_bytes(8));
            $token = $this->encryptToken($tokenData->plainTextToken, config('app.client_key') . $nonce, config('app.cipher'));

            return redirect(config('app.client_url') . "/auth/callback/{$driver}?token={$token}&nonce={$nonce}");
        } catch (\Exception $e) {
            return redirect(config('app.client_url') . "/auth/login/{$driver}?error=" . urlencode('There was an error completing your login. Please try again later. E: ' . $e->getMessage()));
        }
    }

    /**
     * Retrieve stats for the Twitch user
     *
     * @return \Illuminate\Http\Response
     */
    public function stats()
    {
        $user = Auth::user();
        $twitchAccessToken = Cache::get('user.accessToken.' . $user->twitch_id);

        if (! $twitchAccessToken) {
            return $this->jsonUnauthorized('Please login again.');
        }

        //Stream::getStreamsFromTwitch();
        //StreamTag::getTagsInformation();
        //StreamTag::getUnavailableTagsInformation(['39ee8140-901a-4762-bfca-8260dea1310f', '6ea6bca4-4712-4ab9-a906-e3336a9d8039', 'd4bb9c58-2141-4881-bcdc-3fe0505457d1']);
        dd('yes');

        $data = Stream::getUserStreams($twitchAccessToken);
        return $data;

        $streams = Cache::get('computedStats');
    }

    public function lost(Request $request) 
    {
        if ($request->expectsJson()) {
            return $this->jsonNotFound();
        } else {
            return redirect('/');
        }
    }
}
