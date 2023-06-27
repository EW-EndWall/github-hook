<?php

$payload = file_get_contents('php://input'); // * Get data sent by webhook
$event = $_SERVER['HTTP_X_GITHUB_EVENT']; // * Get event type

$userAccess = "user-name";
$repoCheck = ["user-name", true];

logs('---------------------------------------------------------' . "\n");
logs($payload . "\n" . $user . "\n" . $repoURL . "\n" . $userAccess . "\n" . $repoCheck . "\n" . $event . "\n");

// * Signature check Respond only to "push" events
if (isset($payload) && $event == 'push') {

    // * Run git command to get changes
    $data = json_decode($payload, true);
    $user = $data["repository"]["owner"]["name"];
    $repoURL = $data["repository"]["clone_url"];
    $fileName = $data["repository"]["name"];

    logs($user . "\n" . $repoURL . "\n" . $fileName . "\n");

    $allowedCharacters = '/^[a-zA-Z0-9-_]+$/';

    logs(filter_var(filter_var($repoURL, FILTER_SANITIZE_URL), FILTER_VALIDATE_URL) . "\n");
    if (preg_match($allowedCharacters, $fileName) && filter_var(filter_var($repoURL, FILTER_SANITIZE_URL), FILTER_VALIDATE_URL)) {
        logs('Url or repository name ok' . "\n");
        if ($user == $userAccess) {
            logs('userAccess ok' . "\n");
            // * check repo user
            if ($repoCheck[1] == true) {
                logs('repoCheck ok' . "\n");
                if ($repoCheck[0] == explode('/', parse_url($repoURL, PHP_URL_PATH))[1]) {
                    logs('check repo user/url ok' . "\n");
                    getRepo($repoURL, $fileName);
                } else {
                    logs('Access Err.' . "\n");
                    die('Access Err.');
                }
            } else {
                getRepo($repoURL, $fileName);
            }
        }
    } else {
        logs('Url or repository name err.' . "\n");
        die('Url or repository name err.');
    }

} else {
    logs('Invalid request.' . "\n");
    die('Invalid request.');
}
// * Downloading the updated repo
function getRepo($repoURL, $fileName)
{
    logs('getRepo func' . "\n");
    $escapedRepoURL = escapeshellarg($repoURL);
    logs($escapedRepoURL . "\n");

    if (file_exists($fileName)) {
        // * Update if clone already exists
        $code = "cd " . $fileName . " && git pull";
        exec($code);
        logs('Update if clone already exists' . "\n");
    } else {
        // * If no clone, get new clone
        $code = "git clone " . $escapedRepoURL . " " . "./" . $fileName;
        exec($code);
        logs('If no clone, get new clone' . "\n");
    }
}
function logs($data)
{
    $debug = false;
    // * log save
    if ($debug == true) {
        file_put_contents('./logfile.txt', $data, FILE_APPEND);
    }
}
?>