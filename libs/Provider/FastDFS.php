<?php
/**
 * Description of FastDFS.php.
 *
 * @package Videou\Provider\Service
 * @author zhenhao<hit_zhenhao@163.com>
 */
namespace Kinone\Bundle\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Kinone\Bundle\Storage\FastDFS as Driver;

class FastDFS implements ServiceProviderInterface
{
    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     * @param Container $app
     */
    public function register(Container $app)
    {
        $app['fdfs'] = function() use ($app) {
            return new Driver([
                'tracker_index' => $app['fdfs.tracker_index'],
                'domain' => $app['fdfs.domain'],
                'group' => $app['fdfs.group'],
                'options' => $app['fdfs.options'],
            ]);
        };
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     * @param Container $app
     */
    public function boot(Container $app)
    {
        // TODO: Implement boot() method.
    }
}