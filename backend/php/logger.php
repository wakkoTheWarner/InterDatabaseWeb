<?php

session_start();

if (!isset($_SESSION['email'])) {
    header('Location: ../index.php');
    exit;
} else {
    // Log all actions taken by the user to single a txt file. If txt file does not exist, create it.
    // Log format: [timestamp] [email] [action]
    $log = fopen('../log/log.txt', 'a');
    fwrite($log, '[' . date('Y-m-d H:i:s') . '] ' . $_SESSION['email'] . ' ' . $_POST['action'] . PHP_EOL);
    fclose($log);
}