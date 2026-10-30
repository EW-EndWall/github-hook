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
    $branch = preg_replace('#^refs/heads/#', '', $data["ref"] ?? 'main');
    if (!preg_match('/^[a-zA-Z0-9._\/-]+$/', $branch)) {
        die('Invalid branch.');
    }
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
                            getRepo($repoURL, $fileName, $cron, $fileDir, $debug, $branch);
                        } else {
                            logs('Invalid secret key.' . "\n", $debug);
                            die('Invalid secret key.');
                        }
                    } else {
                        logs('check repo user/url ok' . "\n", $debug);
                        getRepo($repoURL, $fileName, $cron, $fileDir, $debug, $branch);
                    }
                } else {
                    logs('Access Err.' . "\n", $debug);
                    die('Access Err.');
                }
            } else {
                getRepo($repoURL, $fileName, $cron, $fileDir, $debug, $branch);
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
function getRepo($repoURL, $fileName, $cron, $fileDir, $debug, $branch)
{
    // * process log
    logs('getRepo func' . "\n", $debug);
    // * get repo url
    $escapedRepoURL = escapeshellarg($repoURL);
    // * process log
    logs($escapedRepoURL . "\n", $debug);
    // * check file
    $baseDir = realpath($fileDir);
    if ($baseDir === false) {
        mkdir($fileDir, 0755, true);
        $baseDir = realpath($fileDir);
    }
    $repoPath = $baseDir . '/' . $fileName;
    $escapedRepoPath = escapeshellarg($repoPath);
    $escapedBranch = escapeshellarg($branch);

    $git = 'HOME=/tmp git -c safe.directory=' . $escapedRepoPath;

    if (is_dir($repoPath . '/.git')) {
        $code = "cd {$escapedRepoPath} && "
            . "{$git} fetch origin {$escapedBranch} 2>&1 && "
            . "{$git} reset --hard FETCH_HEAD 2>&1 && "
            . "{$git} clean -fd 2>&1";
        exec($code, $output, $result);
        logs('Update (Result: ' . $result . '): ' . implode("\n", $output) . "\n", $debug);
    } else {
        $code = "HOME=/tmp git clone -b {$escapedBranch} {$escapedRepoURL} {$escapedRepoPath} 2>&1";
        exec($code, $output, $result);
        logs('Clone (Result: ' . $result . '): ' . implode("\n", $output) . "\n", $debug);
    }

    if ($result !== 0) {
        return;
    }
    // * cron
    if ($cron[0]) {
        $ch = curl_init($cron[1]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $cron[2]",
            "Content-Type: application/json",
        ]);
        $response = curl_exec($ch);
        echo curl_errno($ch) ? "Error." : $response;
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