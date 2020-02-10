<?php
use DI\Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';

$container = new Container();
$container->set('upload_directory', __DIR__ . '/storage');
$container->set('serverId', 1);

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->post("/upload/{requestSessionId}", function(Request $request, Response $response, array $args) {
    $sessionId = $args['requestSessionId'];
    $month = date('M');
    $day = date('d');

    $directory = sprintf("%s/%s/%s/%s", $this->get('upload_directory'), $month, $day, $sessionId);

    createPathIfNotExists($directory);

    $uploadedFiles = $request->getUploadedFiles();

    $uploadedFile = $uploadedFiles['file'];

    $jsonResponse = [
        'serverId' => $this->get('serverId')
    ];

    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
        $filename = moveUploadedFile($directory, $uploadedFile);

        $jsonResponse['uuid'] = $filename;

        $response->getBody()->write(json_encode($jsonResponse));
    }

    return $response;
});


/**
 * Moves the uploaded file to the upload directory and assigns it a unique name
 * to avoid overwriting an existing uploaded file.
 *
 * @param string $directory directory to which the file is moved
 * @param UploadedFile $uploaded file uploaded file to move
 * @return string filename of moved file
 */
function moveUploadedFile($directory, Slim\Psr7\UploadedFile $uploadedFile)
{
    $filename = generateUUID();

    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    return $filename;
}

/**
 * Returns a GUIDv4 string
 *
 * Uses the best cryptographically secure method
 * for all supported pltforms with fallback to an older,
 * less secure version.
 *
 * @param bool $trim
 * @return string
 */
function generateUUID ($trim = true)
{
    // Windows
    if (function_exists('com_create_guid') === true) {
        if ($trim === true)
            return trim(com_create_guid(), '{}');
        else
            return com_create_guid();
    }

    // OSX/Linux
    if (function_exists('openssl_random_pseudo_bytes') === true) {
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    // Fallback (PHP 4.2+)
    mt_srand((double)microtime() * 10000);
    $charid = strtolower(md5(uniqid(rand(), true)));
    $hyphen = chr(45);                  // "-"
    $lbrace = $trim ? "" : chr(123);    // "{"
    $rbrace = $trim ? "" : chr(125);    // "}"
    $guidv4 = $lbrace.
        substr($charid,  0,  8).$hyphen.
        substr($charid,  8,  4).$hyphen.
        substr($charid, 12,  4).$hyphen.
        substr($charid, 16,  4).$hyphen.
        substr($charid, 20, 12).
        $rbrace;
    return $guidv4;
}


function createPathIfNotExists($path)
{
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }
}

$app->run();