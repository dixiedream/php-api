<?php

namespace shared;

use exceptions\InvalidDataException;

class Validator
{
    /**
     * @param string $email
     * @return string
     * @throws InvaliDataException
     */
    public static function cleanEmail(string $email): string
    {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidDataException();
        }

        return $email;
    }
}
