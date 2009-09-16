<?php

require_once('ObjectCache.php');
require_once('Configuration.php');
require_once('MysqlDatabase.php');
require_once('smarty/Smarty.class.php');

class TemplateEngine extends Smarty {
	public function __construct() {
		self::Smarty();
		
		$this->template_dir = ObjectCache('config')->getValue('template_dir');
		$this->compile_dir = ObjectCache('config')->getValue('template_compile_dir');
		$this->cache_dir = ObjectCache('config')->getValue('cache_path');
		$this->force_compile = ObjectCache('config')->getValue('template_force_compile');
		$this->plugins_dir[] = "./smarty_plugins/";
		
		// dump($this->plugins_dir);
		
		$this->assign_by_ref('_GET', $_GET);
		$this->assign_by_ref('_POST', $_POST);
	}
	
	public function setPath($path = null) {
		$this->template_path = $path;
	}
}

?>