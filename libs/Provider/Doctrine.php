<?php
/**
 * Description of Doctrine.php.
 *
 * @package Kinone\Bundle\Provider
 * @author zhenhao<hit_zhenhao@163.com>
 */
namespace Kinone\Bundle\Provider;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Kinone\Bundle\Bridge\Doctrine\DbalLogger;

class Doctrine implements ServiceProviderInterface
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
        $pimple['db.default_options'] = [
            'driver' => 'pdo_mysql',
            'dbname' => null,
            'host' => 'localhost',
            'user' => 'root',
            'password' => null,
        ];

        $pimple['dbs.options.init'] = $pimple->protect(function() use ($pimple){
            static $init = false;

            if($init) {
                return;
            }
            $init = true;

            if (!isset($pimple['dbs.options'])) {
                $pimple['dbs.options'] = isset($pimple['dbs.options']) ? $pimple['dbs.options'] : [];
            }

            $tmp = $pimple['dbs.options'];

            foreach($tmp as $name => &$option) {
                $option = array_replace($pimple['db.default_options'], $option);

                if (!isset($pimple['dbs.default'])) {
                    $pimple['dbs.default'] = $name;
                }
            }
            
            $pimple['dbs.options'] = $tmp;
        });
        
        $pimple['dbs'] = function() use ($pimple){
            $pimple['dbs.options.init']();
            
            $dbs = new Container();
            
            foreach($pimple['dbs.options'] as $name => $option) {
                if ($pimple['dbs.default'] == $name) {
                    $config = $pimple['db.config'];
                    $manager = $pimple['db.event_manager'];
                } else {
                    $config = $pimple['dbs.config'][$name];
                    $manager = $pimple['dbs.event_manager'][$name];
                }
                
                $dbs[$name] = function() use ($option, $config, $manager) {
                    return DriverManager::getConnection($option, $config, $manager);
                };
            }
            
            return $dbs;
        };
        
        $pimple['dbs.config'] = function() use ($pimple) {
            $pimple['dbs.options.init']();
            
            $configs = new Container();
            foreach($pimple['dbs.options'] as $name => $option) {
                $configs[$name] = new Configuration();
                if (isset($pimple['dbs.logger']) && $pimple['dbs.logger'] instanceof DbalLogger) {
                    $configs[$name]->setSQLLogger($pimple['dbs.logger']);
                }
            }
            
            return $configs;
        };
        
        $pimple['dbs.event_manager'] = function() use ($pimple) {
            $pimple['dbs.options.init']();
            
            $managers = new Container();
            foreach($pimple['dbs.options'] as $name => $option) {
                $managers[$name] = new EventManager();
            }
            
            return $managers;
        };
        
        $pimple['db'] = function() use ($pimple) {
            $dbs = $pimple['dbs'];
            return $dbs[$pimple['dbs.default']];
        };
        
        $pimple['db.config'] = function() use ($pimple) {
            $configs = $pimple['dbs.config'];
            return $configs[$pimple['dbs.default']];
        };
        
        $pimple['db.event_manager'] = function() use ($pimple) {
            $managers = $pimple['dbs.event_manager'];
            return $managers[$pimple['dbs.default']];
        };
    }
}