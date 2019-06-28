<?php
return [
    'name' => 'captcha',
    'wordLen' => 6,
    'timeout' => 300,
    'useNumbers' => true,
    //font shipped with Simplex
    'font' => 'private/share/vukbgit/simplex/fonts/Lato/Lato-Regular.ttf',
    'height' => 100,
    //will be created if does not exist
    'imgDir' => 'public/local/simplex/captcha',
    'expiration' => 400
];
