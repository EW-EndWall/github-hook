<?php

$payload = file_get_contents('php://input'); // * Get data sent by webhook
$event = $_SERVER['HTTP_X_GITHUB_EVENT']; // * Get event type

$userAccess = "name";
$repoCheck = ["name", true];

// İmza kontrolü Sadece "push" olaylarına yanıt ver
if (isset($payload) && $event == 'push') {

    // * Değişiklikleri almak için git komutunu çalıştır
    $data = json_decode($payload, true);
    $user = $data["repository"]["owner"]["name"];
    $repoURL = $data["repository"]["clone_url"];
    $fileName = $data["repository"]["name"];
    if ($user == $userAccess) {
        // * check repo user
        if ($repoCheck[1] == true) {
            if ($repoCheck[0] == explode('/', parse_url($repoURL, PHP_URL_PATH))[1]) {
                getRepo($repoURL, $fileName);
            }
        } else {
            getRepo($repoURL, $fileName);
        }

    }
    // $logData = $payload . "\n" . $user . "\n" . $repoURL . "\n" . $userAccess . "\n" . $event . "\n";
    // file_put_contents('./logfile.txt', $logData, FILE_APPEND);

} else {

    die('Invalid request.');

    // $code = "git clone --progress " . "url" . " " . "./";
    // $output = array();
    // $returnValue = null;

    // exec($code, $output, $returnValue);

    // if ($returnValue === 0) {
    //     foreach ($output as $line) {
    //         echo $line . "\n";
    //     }
    // } else {
    //     echo "Err: " . $returnValue . "\n";
    //     foreach ($output as $line) {
    //         echo $line . "\n";
    //     }
    // }
}
// Güncellenen repoyu indirmek için kullanacağımız fonksiyon
function getRepo($repoURL, $fileName)
{
    if (file_exists($fileName)) {
        // * Klon zaten varsa, güncelleme yap
        $code = "cd " . $fileName . " && git pull";
        exec($code);
    } else {
        // * Klon yoksa, yeni klon al
        $code = "git clone " . $repoURL . " " . "./" . $fileName;
        exec($code);
    }
}
?>