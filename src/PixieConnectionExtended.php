<?php
declare(strict_types=1);

namespace Simplex;

use \Pixie\Connection;

/*
* Subclass of the gorgeous Pixie query builder (https://github.com/usmanhalalit/pixie) to add some functionalities
*
*/
class PixieConnectionExtended extends Connection
{
    /**
     * Create the connection adapter
     */
    public function reConnect()
    {
        $this->connect();
    }
}
