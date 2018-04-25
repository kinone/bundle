<?php
/**
 * Description of Session.php.
 *
 * @package Kinone\Bundle\Provider
 * @author zhenhao<hit_zhenhao@163.com>
 */
namespace Kinone\Bundle\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Session\Session as SessionDriver;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

class Session implements ServiceProviderInterface
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
        $pimple['session'] = function() use ($pimple) {
            if (!isset($pimple['session.storage'])) {
                $pimple['session.storage'] = $pimple['session.storage.native'];
            }
            
            return new SessionDriver($pimple['session.storage']);
        };
        
        $pimple['session.storage.handler'] = function() use ($pimple) {
            return new NativeFileSessionHandler($pimple['session.storage.save_path']);
        };
        
        $pimple['session.storage.native'] = function() use ($pimple) {
            return new NativeSessionStorage(
                $pimple['session.storage.options'],
                $pimple['session.storage.handler']
            );
        };
        
        $pimple['session.storage.options'] = [];
        $pimple['session.storage.save_path'] = null;
    }
}