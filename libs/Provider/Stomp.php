<?php
/**
 * Description of Stomp.php.
 *
 * @package Kinone\Bundle\Provider
 */

namespace Kinone\Bundle\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Stomp\Client;
use Stomp\StatefulStomp;

class Stomp implements ServiceProviderInterface
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
        $pimple['stomp'] = function() use ($pimple) {
            $uri = sprintf('tcp://%s:%d', $pimple['stomp.host'], $pimple['stomp.port']);
            $stomp = new StatefulStomp(new Client($uri));

            return $stomp;
        };

        $pimple['stomp.host'] = '127.0.0.1';
        $pimple['stomp.port'] = 61613;
    }
}
