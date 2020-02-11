<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

include __DIR__ . '/../helpers/helperFunctions.php';

$app->get('/getFile/{uuid}/{requestSessionId}', function (Request $request, Response $response, array $args) {
    $requestSessionId = $args['requestSessionId'];

    $date = DateTime::createFromFormat("YmdHis", $requestSessionId);
    $month = $date->format('M');
    $day = $date->format('d');

    $path = sprintf("%s/%s/%s/%s/%s", $this->get('upload_directory'), $month, $day, $args['requestSessionId'], $args['uuid']);

    if (file_exists($path)) {
        $htmlContent = file_get_contents($path);
        $response->getBody()->write($htmlContent);
        return $response->withHeader('Content-type', 'text/html');
    }

    $response->getBody()->write("\nFile not found");

    return $response->withStatus(404)->withHeader('Content-type', 'application/json');
});


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

    return $response->withHeader('Content-type', 'application/json');
});

