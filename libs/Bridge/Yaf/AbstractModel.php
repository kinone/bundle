<?php
/**
 * Description of Model.php.
 *
 * @package Kinone\Bundle\Bridge\Yaf
 */
namespace Kinone\Bundle\Bridge\Yaf;
use Doctrine\DBAL\Connection;
use Kinone\Yaf\Config_Ini;
use Memcached;
use Pimple\Container;
use Psr\Log\LoggerInterface;
use Stomp\StatefulStomp;
use Symfony\Component\HttpFoundation\Session\Session;

abstract class AbstractModel
{
    /**
     * @var Container
     */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
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
        return $this->container['db'];
    }

    /**
     * @return LoggerInterface
     */
    protected function logger()
    {
        return $this->container['logger'];
    }

    /**
     * @return Memcached
     */
    protected function memcached()
    {
        return $this->container['memcached'];
    }

    /**
     * @return Session
     */
    protected function session()
    {
        return $this->container['session'];
    }

    /**
     * @return Config_Ini
     */
    protected function config()
    {
        return $this->container['yaf.config'];
    }

    /**
     * @return StatefulStomp
     */
    protected function stomp()
    {
        return $this->container['stomp'];
    }

    /**
     * @return \Predis\Client
     */
    protected function redis()
    {
        return $this->container['redis'];
    }
}
