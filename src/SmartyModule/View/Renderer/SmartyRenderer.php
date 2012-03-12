<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Murga Nikolay work@murga.kiev.ua
 * Date: 28.02.12
 * Time: 16:52
 */

namespace SmartyModule\View\Renderer;

use Zend\Filter\FilterChain,
    Zend\View\Renderer\PhpRenderer,
    Zend\Loader\Pluggable,
    Zend\View\Model,
    Zend\View\Exception,
    ArrayAccess;

/*
use ArrayAccess,
Zend\Filter\FilterChain,
Zend\Loader\Pluggable,
Zend\View\Exception,
Zend\View\HelperBroker,
Zend\View\Renderer,
Zend\View\Resolver,
Zend\View\Variables;*/

class SmartyRenderer extends PhpRenderer
{
    /**
     * @var \Smarty $smarty
     */
    protected $smarty;
    protected $config;

    private $__file = null;
    private $__templates = array();
    private $__template = array();
    private $__content = '';

    public function init()
    {
        //$this->setSmarty(new \Smarty());
    }

    /**
     * @param \Smarty $smarty
     */
    public function setSmarty($smarty) {
        $this->smarty = $smarty;
        $this->smarty->assign('this', $this);
    }

    public function getEngine()
    {
        return $this->smarty;
    }

    public function render($nameOrModel, $values = null)
    {


        if ($nameOrModel instanceof Model) {
            $model = $nameOrModel;
            $nameOrModel = $model->getTemplate();
            if (empty($nameOrModel)) {
                throw new Exception\DomainException(sprintf(
                    '%s: received View Model argument, but template is empty',
                    __METHOD__
                ));
            }
            $options = $model->getOptions();
            foreach ($options as $setting => $value)
            {
                $method = 'set' . $setting;
                if (method_exists($this, $method)) {
                    $this->$method($value);
                }
                unset($method, $setting, $value);
            }
            unset($options);

            // Give view model awareness via ViewModel helper
            $helper = $this->plugin('view_model');
            $helper->setCurrent($model);

            $values = $model->getVariables();
            unset($model);
        }

        // find the script file name using the parent private method
        $this->addTemplate($nameOrModel);
        unset($nameOrModel); // remove $name from local scope

        if (null !== $values) {
            $this->setVars($values);
        }
        unset($values);

        // extract all assigned vars (pre-escaped), but not 'this'.
        // assigns to a double-underscored variable, to prevent naming collisions
        $__vars = $this->vars()->getArrayCopy();
        if (array_key_exists('this', $__vars)) {
            unset($__vars['this']);
        }
        $this->smarty->assign('this', $this);
        $this->smarty->assign($__vars);

        while ($this->__template = array_pop($this->__templates))
        {

            $this->__file = $this->resolver($this->__template);
            if (!$this->__file) {
                throw new Exception\RuntimeException(sprintf(
                    '%s: Unable to render template "%s"; resolver could not resolve to a file',
                    __METHOD__,
                    $this->__template
                ));
            }

            $this->__content = $this->smarty->fetch($this->__file);
        }

        return $this->getFilterChain()->filter($this->__content); // filter output
    }

    public function __clone()
    {
        $this->smarty = clone $this->smarty;
        $this->smarty->assign('this', $this);
    }

    public function addTemplate($template)
    {
        $this->__templates[] = $template;
        return $this;
    }
}