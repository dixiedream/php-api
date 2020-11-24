<?php

namespace exceptions;

class NotLoggedException extends \RuntimeException
{
    protected $message = 'Operator not logged in';
}
