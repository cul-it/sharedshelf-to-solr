<?php

function ssUser() {
    // sharedshelf user
    if (!empty(getenv('JSTOR_USER')) && !empty(getenv('JSTOR_PASSWORD'))) {
        $user['email'] = getenv('JSTOR_USER');
        $user['password'] = getenv('JSTOR_PASSWORD');
    } else {
        $user = parse_ini_file('ssUser.ini');
        if ($user === false) {
            throw new Exception('Need to create ssUser.ini. See README.md', 1);
        }
    }
}