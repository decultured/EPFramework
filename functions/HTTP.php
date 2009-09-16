<?php

/*
	set_cookie()
		- set a cookie thats safe for header("Location: ") style redirects
*/
function set_cookie($name, $value = '', $expires = 0, $path = '', $domain = '', $secure = false, $http_only = false) {
   header('Set-Cookie: ' . rawurlencode($name) . '=' . rawurlencode($value)
                         . (empty($expires) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s \\G\\M\\T', $expires))
                         . (empty($path)    ? '' : '; path=' . $path)
                         . (empty($domain)  ? '' : '; domain=' . $domain)
                         . (!$secure        ? '' : '; secure')
                         . (!$http_only    ? '' : '; HttpOnly'), false);
}

function request_headers()
{
    if(function_exists("apache_request_headers")) // If apache_request_headers() exists...
    {
        if($headers = apache_request_headers()) // And works...
        {
            return $headers; // Use it
        }
    }

    $headers = array();

    foreach(array_keys($_SERVER) as $skey)
    {
        if(substr($skey, 0, 5) == "HTTP_")
        {
            $headername = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($skey, 0, 5)))));
            $headers[$headername] = $_SERVER[$skey];
        }
    }

    return $headers;
}

define('HTTP_REQUEST', 0);
define('AJAX_REQUEST', 1);

function request_type() {
	$json_request = array_safe_value(request_headers(), 'X-Requested-With', false, true);
	if($json_request == true || defined('RAW_OUTPUT')) {
		return AJAX_REQUEST;
	}
	
	return HTTP_REQUEST;
}

/*
	redirect_user()
		- this function should be used to handle redirecting a user to a new page.
		- it will accept either absolute or relative urls.
*/
function absolutize_url($url, $add_return_path = false) {
	global $_CONFIG;
	
	$deployment_url = parse_url(ObjectCache('config')->deployment_url);
	
	$url_parts = parse_url($url);
	// make sure we've got all the needed parts.
	$url_parts['scheme'] = array_safe_value($url_parts, 'scheme', $deployment_url['scheme']);
	$url_parts['host'] = array_safe_value($url_parts, 'host', $deployment_url['host']);
	$url_parts['path'] = array_safe_value($url_parts, 'path', null);
	
	if(substr($url_parts['path'], 0, 2) == './') {
		$deployment_path = '';
		if($deployment_url['path']{0} == '/' && strlen($deployment_url['path']) > 1) {
			$deployment_path = substr($deployment_url['path'], 1);
		}
		$url_parts['path'] = $deployment_path . substr($url_parts['path'], 2);
	} else if($url_parts['path']{0} == '/') {
		// target url is an absolute path
		$url_parts['path'] = substr($url_parts['path'], 1, strlen($url_parts['path']) - 1);
	}
	
	// do we want a return path?
	if($add_return_path == true) {
		$current_url = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
		// die($current_url);
		$b64_url = urlsafe_b64encode($current_url);
		
		$url_parts['query'] .= "&return={$b64_url}";
	}
	
	// build the new url based on the parsing done above
	$new_url = "{$url_parts['scheme']}://{$url_parts['host']}/{$url_parts['path']}";
	
	if(array_safe_value($url_parts, 'query', null) != null) {
		$new_url .= "?{$url_parts['query']}";
	}
	
	// dump($new_url);
	return $new_url;
}

function redirect_user($url, $add_return_path = false) {
	// found an odd behavior with ajax if this function is allowed to process under an ajax request.
	// it is not needed in ajax, so we will just return out of here.
	if(request_type() == AJAX_REQUEST) {
		return;
	} else if(stristr($url, "://") != false) {
		header("Location: {$url}");
		exit();
	}
	
	$absolute_url = absolutize_url($url, $add_return_path);
	
	session_write_close();
	// send user to new page, and exit.
	header("Location: {$absolute_url}");
	exit();
}

function redirect_return_to_original($key_name = 'return') {
	$return_url = array_safe_value($_GET, $key_name, null);
	if($return_url == null) {
		return false;
	}
	
	$return_url = urldecode($return_url);
	
	header("Location: {$return_url}");
	exit();
}

/*
	http_require_auth()
		- this function should be used when authentication over HTTP is required.
*/
function http_require_auth() {
	header('WWW-Authenticate: Basic realm="HTTP Authentication System"');
	header('HTTP/1.0 401 Unauthorized');
	echo "You must enter a valid login ID and password to access this resource\n";
	exit;
}

function urlsafe_b64encode($string) {
	$data = base64_encode($string);
	$data = urlencode($data);
	// $data = str_replace(array('+','/','='),array('-','_',''),$data);
	return $data;
}

function urlsafe_b64decode($string) {
	$data = urldecode($string);
   // $data = str_replace(array('-','_'),array('+','/'),$string);
   $mod4 = strlen($data) % 4;
   if ($mod4) {
       $data .= substr('====', $mod4);
   }
   return base64_decode($data);
}

function url_make_absolute($absolute, $relative) {
	if($relative == null) {
		return $relative;
	}
	
	$p = parse_url($relative);
	$abs = parse_url($absolute);
	
	$path = dirname(array_safe_value($abs, 'path', ''));

	if($relative{0} == '/') {
		$cparts = array_filter(explode("/", $relative));
	} else {
		$aparts = array_filter(explode("/", $path));
		$rparts = array_filter(explode("/", $relative));
		$cparts = array_merge($aparts, $rparts);

		foreach($cparts as $i => $part) {
			if($part == '.') {
				$cparts[$i] = null;
			}

			if($part == '..') {
				$cparts[$i - 1] = null;
				$cparts[$i] = null;
			}
		}

		$cparts = array_filter($cparts);
	}
	
	$path = implode("/", $cparts);
	$url = "";
	
	if($abs['scheme']) {
		$url = "{$abs['scheme']}://";
	}
	
	if($abs['host']) {
		$url .= "{$abs['host']}/";
	}
	
	$url .= $path;
	return $url;
}

function get_remotetitle($urlpage)
{
        $dom = new DOMDocument();

        if($dom->loadHTMLFile($urlpage)) {

            $list = $dom->getElementsByTagName("title");
            if ($list->length > 0) {
                return $list->item(0)->textContent;
            }
        }
}

?>