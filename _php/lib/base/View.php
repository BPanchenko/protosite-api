<?php
namespace base;

class View extends \Smarty {
    public $Page;
    public $crumbs = array(
        'url' => "/",
        'name' => "Главная"
    );
    protected $_tpl_top = '_top.tpl';
    protected $_tpl_breadcrumbs = '_breadcrumbs.tpl';
    protected $_tpl_middle = '_middle.tpl';
    protected $_tpl_bottom = '_bottom.tpl';

    function __construct($Page) {
        parent::__construct();

        $this->Page = $Page;
        $this->assign('Page', $this->Page);

        // Custom Settings
        $this->compile_check	= true;
        $this->caching          = false;
        $this->cache_lifetime	= -1;		// 86400
        $this->debugging		= false;

        $this->template_dir	= PHP_DIR . '/templates';
        $this->config_dir	= PHP_DIR . '/templates/configs';

        $this->compile_dir	= PHP_DIR . '/templates_c';
        $this->cache_dir	= PHP_DIR . '/cache';
    }

    public function headers() {
        header("HTTP/1.0 200 OK");
        return $this;
    }

    public function render() {
        $this->headers()
             ->renderTop()
             ->renderBreadcrumbs()
             ->renderMiddle()
             ->renderBottom();
        return $this;
    }

    public function renderTop() {
        $this->display($this->_tpl_top);
        return $this;
    }

    public function renderBreadcrumbs() {
        if($this->Page->url == '/')
            return $this;

        $this->assign('crumbs', $this->crumbs);
        $this->display($this->_tpl_breadcrumbs);

        return $this;
    }

    public function renderMiddle() {
        $this->display($this->_tpl_middle);
        return $this;
    }

    public function renderBottom() {
        $this->display($this->_tpl_bottom);
        return $this;
    }
}
?>