<?php
// fau: videoPortal - entry script for video portal REST service

chdir('../../..');

include_once 'Services/Context/classes/class.ilContext.php';
ilContext::init(ilContext::CONTEXT_REST);

$_COOKIE['client_id'] = $_GET['client_id'] = $_REQUEST['client_id'];

include_once './include/inc.header.php';


include_once './Services/WebServices/VP/classes/class.ilVideoPortalServer.php';
$server = new ilVideoPortalServer(
    [
        'settings' => [
            'displayErrorDetails' => true
        ]
    ]
);
$server->init();
$server->run();
