<?php

namespace exceptions;

use RuntimeException;

class InvalidDataException extends RuntimeException
{
    protected $message = 'Invalid data';
}
