<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\FormattableHandlerInterface;

/**
 * Configura el canal "json" de logging para emitir una línea JSON por evento
 * (Logstash/New Relic compatible). Se aplica vía el array `tap` del canal.
 */
class JsonDailyTap
{
    /**
     * @param  Logger  $logger
     */
    public function __invoke($logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof FormattableHandlerInterface) {
                $handler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true));
            }
        }
    }
}
