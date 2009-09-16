<?php

class Framework {
	public $log_messages;
	public $error_messages;
	public $warning_messages;
	public $debug_data;
	
	public $run_mode;
	public $theme;
	public $javascript_files;
	public $stylesheets;
	public $index_wrapper;
	
	private $page_title;
	
	public $quit;
	
	public $error_page;
	
	function __construct() {
		$this->log_messages = array();
		$this->error_messages = array();
		$this->warning_messages = array();
		
		$this->debug_data = array(
			'queries_used' => 0,
			'queries_list' => null,
		);
		
		$this->theme = 'default';
		$this->javascript_files = array();
		$this->stylesheets = array();
		$this->index_wrapper = 'default.tpl';
		
		$this->page_title = '';
		
		// this is used to escape running a module when global permissions fails to grant access to a user.
		$this->quit = false;
	}
	
	function Init($run_mode = 'web') {
		// set_magic_quotes_runtime(0);
		ini_set('display_errors', ObjectCache('config')->getValue('display_errors'));
		date_default_timezone_set('America/Chicago');
		
		$this->run_mode = $run_mode;
		
		//////////////////////////////////////////////////////////
		// disable IE caching, its broken like everything else	//
		//////////////////////////////////////////////////////////
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Cache-Control: no-cache, must-revalidate");
		header("Pragma: no-cache");
		
		if(ObjectCache('template') != null) {
			$_CONFIG = ObjectCache('config');
			g('template')->assign_by_ref('user_error_messages', $this->error_messages);
			g('template')->assign_by_ref('user_warning_messages', $this->warning_messages);
			g('template')->assign_by_ref('_DEBUG', $this->debug_data);
			g('template')->assign_by_ref('_FRAMEWORK', $this);
			g('template')->assign_by_ref('_CONFIG', $_CONFIG);
			
			g('template')->register_function('addJavascript', array(&$this, 'Smarty_AddJavascript'));
			g('template')->register_function('addStylesheet', array(&$this, 'Smarty_AddStylesheet'));
			g('template')->register_function('RunModule', array(&$this, 'Smarty_RunModule'));
			g('template')->register_function('RunPage', array(&$this, 'Smarty_RunPage'));
			g('template')->register_function('RunPagelet', array(&$this, 'Smarty_RunPagelet'));
			g('template')->register_function('RedirectUser', array(&$this, 'Smarty_RedirectUser'));
			g('template')->register_function('ConcatResources', array(&$this, 'Smarty_ConcatResources'));
			
			g('template')->register_function('json_encode', array(&$this, 'Smarty_JSONEncode'));
		}
	}
	
	function getRunMode() {
		return $this->run_mode;
	}
	
	function useGzipCompression($value = false) {
		ini_set('zlib.output_compression', ($value == true ? 'On' : 'Off'));
	}
	
	function setTheme($theme = 'default') {
		$this->theme = $theme;
	}
	
	function setWrapper($wrapper = 'default.tpl') {
		$this->index_wrapper = $wrapper;
	}
	
	function setContent($content = null) {
		g('template')->assign('page_contents', $content);
	}
	
	function setHeader($name = null, $value = null) {
		if($name != null && $value == null) {
			// assume that this is a call with a single string
			header($name, true);
		} else {
			header("{$name}: {$value}", true);
		}
	}
	
	function setPageTitle($string = '') {
		$this->page_title = $string;
	}
	
	function getPageTitle() {
		return $this->page_title;
	}
	
	function getRequestType() {
		return array_safe_value(request_headers(), 'X-Requested-With', false, true);
	}
	
	function currentUrl() {
		return absolutize_url($_SERVER['REQUEST_URI']);
	}
	
	function currentUrl_UrlSafe() {
		return urlencode(absolutize_url($_SERVER['REQUEST_URI']));
	}
	
	function currentUrl_Base64() {
		return urlsafe_b64encode($_SERVER['REQUEST_URI']);
	}
	
	function redirectUrl_Base64($url = '') {
		return urlsafe_b64encode(absolutize_url($url));
	}
	
	function returnUrl_Base64($url = '') {
		return urlsafe_b64decode($url);
	}
	
