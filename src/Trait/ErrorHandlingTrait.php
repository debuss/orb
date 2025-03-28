<?php

namespace Orb\Trait;


use Orb\Exception\ErrorException;

trait ErrorHandlingTrait
{

    /**
     * @throws ErrorException
     */
    private function handleError(int $number, string $error, string $file, int $line): void
    {
        if (!(error_reporting() & $number)) {
            return;
        }

        throw new ErrorException($error, 500, $number, $file, $line);
    }
}
