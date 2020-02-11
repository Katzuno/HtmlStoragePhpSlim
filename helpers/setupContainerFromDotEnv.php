<?php

$container->set('upload_directory', __DIR__ . getenv('STORAGE_DIRECTORY'));
$container->set('serverId', getenv('SERVER_ID'));