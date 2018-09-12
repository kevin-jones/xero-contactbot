<?php

use \Html2Text\Html2Text;

require 'vendor/autoload.php';
require 'config.php';
require 'ContactBot.php';

if (empty($_GET['a'])) {
    $action = 'home';
} else {
    $action = $_GET['a'];
}

switch ($action) {
case 'create':
    $bot = new ContactBot();
    $bot->createContact($_POST, $config);
    break;
case 'select':
    $bot = new ContactBot();
    $bot->loadSelections($_POST);
    break;
case 'choose':
    $bot = new ContactBot($_GET['u']);
    break;
}

require 'views/header.php';

$view = "views/{$action}.php";
if (file_exists($view)) {
    require $view;
}

require 'views/footer.php';