	function base64Encode($string = null) {
		return base64_encode($string);
	}
	
	function base64Decode($string = null) {
		return base64_decode($string);
	}
	
	function loginRequired($redirect_to = null) {
		if(g('user')->authenticated == false && g('user')->isAuthenticated() == false) {
			// get the absolute form of the redirection url
			$absolute_url = absolutize_url(g('config')->login_path, true);
			if(g('user')->RequireAuth($absolute_url) == false) {
				// g('user')->SaveSession();
				redirect_user($absolute_url);
			}
		}
	}
	
	function addJavascript($filename = null) {
		$this->javascript_files = array_merge($this->javascript_files, array($filename));
	}
	
	function addStylesheet($filename = null) {
		$this->stylesheets = array_merge($this->stylesheets, array($filename));
	}
	
	function RunModule($filename = null, $module_path = 'modules/', $use_buffer = true) {
		if($this->quit == true) {
			return '';
		}else if($filename == null || $module_path == null) {
			$this->error_page = '404';
			return "Module path or filename is null.\n";
		} else if(file_exists("{$module_path}/{$filename}") == false) {
			$this->error_page = '404';
			return "Module [{$module_path}/{$filename}] cannot be found.\n";
		}
		
		if($use_buffer == true) {
			ob_start();
			include("{$module_path}/{$filename}");
			$module_contents = ob_get_clean();
		
			return $module_contents;
		} else {
			include("{$module_path}/{$filename}");
		}
	}
	
	private function getIncludePath($resource_path = 'pages', $filename = 'default') {
		$filename = str_ireplace(".tpl", "", $filename);
		
		if(file_exists("themes/{$this->theme}/{$resource_path}/{$filename}.tpl") == true) {
			return "{$this->theme}/{$resource_path}/{$filename}.tpl";
		} else if(file_exists("themes/{$this->theme}/{$resource_path}/{$filename}/default.tpl") == true) {
			return "{$this->theme}/{$resource_path}/{$filename}/default.tpl";
		} else if(file_exists("themes/default/{$resource_path}/{$filename}.tpl") == true) {
			return "default/{$resource_path}/{$filename}.tpl";
		} else if(file_exists("themes/default/{$resource_path}/{$filename}/default.tpl") == true) {
			return "default/{$resource_path}/{$filename}/default.tpl";
		}
		
		return false;
	}
	
	function RunPage($page_path = null) {
		if($this->quit == true) {
			return '';
		}
		
		$full_path = $this->getIncludePath('pages', $page_path);
		if($full_path == false) {
			$this->error_page = '404';
			return "Page [{$page_path}] cannot be found.";
		}
		
		return ObjectCache('template')->fetch($full_path);
	}
	
	function RunPagelet($pagelet_path = null) {
		if($this->quit == true) {
			return '';
		}
		
		$full_path = $this->getIncludePath('pagelets', $pagelet_path);
		if($full_path == false) {
			return "Pagelet [{$pagelet_path}] cannot be found.";
		}
		
		return ObjectCache('template')->fetch($full_path);
	}
	
	function RunIndex() {
		$json_request = array_safe_value(request_headers(), 'X-Requested-With', false, true);
		if($json_request != false) {
			$this->setWrapper('plain.tpl');
		}
		// not a JSON request, give them the page.
		$full_path = $this->getIncludePath('wrappers', $this->index_wrapper);
		if($full_path == false) {
			return "Wrapper [{$this->index_wrapper}] cannot be found.";
		}
		
		return ObjectCache('template')->fetch($full_path);
	}
	
	function Smarty_AddJavascript($params, &$smarty) {
		extract($params);
		
		if($file == null) {
			return "File path not specified.";
		} else if(file_exists($file) == false) {
			return "File [{$file}] cannot be found.";
		}
		
		$this->addJavascript($file);
	}
	
	function Smarty_AddStylesheet($params, &$smarty) {
		extract($params);
		
		if($file == null) {
			return "File path not specified.";
		} else if(file_exists($file) == false) {
			return "File [{$file}] cannot be found.";
		}
		
		$this->addStylesheet($file);
	}
	
