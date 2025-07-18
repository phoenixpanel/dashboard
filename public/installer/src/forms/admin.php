<?php

use DevCoder\DotEnv;

(new DotEnv(dirname(__FILE__, 5) . '/.env'))->load();

if (isset($_POST['createUser'])) {
    wh_log('Getting PhoenixPanel User', 'debug');

    try {
        $db = new mysqli(getenv('DB_HOST'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'), getenv('DB_DATABASE'), getenv('DB_PORT'));
    } catch (Throwable $th) {
        wh_log($th->getMessage(), 'error');
        send_error_message("Could not connect to the Database");
        exit();
    }

    $phoenixID = $_POST['phoenixID'];
    $pass = $_POST['pass'];
    $repass = $_POST['repass'];

    try {
        $panelUrl = run_console("php artisan settings:get 'PhoenixPanelSettings' 'panel_url' --sameline");
        $adminToken = run_console("php artisan settings:get 'PhoenixPanelSettings' 'admin_token' --sameline");
    } catch (Throwable $th) {
        wh_log("Getting PhoenixPanel information failed.", 'error');
        send_error_message($th->getMessage() . " <br>Please check the installer.log file in " . dirname(__DIR__,4) . '/storage/logs' . "!");

        exit();
    }

    $panelApiUrl = $panelUrl . '/api/application/users/' . $phoenixID;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $panelApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $adminToken,
    ]);
    $response = curl_exec($ch);
    $result = json_decode($response, true);
    curl_close($ch);

    if ($pass !== $repass) {
        send_error_message("The Passwords did not match!");
        exit();
    }

    if (array_key_exists('errors', $result)) {
        send_error_message("Could not find the user with phoenixpanel ID" . $phoenixID);
        exit();
    }

    $mail = $result['attributes']['email'];
    $name = $result['attributes']['username'];
    $pass = password_hash($pass, PASSWORD_DEFAULT);

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $panelApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $adminToken,
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'email' => $mail,
        'username' => $name,
        'first_name' => $name,
        'last_name' => $name,
        'password' => $pass,
    ]);
    $response = curl_exec($ch);
    $result = json_decode($response, true);
    curl_close($ch);

    $random = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 8); // random referal

    $query1 = 'INSERT INTO `' . getenv('DB_DATABASE') . "`.`users` (`name`, `credits`, `server_limit`, `phoenixpanel_id`, `email`, `password`, `created_at`, `referral_code`) VALUES ('$name', '250', '1', '$phoenixID', '$mail', '$pass', CURRENT_TIMESTAMP, '$random')";
    $query2 = "INSERT INTO `" . getenv('DB_DATABASE') . "`.`model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES ('1', 'App\\\Models\\\User', '1')";
    try {
        $db->query($query1);
        $db->query($query2);

        wh_log('Created user with Email ' . $mail . ' and phoenixpanel ID ' . $phoenixID);
        next_step();
    } catch (Throwable $th) {
        wh_log($th->getMessage(), 'error');
        if (str_contains($th->getMessage(), 'Duplicate entry')) {
            send_error_message("User already exists in CtrlPanel\'s Database");
        } else {
            send_error_message("Something went wrong when communicating with the Database.");
        }
        exit();
    }
}

?>
