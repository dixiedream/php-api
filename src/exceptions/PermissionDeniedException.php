<?php

namespace exceptions;

use RuntimeException;

class PermissionDeniedException extends RuntimeException
{
    protected $message = 'Permission denied';
}
