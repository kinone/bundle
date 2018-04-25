<?php
/**
 * Description of Memcached.php.
 *
 * @package Kinone\Bundle\Provider
 * @author zhenhao<hit_zhenhao@163.com>
 */
namespace Kinone\Bundle\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class Memcached implements ServiceProviderInterface
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
        $pimple['memcached'] = function() use ($pimple) {
            $mem = new \Memcached();
            $servers = isset($pimple['memcached.servers']) ? $pimple['memcached.servers'] : [];
            $mem->addServers($servers);
            return $mem;
        };
    }
}