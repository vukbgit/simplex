<?php
declare(strict_types=1);

namespace Simplex;

use PHPMailer\PHPMailer\PHPMailer;

/*
* Subclass of PHPMailer (https://github.com/PHPMailer/PHPMailer) to add some functionalities
*
*/
class PHPMailerExtended extends PHPMailer
{
    /**
     * Sets SMTP
     * @param object $config with propertieshost
     *  - host
     *  - port
     *  - username
     *  - password
     *  - security: tls|ssl
     **/
    public function setSMTP($config)
    {
        $this->CharSet = PHPMailer::CHARSET_UTF8;
        $this->IsSMTP();
        $this->Host = $config->host;
        if($config->username && $config->password) {
            $this->SMTPAuth = true;
            //$this->SMTPAutoTLS = false;
            $this->Username = $config->username;
            $this->Password = $config->password;
            if($config->security) {
                $this->SMTPSecure = $config->security;
            }
            $this->Port = $config->port;
        }
    }
    
    /**
     * Sends an email
     * @param string $from
     * @param string $to
     * @param string $subject
     * @param string $body: html body, text version gets automatically extracted
     * @param string $fromName
     * @param string $toName
     * @param array $cc
     * @param array $bcc
     * @param string $replyTo: if not set $from is used
     * @param string $replyToName: if not set $fromName is used
     * @param mixed $attachments attachment object or array of attackments objects:
     *                  ->type = s(tring) | f(ile)
     *                  ->name = file-name
     *                  ->content = path-toFile | content-string
     **/
    public function sendEmail(string $from, string $to, string $subject, string $body, string $fromName = null, string $toName = null, array $cc = [], array $bcc = [], string $replyTo = null, string $replyToName = null, $attachments = null)
    {
        //$this->debugEmail();
        //sender
        $this->setFrom($from, $fromName);
        $this->addReplyTo($replyTo ?? $from, $replyToName ?? $fromName);
        //recipients
        $this->addAddress($to, $toName);
        foreach((array) $cc as $ccAddress) {
            $this->addCC($ccAddress);
        }
        foreach((array) $bcc as $bccAddress) {
            $this->addBCC($bccAddress);
        }
        //subject
        $this->Subject = $subject;
        //body
        $this->msgHTML($body, ABS_PATH_TO_ROOT);
        //attachments
        if($attachments) {
            if(!is_array($attachments)) {
                $attachments = [$attachments];
            }
            foreach ($attachments as $attachment) {
                switch($attachment->type) {
                    case 'f':
                    case 'file':
                        $this->addAttachment($attachment->content, $attachment->name);
                    break;
                    case 's':
                    case 'string':
                        $this->addStringAttachment($attachment->content, $attachment->name);
                    break;
                }
            }
        }
        //invia
        $result = $this->send();
        //clear addresses in case of bulk sending
        $this->clearAddresses();
        return $result;
    }
}
