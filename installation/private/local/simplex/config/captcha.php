<?php
return [
    'name' => 'captcha',
    'wordLen' => 6,
    'timeout' => 300,
    'useNumbers' => true,
    //font shipped with Simplex
    'font' => sprintf('%s/fonts/Lato/Lato-Regular.ttf', PRIVATE_SHARE_SIMPLEX_DIR),
    'height' => 100,
    //will be created if does not exist
    'imgDir' => sprintf('%s/captcha', PUBLIC_LOCAL_SIMPLEX_DIR),
    'expiration' => 400
];
