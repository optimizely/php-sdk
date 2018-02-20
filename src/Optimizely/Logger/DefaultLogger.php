<?php
/**
 * Copyright 2016,2018 Optimizely
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Optimizely\Logger;

use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

/**
 * Class DefaultLogger
 *
 * @package Optimizely\Logger
 */
class DefaultLogger implements LoggerInterface
{
    /**
     * @var Logger Logger instance.
     */
    private $logger;

    /**
     * DefaultLogger constructor.
     *
     * @param int $minLevel Minimum level of messages to be logged.
     * @param string $stream The PHP stream to log output.
     */
    public function __construct($minLevel = Logger::INFO, $stream = "stdout")
    {
        $formatter = new LineFormatter("[%datetime%] %channel%.%level_name%: %message%\n");
        $streamHandler = new StreamHandler("php://{$stream}", $minLevel);
        $streamHandler->setFormatter($formatter);
        $this->logger = new Logger('Optimizely');
        $this->logger->pushHandler($streamHandler);
    }

    public function log($logLevel, $logMessage)
    {
        $this->logger->log($logLevel, $logMessage);
    }
}
