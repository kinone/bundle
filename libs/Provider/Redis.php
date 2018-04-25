<?php
/**
 * Description of Redis.php.
 *
 * @package Kinone\Bundle\Provider
 * @author zhenhao<hit_zhenhao@163.com>
 */
namespace Kinone\Bundle\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Predis\Client;

class Redis implements ServiceProviderInterface
{

    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $pimple An Container instance
     */
    public function register(Container $pimple)
    {
        $pimple['redis'] = function () use ($pimple) {
            $uri = sprintf('tcp://%s:%s', $pimple['redis.host'], $pimple['redis.port']);
            $options = [];
            if (isset($pimple['redis.auth'])) {
                $options['parameters']['password'] = $pimple['redis.auth'];
            }
            $redis = new Client($uri, $options);

            return $redis;
        };
    }
}