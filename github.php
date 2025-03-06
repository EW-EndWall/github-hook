<?php
// * settings
$payload = file_get_contents('php://input'); // * Get data sent by webhook
$event = $_SERVER['HTTP_X_GITHUB_EVENT']; // * Get event type
$incomingSignature = $_SERVER['HTTP_X_HUB_SIGNATURE'];
$fileDir = "./";
$debug = false;
// * github settings
$userAccess = "user-name";
$repoCheck = ["user-name", true];
$secret = [true, "YourSecretKey"];
// * cron settings
$cron = [true, "https://example.com", "YourSecretKey"];
// * hash check
$calculatedSignature = 'sha1=' . hash_hmac('sha1', $payload, $secret[1]);
// * log started
logs('---------------------------------------------------------' . "\n", $debug);
// * Signature check Respond only to "push" events
if (isset($payload) && $event == 'push') {
    // * Run git command to get changes
    $data = json_decode($payload, true);
    $user = $data["repository"]["owner"]["name"];
    $repoURL = $data["repository"]["clone_url"];
    $fileName = $data["repository"]["name"];
    // * process log
    logs($user . "\n" . $repoURL . "\n" . $fileName . "\n", $debug);
    // * regex
    $allowedCharacters = '/^[a-zA-Z0-9-_]+$/';
    // * process log
    logs(filter_var(filter_var($repoURL, FILTER_SANITIZE_URL), FILTER_VALIDATE_URL) . "\n", $debug);
    if (preg_match($allowedCharacters, $fileName) && filter_var(filter_var($repoURL, FILTER_SANITIZE_URL), FILTER_VALIDATE_URL)) {
        logs('Url or repository name ok' . "\n", $debug);
        if ($user == $userAccess) {
            logs('userAccess ok' . "\n", $debug);
            // * check repo user
            if ($repoCheck[1] == true) {
                logs('repoCheck ok' . "\n", $debug);
                if ($repoCheck[0] == explode('/', parse_url($repoURL, PHP_URL_PATH))[1]) {
                    //* Signature Check
                    if ($secret[0]) {
                        if (hash_equals($calculatedSignature, $incomingSignature)) {
                            logs('check repo user/url ok' . "\n", $debug);
                            getRepo($repoURL, $fileName, $cron, $fileDir, $debug);
                        } else {
                            logs('Invalid secret key.' . "\n", $debug);
                            die('Invalid secret key.');
                        }
                    } else {
                        logs('check repo user/url ok' . "\n", $debug);
                        getRepo($repoURL, $fileName, $cron, $fileDir, $debug);
                    }
                } else {
                    logs('Access Err.' . "\n", $debug);
                    die('Access Err.');
                }
            } else {
                getRepo($repoURL, $fileName, $cron, $fileDir, $debug);
            }
        }
    } else {
        logs('Url or repository name err.' . "\n", $debug);
        die('Url or repository name err.');
    }
} else {
    logs('Invalid request.' . "\n", $debug);
    die('Invalid request.');
}
// * Downloading the updated repo
function getRepo($repoURL, $fileName, $cron, $fileDir, $debug)
{
    // * process log
    logs('getRepo func' . "\n", $debug);
    // * get repo url
    $escapedRepoURL = escapeshellarg(escapeshellcmd($repoURL));
    // * process log
    logs($escapedRepoURL . "\n", $debug);
    // * check file
    if (file_exists($fileName)) {
        // * Update if clone already exists
        $code = "cd " . $fileDir . $fileName . " && git pull";
        exec($code);
        logs('Update if clone already exists' . "\n", $debug);
    } else {
        // * If no clone, get new clone
        $code = "git clone " . $escapedRepoURL . " " . $fileDir . $fileName;
        exec($code);
        logs('If no clone, get new clone' . "\n", $debug);
    }
    // * cron
    if ($cron[0]) {
        $ch = curl_init($cron[1]);
        // *Header settigs
        $headers = [
            "Authorization: Bearer $cron[2]",
            "Content-Type: application/json",
        ];
        // * Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // * Make the request and get the response
        $response = curl_exec($ch);
        // * cURL is err
        if (curl_errno($ch)) {
            // echo "Error:" . curl_error($ch);
            echo "Error.";
        } else {
            // * response
            echo $response;
        }
        // * cURL session close
        curl_close($ch);
    }
}
// * log print
function logs($data, $debug)
{
    // * log save
    if ($debug == true) {
        file_put_contents('./logfile.txt', $data, FILE_APPEND);
    }
}