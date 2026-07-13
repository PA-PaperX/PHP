<?php
// CORS is now handled globally in public/index.php
// This file is kept to avoid breaking legacy requires, but it should not output duplicate headers.

// Set session cookie lifetime to 30 days
session_set_cookie_params([
    'lifetime' => 2592000,
    'path' => '/',
    'samesite' => 'Lax'
]);

