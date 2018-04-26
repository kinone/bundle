<?php
/**
 * Description of Model.php.
 *
 * @package Kinone\Bundle\Bridge\Yaf
 */
namespace Kinone\Bundle\Bridge\Yaf;
use Doctrine\DBAL\Connection;
use Kinone\Yaf\Application;
use Kinone\Yaf\Config_Ini;
use Memcached;
use Predis\Client;
use Psr\Log\LoggerInterface;
use Stomp\StatefulStomp;
use Symfony\Component\HttpFoundation\Session\Session;

abstract class AbstractModel
{
    /**
     * @var Application
     */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->init();
    }

    public function init()
    {

    }

    /**
     * @return Connection
     */
    protected function db()
    {
        return $this->app['db'];
    }

    /**
     * @return LoggerInterface
     */
    protected function logger()
    {
        return $this->app['logger'];
    }

    /**
     * @return Memcached
     */
    protected function memcached()
    {
        return $this->app['memcached'];
    }

    /**
     * @return Session
     */
    protected function session()
    {
        return $this->app['session'];
    }

    /**
     * @return Config_Ini
     */
    protected function config()
    {
        return $this->app->getConfig();
    }

    /**
     * @return StatefulStomp
     */
    protected function stomp()
    {
        return $this->app['stomp'];
    }

    /**
     * @return Client
     */
    protected function redis()
    {
        return $this->app['redis'];
    }
}
