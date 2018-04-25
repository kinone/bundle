<?php

/**
 * Description of Twig.php.
 *
 * @package Kinone\Bundle\Bridge\Yaf
 * @author Kinone\Bundle\Bridge;
 */
namespace Kinone\Bundle\Bridge\Yaf;

use Kinone\Yaf\View_Interface;
use Symfony\Component\VarDumper\VarDumper;
use Twig_Environment;
use Twig_Loader_Filesystem;
use Twig_Filter;
use Twig_Function;

class Twig implements View_Interface
{
    /**
     * @var Twig_Environment
     */
    private $twig;

    /**
     * @var array
     */
    private $context = [];
    
    public function __construct($path = [], $debug = false)
    {
        $this->twig = new Twig_Environment(new Twig_Loader_Filesystem($path));
        if ($debug) {
            $this->addFunction('dump', [VarDumper::class, 'dump']);
        }
    }

    function assign($name, $value = null)
    {
        if (is_array($name)) {
            $this->context = array_merge($this->context, $name);
        } else {
            $this->context[$name] = $value;
        }
        return true;
    }

    function display($tpl, $tpl_vars = null)
    {
        if ($tpl_vars) {
            $this->context = array_merge($this->context, $tpl_vars);
        }

        $this->twig->display($tpl, $this->context);
    }

    function getScriptPath()
    {
        return $this->twig->getLoader()->getPaths();
    }

    function render($tpl, $tpl_vars = null)
    {
        if ($tpl_vars) {
            $this->context = array_merge($this->context, $tpl_vars);
        }
        
        return $this->twig->render($tpl, $this->context);
    }

    function setScriptPath($template_dir, $namespace = Twig_Loader_Filesystem::MAIN_NAMESPACE)
    {
        $this->twig->getLoader()->setPaths($template_dir, $namespace);
    }

    public function addScriptPath($template_dir, $namespace = Twig_Loader_Filesystem::MAIN_NAMESPACE)
    {
        $this->twig->getLoader()->addPath($template_dir, $namespace);
    }

    public function addFilter($name, callable $callable)
    {
        $this->twig->addFilter(new Twig_Filter($name, $callable));
    }

    public function addFunction($name, callable $callable)
    {
        $this->twig->addFunction(new Twig_Function($name, $callable));
    }
}