	function Smarty_RunModule($params, &$smarty) {
		extract($params);
		
		if($module == null) {
			if($file != null) {
				$module = $file;
			} else {
				return "Module path is null.";
			}
		} else if(file_exists("modules/{$module}") == false) {
			return "Module [{$module}] cannot be found.";
		}
		
		ob_start();
		include("modules/{$module}");
		$results = ob_get_clean();
		
		if($assign != null) {
			$smarty->assign($assign, $results);
		} else {
			return $results;
		}
	}
	
	function Smarty_RunPage($params, &$smarty) {
		if($this->quit == true) {
			return '';
		}
		
		extract($params);
		
		$full_path = $this->getIncludePath('pages', $file);
		if($full_path == false) {
			return "Page [{$file}] cannot be found.";
		}
		
		$smarty->assign($params);
		
		$results = ObjectCache('template')->fetch($full_path);
		
		if($assign != null) {
			$smarty->assign($assign, $results);
		} else {
			return $results;
		}
	}
	
	function Smarty_RunPagelet($params, &$smarty) {
		if($this->quit == true) {
			return '';
		}
		
		extract($params);
		
		$full_path = $this->getIncludePath('pagelets', $file);
		if($full_path == false) {
			return "Pagelet [{$file}] cannot be found.";
		}
		
		g('template')->assign($params);
		
		$results = ObjectCache('template')->fetch($full_path);
		
		if($assign != null) {
			$smarty->assign($assign, $results);
		} else {
			return $results;
		}
	}
	
	function Smarty_RedirectUser($params, &$smarty) {
		extract($params);
		
		redirect_user($url);
	}
	
	function Smarty_ConcatResources($params, &$smarty) {
		extract($params);
		
		if(is_string($files) && stristr($files, ",") != false) {
			$files = explode(',', $files);
		} else if(is_string($files) && stristr($files, ",") == false) {
			$files = array($files);
		}
		
		if(is_array($files) == false || count($files) == 0) {
			return false;
		}
		
		$debug_mode = (boolean)g('config')->debug_mode;
		$extension = filename_get_extension($files[0]);
		
		$output_filename = "cache/combined/". md5(implode('|', $files)) .".{$extension}";
		$output_data = '';
		
		if($debug_mode == true || file_exists($output_filename) == false || filemtime($filename) >= time() - 3600) {
			foreach($files as $filename) {
				$filename = trim($filename);
				if(file_exists($filename) == true) {
					$file_data = file_get_contents($filename);
					
					if($extension == 'css') {
						$regex = '/url\((.*?)\)/';
						preg_match_all($regex, $file_data, $matches);
						$old_paths = array();
						$new_paths = array();
						for($i = 0; $i < count($matches[0]); $i++) {
							$old_path = $matches[1][$i];
							$new_path = $this->_urlCB($old_path, realpath(dirname($filename)));
							
							if($new_path != false) {
								$old_paths[] = $matches[0][$i];
								$new_paths[] = $new_path;
							}
						}
					}
					// dump(array($old_paths, $new_paths), false);
					$file_data = str_replace($old_paths, $new_paths, $file_data);
					
					$output_data .= $file_data;
				}
			}
			// die();
			file_put_contents($output_filename, $output_data);
		}
		
		return $output_filename;
	}
	
	protected function _urlCB($path, $full_path) {
		if('/' !== $path[0]) {
			if(strpos($path, '//') > 0) {
				// possibly starts with a protocol, avoid altering it.
			} else {
				$document_root = realpath($_SERVER['DOCUMENT_ROOT']);
				$full_path = realpath($full_path);

				$new_path = $full_path .'/'. $path;
				$new_path = realpath(str_replace('//', '/', $new_path));
				$new_path = str_replace($document_root, null, $new_path);
				$new_path = str_replace('/../', '/', $new_path);

				$path = $new_path;
			}
		}

		return "url({$path})";
	}
	
	function Smarty_JSONEncode($params, &$smarty) {
		extract($params);
		
		$json = json_encode($data);
		
		if($assign != null) {
			g('template')->assign($assign, $json);
		} else {
			return $json;
		}
	}
}

?>