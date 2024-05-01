<?php
return [
    'db' => [
        'type' => 'sqlite',
        'sqlite' => [
            'path' => $_SERVER['DOCUMENT_ROOT'] . '/backend/database/interDatabase.db',
        ],
        'mysql' => [
            'host' => 'localhost',
            'dbname' => 'your_db_name',
            'username' => 'your_username',
            'password' => 'your_password',
        ],
    ],
];