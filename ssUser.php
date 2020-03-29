<?php

function ssUser() {
    // sharedshelf user
    $email = getenv('JSTOR_USER');
    $pass = getenv('JSTOR_PASSWORD');
    if (!empty($email) && !empty($pass)) {
        $user['email'] = getenv('JSTOR_USER');
        $user['password'] = getenv('JSTOR_PASSWORD');
    } else {
        $user = parse_ini_file('ssUser.ini');
        if ($user === false) {
            throw new Exception('Need to create ssUser.ini. See README.md', 1);
        }
    }
    return $user;
}