<?php
declare(strict_types=1);

namespace Simplex;

use \Whoops\Handler\Handler;

class ErrorHandler extends Handler
{
    /**
     * @return int
     */
    public function handle()
    {
        $response = $this->generateResponse();

        echo $response;

        return Handler::QUIT;
    }

    /**
     * Create plain text response and return it as a string
     * @return string
     */
    public function generateResponse()
    {
        $exception = $this->getException();
        return sprintf(
            '<style type="text/css">
            body{font-family:Arial,sans-serif;}
            h1{color:#f00;}
            div{border-radius:5px;background-color:#ddd;padding:10px;}
            </style>
            <h1>Oh No! :-(</h1>
            <p>
                <a href="mailto:%1$s">%1$s</a>
            </p>
            <div>
            %2$s
            <br>(%3$s:%4$s)
            </div>' ,
            //get_class($exception),
            TECH_EMAIL,
            nl2br($exception->getMessage()),
            $exception->getFile(),
            $exception->getLine()
        );
    }
}
