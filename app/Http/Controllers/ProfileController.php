<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Settings\UserSettings;
use App\Settings\PhoenixPanelSettings;
use App\Classes\PhoenixPanelClient;
use App\Settings\DiscordSettings;
use App\Settings\ReferralSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    private $phoenixpanel;

    public function __construct(PhoenixPanelSettings $phoenix_settings)
    {
        $this->phoenixpanel = new PhoenixPanelClient($phoenix_settings);
    }

    /** Display a listing of the resource. */
    public function index(UserSettings $user_settings, DiscordSettings $discord_settings, ReferralSettings $referral_settings)
    {

        return view('profile.index')->with([
            'user' => Auth::user(),
            'credits_reward_after_verify_discord' => $user_settings->credits_reward_after_verify_discord,
            'force_email_verification' => $user_settings->force_email_verification,
            'force_discord_verification' => $user_settings->force_discord_verification,
            'discord_client_id' => $discord_settings->client_id,
            'discord_client_secret' => $discord_settings->client_secret,
            'referral_enabled' => $referral_settings->enabled
        ]);
    }

    public function selfDestroyUser()
    {
        $user = Auth::user();
        if ($user->hasRole("Admin")) return back()->with("error", "You cannot delete yourself as an admin!");

        $user->delete();

        return redirect('/login')->with('success', __('Account permanently deleted!'));
    }

    /** Update the specified resource in storage.
     * @param  Request  $request
     * @param  int  $id
     * @return RedirectResponse
     */
    public function update(Request $request, int $id)
    {
        //prevent other users from editing a user
        if ($id != Auth::user()->id) {
            dd(401);
        }
        $user = User::findOrFail($id);

        //update password if necessary
        if (!is_null($request->input('new_password'))) {

            //validate password request
            $request->validate([
                'current_password' => [
                    'required',
                    function ($attribute, $value, $fail) use ($user) {
                        if (!Hash::check($value, $user->password)) {
                            $fail('The ' . $attribute . ' is invalid.');
                        }
                    },
                ],
                'new_password' => 'required|string|min:8',
                'new_password_confirmation' => 'required|same:new_password',
            ]);

            //Update Users Password on PhoenixPanel
            //Username,Mail,First and Lastname are required aswell
            $response = $this->phoenixpanel->application->patch('/application/users/' . $user->phoenixpanel_id, [
                'password' => $request->input('new_password'),
                'username' => $request->input('name'),
                'first_name' => $request->input('name'),
                'last_name' => $request->input('name'),
                'email' => $request->input('email'),

            ]);
            if ($response->failed()) {
                throw ValidationException::withMessages([
                    'phoenixpanel_error_message' => $response->toException()->getMessage(),
                    'phoenixpanel_error_status' => $response->toException()->getCode(),
                ]);
            }
            //update password
            $user->update([
                'password' => Hash::make($request->input('new_password')),
            ]);
        }

        //validate request
        $request->validate([
            'name' => 'required|min:4|max:30|alpha_num|unique:users,name,' . $id . ',id',
            'email' => 'required|email|max:64|unique:users,email,' . $id . ',id',
            'avatar' => 'nullable',
        ]);

        //update avatar
        if (!is_null($request->input('avatar'))) {
            $avatar = json_decode($request->input('avatar'));
            if ($avatar->input->size > 3000000) {
                abort(500);
            }

            $user->update([
                'avatar' => $avatar->output->image,
            ]);
        } else {
            $user->update([
                'avatar' => null,
            ]);
        }

        //update name and email on PhoenixPanel
        $response = $this->phoenixpanel->application->patch('/application/users/' . $user->phoenixpanel_id, [
            'username' => $request->input('name'),
            'first_name' => $request->input('name'),
            'last_name' => $request->input('name'),
            'email' => $request->input('email'),
        ]);

        if ($response->failed()) {
            throw ValidationException::withMessages([
                'phoenixpanel_error_message' => $response->toException()->getMessage(),
                'phoenixpanel_error_status' => $response->toException()->getCode(),
            ]);
        }

        //update name and email
        $user->update([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
        ]);

        if ($request->input('email') != Auth::user()->email) {
            $user->reVerifyEmail();
            $user->sendEmailVerificationNotification();
        }

        return redirect()->route('profile.index')->with('success', __('Profile updated'));
    }
}
