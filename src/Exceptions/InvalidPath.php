<?php

namespace Tsukaeru\RushFiles\Exceptions;

use Exception;

class InvalidPath extends Exception
{
    /**
     * @var string
     */
    private $path;

    public function __construct($path, $code = 0, Exception $previous = null)
    {
        $this->path = $path;
        parent::__construct("Invalid path: $path", $code, $previous);
    }

    public function getPath()
    {
        return $this->path;
    }
}