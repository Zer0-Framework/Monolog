<?php

namespace Zer0\Brokers;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use PHPDaemon\Core\ClassFinder;
use Zer0\Config\Interfaces\ConfigInterface;
use Zer0\Helpers\ErrorsAreExceptions;

/**
 * Class Monolog
 * @package Zer0\Brokers
 */
class Monolog extends Base
{
    /**
     * @param ConfigInterface $config
     * @return \Zer0\Drivers\PDO\PDO
     */
    public function instantiate(ConfigInterface $config): Logger
    {
        $logger = new Logger($this->lastName ?: 'default');
        foreach ($config->handlers ?? [] as $scheme) {
            if (!isset($scheme['handler'])) {
                $scheme = ['handler' => $scheme];
            }
            $handler = (array) $scheme['handler'];
            $class = ClassFinder::find($handler[0], ClassFinder::getNamespace(AbstractProcessingHandler::class), '~');
            $handlerObject = new $class(...array_slice($handler, 1));
            $logger->pushHandler($handlerObject);
            if (isset($scheme['formatter'])) {
                $formatter = (array) $scheme['formatter'];
                $class = ClassFinder::find($formatter[0], ClassFinder::getNamespace(FormatterInterface::class), '~');
                $formatterObject = new $class(...array_slice($formatter, 1));
                $handlerObject->setFormatter($formatterObject);
                
            }
        }
        foreach ($config->processors ?? [] as $processor) {
            $class = ClassFinder::find($processor[0], ClassFinder::getNamespace(AbstractProcessingHandler::class), '~');
            $logger->pushProcessor(new $class(...array_slice($formatter, 1)));
        }

        return $logger;
    }

    /**
     * @param mixed $dsn
     * @return string
     */
    protected static function getDSN($dsn): string
    {
        if (is_string($dsn)) {
            return $dsn;
        }
        $ret = '';
        foreach ($dsn as $type => $sub) {
            $ret .= $type . ':';
            foreach ($sub as $key => $value) {
                $ret .= $key . '=' . $value . ';';
            }
        }
        return $ret;
    }
}
