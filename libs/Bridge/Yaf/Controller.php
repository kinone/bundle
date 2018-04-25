<?php
/**
 * Description of Controller.php.
 *
 * @package Kinone\Bundle\Bridge\Yaf
 */

namespace Kinone\Bundle\Bridge\Yaf;

use Kinone\Yaf\Controller_Abstract;
use Kinone\Yaf\Dispatcher;
use Kinone\Yaf\Registry;
use Memcached;
use Monolog\Logger;
use Pimple\Container;
use Predis\Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\Session\Session;

class Controller extends Controller_Abstract
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var FileBag
     */
    private static $fileBage;

    public function init()
    {
        $this->container = Registry::get('container');
    }

    /**
     * @return Logger
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
     * @return Client
     */
    protected function redis()
    {
        return $this->container['redis'];
    }

    /**
     * @return \Stomp
     */
    protected function stomp()
    {
        return $this->container['stomp'];
    }

    /**
     * 获取Request参数.
     *
     * @param $name
     * @param null $default
     * @return mixed
     */
    protected function get($name, $default = null)
    {
        return $this->getRequest()->get($name, $default);
    }

    /**
     * 获取一个GET参数.
     *
     * @param $name
     * @param null $default
     * @return mixed
     */
    protected function getQuery($name, $default = null)
    {
        return $this->getRequest()->getQuery($name, $default);
    }

    /**
     * 获取一个Post参数.
     *
     * @param $name
     * @param null $default
     * @return mixed
     */
    protected function getPost($name, $default = null)
    {
        return $this->getRequest()->getPost($name, $default);
    }

    /**
     * 获取一个上传文件.
     *
     * @param $name
     * @param null $default
     * @return UploadedFile
     */
    protected function getFile($name, $default = null)
    {
        if (null == self::$fileBage) {
            self::$fileBage = new FileBag($_FILES);
        }

        return self::$fileBage->get($name, $default);
    }

    /**
     * 获取一个Cookie参数.
     *
     * @param $name
     * @param null $default
     * @return mixed
     */
    protected function getCookie($name, $default = null)
    {
        return $this->getRequest()->getCookie($name, $default);
    }

    /**
     * 获取一个$_SERVER参数.
     *
     * @param $name
     * @param null $default
     * @return mixed
     */
    protected function getServer($name, $default = null)
    {
        return $this->getRequest()->getServer($name, $default);
    }

    /**
     * 标准化输入json.
     *
     * @param $content
     * @param int $code
     * @param string $message
     */
    protected function outputJson($content, $code = 0, $message = '')
    {
        $this->disableView();
        $content = (object)$content;
        $content = json_encode(compact('code', 'message', 'content'));
        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setHeader('Content-Length', strlen($content));
        $this->getResponse()->setBody($content);
    }

    /**
     * 标准化输入异常.
     *
     * @param \Exception $ex
     */
    protected function outputException(\Exception $ex)
    {
        $this->outputJson([], $ex->getCode() ?: 100, $ex->getMessage());
    }

    /**
     * 禁止自动渲染页面.
     */
    protected function disableView()
    {
        Dispatcher::getInstance()->disableView();
    }
}
