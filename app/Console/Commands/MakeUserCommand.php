<?php

namespace App\Console\Commands;

use App\Classes\PhoenixPanelClient;
use App\Models\User;
use App\Settings\PhoenixPanelSettings;
use App\Traits\Referral;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MakeUserCommand extends Command
{
    use Referral;

    private $phoenixpanel;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:user {--phoenix_id=} {--password=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an admin account with the Artisan Console';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(PhoenixPanelSettings $phoenix_settings)
    {
        $this->phoenixpanel = new PhoenixPanelClient($phoenix_settings);
        $phoenix_id = $this->option('phoenix_id') ?? $this->ask('Please specify your PhoenixPanel ID.');
        $password = $this->secret('password') ?? $this->ask('Please specify your password.');

        // Validate user input
        $validator = Validator::make([
            'phoenix_id' => $phoenix_id,
            'password' => $password,
        ], [
            'phoenix_id' => 'required|numeric|integer|min:1|max:2147483647',
            'password' => 'required|string|min:8|max:60',
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return 0;
        }

        //TODO: Do something with response (check for status code and give hints based upon that)
        $response = $this->phoenixpanel->getUser($phoenix_id);

        if (isset($response['errors'])) {
            if (isset($response['errors'][0]['code'])) {
                $this->error("code: {$response['errors'][0]['code']}");
            }
            if (isset($response['errors'][0]['status'])) {
                $this->error("status: {$response['errors'][0]['status']}");
            }
            if (isset($response['errors'][0]['detail'])) {
                $this->error("detail: {$response['errors'][0]['detail']}");
            }

            return 0;
        }
        $user = User::create([
            'name' => $response['first_name'],
            'email' => $response['email'],
            'password' => Hash::make($password),
            'referral_code' => $this->createReferralCode(),
            'phoenixpanel_id' => $response['id'],
        ]);

        $this->table(['Field', 'Value'], [
            ['ID', $user->id],
            ['Email', $user->email],
            ['Username', $user->name],
            ['Phoenix-ID', $user->phoenixpanel_id],
            ['Referral code', $user->referral_code],
        ]);

        $user->syncRoles(1);

        return 1;
    }
}
