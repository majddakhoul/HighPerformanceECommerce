<?php

namespace App\Logging;

use Monolog\Formatter\JsonFormatter;

class PerformanceLogFormatter
{
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            $formatter = new JsonFormatter();
            $formatter->setJsonPrettyPrint(true);
            $handler->setFormatter($formatter);
        }
    }
}
