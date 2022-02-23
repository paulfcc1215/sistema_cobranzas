<?php
require 'config.php';
$sm=SessionManager::getInstance(_SESSION_NAME);
$sm->destroy();
header('Location: index.php');