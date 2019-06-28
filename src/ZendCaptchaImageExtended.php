<?php
declare(strict_types=1);

namespace Simplex;

use Zend\Captcha\Image;

/*
* Subclass of Zend\Captcha\Image (https://docs.zendframework.com/zend-captcha/adapters/#zend92captcha92image) to handle captcha UI and operations
*
*/
class ZendCaptchaImageExtended extends Image
{
    /**
    * Generate image and return url
    **/
    public function generateCaptchaImage()
    {
        $id = $this->captcha->generate();
        $configuration = require sprintf('%s/Frontend/config/captcha.php', PRIVATE_LOCAL_DIR);
        return (object) [
            'id' => $id,
            'imageUrl' => sprintf('%s/%s.png', $configuration['imgDir'], $id)
        ];
    }

    /**
    * Generate image and return url
    **/
    public function reloadCaptcha()
    {
        $response = $this->response->withHeader('Content-Type', 'text/json');
        $response->getBody()
            ->write(json_encode($this->generateCaptchaImage()));
    }

    /**
    * Generate image and return url
    **/
    public function execValidateCaptcha()
    {
        $fieldsDefinition = [
            'id' => FILTER_SANITIZE_STRING,
            'value' => FILTER_SANITIZE_STRING
        ];
        $input = (object) filter_input_array(INPUT_GET, $fieldsDefinition);
    }

    /**
    * valida il codice
    **/
    public function isCaptchaValid($id, $value)
    {
        $captchaData = [
            $this->captcha->getName() => [
                'id' => $id,
                'input' => $value
            ]
        ];
        return $this->captcha->isValid($captchaData);
    }
}
