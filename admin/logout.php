<?php
require dirname(__DIR__) . '/config.php';
session_destroy();
go('login.php');
