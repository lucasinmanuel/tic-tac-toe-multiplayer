<?php

require './vendor/autoload.php';

use Config\ConfigServer;

$app = new Ratchet\App('localhost', 9990);
$app->route('/', new ConfigServer, ['*']);
$app->run();
