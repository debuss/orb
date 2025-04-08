<?php

namespace Orb\Trait;

use Psr\Http\Message\ResponseInterface;

trait EmitterTrait
{

    private function emit(ResponseInterface $response): void
    {
        $http_line = sprintf('HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );

        header($http_line, true, $response->getStatusCode());

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }

        $stream = $response->getBody();

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        $length = 1024 * 8;
        while (!$stream->eof()) {
            echo $stream->read($length);
        }

        $this->logger->debug('Executed in {time} seconds', ['time' => microtime(true) - $this->time_start]);
    }
}
