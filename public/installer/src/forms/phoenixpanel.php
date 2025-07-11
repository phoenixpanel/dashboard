<?php
if (isset($_POST['checkPhoenix'])) {
    wh_log('Checking PhoenixPanel Settings', 'debug');

    $url = $_POST['url'];
    $key = $_POST['key'];
    $clientkey = $_POST['clientkey'];

    $parsedUrl = parse_url($url);

    if (!isset($parsedUrl['scheme'])) {
        send_error_message("Please set an URL Scheme like 'https://'!");
        exit();
    }

    if (!isset($parsedUrl['host'])) {
        send_error_message("Please set an valid URL host like 'https://panel.example.com'!");
        exit();
    }

    $url = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

    $callphoenixURL = $url . '/api/client/account';
    $call = curl_init();

    curl_setopt($call, CURLOPT_URL, $callphoenixURL);
    curl_setopt($call, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($call, CURLOPT_HTTPHEADER, [
        'Accept: Application/vnd.phoenixpanel.v1+json',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $clientkey,
    ]);
    $callresponse = curl_exec($call);
    $callresult = json_decode($callresponse, true);
    curl_close($call);

    $phoenixURL = $url . '/api/application/users';
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $phoenixURL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: Application/vnd.phoenixpanel.v1+json',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $key,
    ]);
    $response = curl_exec($ch);
    $result = json_decode($response, true);
    curl_close($ch);

    if (!is_array($result)) {
        wh_log('No array in response found'. $result, 'error');
        send_error_message("An unknown Error occured, please try again!");
    }

    if (array_key_exists('errors', $result) && $result['errors'][0]['detail'] === 'This action is unauthorized.') {
        wh_log('API CALL ERROR: ' . $result['errors'][0]['code'], 'error');
        send_error_message("Couldn\'t connect to PhoenixPanel. Make sure your Application API key has all read and write permissions!");
        exit();
    }

    if (array_key_exists('errors', $callresult) && $callresult['errors'][0]['detail'] === 'Unauthenticated.') {
        wh_log('API CALL ERROR: ' . $callresult['errors'][0]['code'], 'error');
        send_error_message("Your ClientAPI Key is wrong or the account is not an admin!");
        exit();
    }

    try {
        run_console("php artisan settings:set 'PhoenixPanelSettings' 'panel_url' '$url'", null,null,null,false);
        run_console("php artisan settings:set 'PhoenixPanelSettings' 'admin_token' '$key'", null,null,null,false);
        run_console("php artisan settings:set 'PhoenixPanelSettings' 'user_token' '$clientkey'", null,null,null,false);
        wh_log('Database updated with phoenixpanel Settings.', 'debug');
        next_step();
    } catch (Throwable $th) {
        wh_log("Setting PhoenixPanel information failed. ".$th->getMessage(), 'error');
        send_error_message($th->getMessage() . " <br>Please check the installer.log file in " . dirname(__DIR__,4) . '/storage/logs' . "!");
        exit();
    }
}
?>
