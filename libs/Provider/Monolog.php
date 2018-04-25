<?php
/**
 * Description of Monolog.php.
 *
 * @package Kinone\Bundle\Provider
 * @author zhenhao<hit_zhenhao@163.com>
 */
namespace Kinone\Bundle\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Monolog implements ServiceProviderInterface
{
    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $pimple A container instance
     */
    public function register(Container $pimple)
    {
        $pimple['logger'] = function() use ($pimple) {
            return $pimple['monolog'];
        };
        
        $pimple['monolog'] = function() use ($pimple) {
            $logger = new Logger($pimple['monolog.name']);
            $logger->pushHandler($pimple['monolog.handler']);
            return $logger;
        };
        
        $pimple['monolog.handler'] = function() use ($pimple) {
            $level = $this->translateLevel($pimple['monolog.level']);

            return new StreamHandler($pimple['monolog.logfile'], $level, $pimple['monolog.bubble'], $pimple['monolog.permission']);
        };

        $pimple['monolog.level'] = Logger::DEBUG;
        $pimple['monolog.name'] = 'myapp';
        $pimple['monolog.bubble'] = true;
        $pimple['monolog.permission'] = null;
    }

    private function translateLevel($level)
    {
        if (is_int($level)) {
            return $level;
        }

        $levels = Logger::getLevels();
        $upper = strtoupper($level);

        if (!isset($levels[$upper])) {
            throw new \InvalidArgumentException("Provided logging level '$level' does not exist. Must be a valid monolog logging level.");
        }

        return $levels[$upper];
    }
}