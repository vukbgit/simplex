<?php
declare(strict_types=1);

namespace Simplex;

use Laminas\Captcha\Image;

/*
* Subclass of Laminas\Captcha\Image (https://docs.zendframework.com/zend-captcha/adapters/#zend92captcha92image) to handle captcha UI and operations
*
*/
class LaminasCaptchaImageExtended extends Image
{
    /**
    * Checks whether image folder config and creates it if not
    **/
    private function checkCaptchaImageFolder()
    {
        $options = $this->getOptions();
        if(!is_dir($options['imgDir'])) {
            mkdir($options['imgDir'], 0755, true);
        }
    }
    
    /**
    * Generate image and return url
    * WARNING: NEVER CALL INTO A PAGE THAT IS VALIDATING CAPTCHA, OTHERWISE CODE WILL BE REGENERATED E VALIDATION NEVER WILL BE SUCCESSFUL!!!
    **/
    public function generateCaptchaImage()
    {
        $this->checkCaptchaImageFolder();
        $options = $this->getOptions();
        $id = $this->generate();
        return (object) [
            'id' => $id,
            'imageUrl' => sprintf('/%s/%s.png', $options['imgDir'], $id)
        ];
    }

    /**
    * Generate image and return url
    **/
    public function reloadCaptcha(\Psr\Http\Message\ResponseInterface &$response)
    {
        $response = $response->withHeader('Content-Type', 'text/json');
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
            $this->getName() => [
                'id' => $id,
                'input' => $value
            ]
        ];
        return $this->isValid($captchaData);
    }
}
