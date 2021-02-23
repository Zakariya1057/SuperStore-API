<?php

namespace App\Logger;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

class RequestLogFiles
{
    /**
     * Customize the given logger instance.
     *
     * @param  \Illuminate\Log\Logger  $logger
     * @return void
     */
    public function __invoke($logger)
    {

        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter(new LineFormatter(
                "\n" . '[%datetime%] %level_name%: %message% %context% %extra%'
            ));

            if ($handler instanceof RotatingFileHandler) {
                $handler->setFilenameFormat("Requests/{date}/{filename}", 'Y-m-d');
            }
        }
    }
}

?>