<?php

require_once __DIR__ . '/../vendor/autoload.php';

mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
date_default_timezone_set('Europe/Rome');
setlocale(LC_ALL, 'it_IT.UTF-8');

/**
 * Db stuff
 */
require_once __DIR__ . '/config/config.php';
