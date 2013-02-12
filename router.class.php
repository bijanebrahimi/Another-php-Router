<?php

class Router{
	public static $aliases = array();
	public static $defaults = array();
	public static $patterns = array();
	public static $variables = array();
	public static $alias_name = '';
	
	private static function is_pattern($name){
		if(preg_match("/(.*)?:(.*)/", $name, $matched))
			return array($matched[1], $matched[2]);
	}
	private static function parse_httphost($host){
		list($host, $port)=preg_split("/\:/", $host);
		if($tld=strrchr($host, '.')){
			$tld = substr($tld, 1);
			$domain = substr($host, 0, strlen($host)-strlen($tld)-1);
			if($sub=strrchr($domain, '.')){
				$subdomain = substr($domain, 0, strlen($domain)-strlen($sub));
			}
			$sld = substr($domain, strlen($subdomain)+($subdomain!=''));
			return array($subdomain, "$sld.$tld");
		}else return array(null, $host);
	}
	
	static function defaults($name, $value=null, $pattern=null){
		if(!is_null($value)){
			if($pattern){
				self::$defaults[$name] = array($value, $pattern);
			}else self::$defaults[$name] = array($value);
		}
		return self::$defaults[$name];
	}
	static function pattern($name, $regex=null){
		if($regex){
			if(!array_key_exists($name, self::$patterns))
				self::$patterns[$name] = $regex;
		}else{
			if(array_key_exists($name, self::$patterns))
				return self::$patterns[$name];
		}
	}
	static function route($name, $array, $value){
		$options = array();
		
		list($method_value, $method_pattern) = self::defaults('method');
		if(array_key_exists('method', $array)){
			if(!is_array($array['method'])) $array['method']=array($array['method']);
			foreach($array['method'] as $method)
				if(self::is_pattern($method)) $method_pattern = $method;
				else $method_value = $method;
		}
		
		list($protocol_value, $protocol_pattern) = self::defaults('protocol');
		if(array_key_exists('protocol', $array)){
			if(!is_array($array['protocol'])) $array['protocol']=array($array['protocol']);
			foreach($array['protocol'] as $protocol)
				if(self::is_pattern($protocol)) $protocol_pattern = $protocol;
				else $protocol_value = $protocol;
		}
		
		list($subdomain_value, $subdomain_pattern) = self::defaults('subdomain');
		if(array_key_exists('subdomain', $array)){
			if(!is_array($array['subdomain'])) $array['subdomain']=array($array['subdomain']);
			foreach($array['subdomain'] as $subdomain)
				if(self::is_pattern($subdomain)) $subdomain_pattern = $subdomain;
				else $subdomain_value = $subdomain;
		}
		
		list($domain_value, $domain_pattern) = self::defaults('domain');
		if(array_key_exists('domain', $array)){
			if(!is_array($array['domain'])) $array['domain']=array($array['domain']);
			foreach($array['domain'] as $domain)
				if(self::is_pattern($domain)) $domain_pattern = $domain;
				else $domain_value = $domain;
		}
		
		list($port_value, $port_pattern) = self::defaults('port');
		if(array_key_exists('port', $array)){
			if(!is_array($array['port'])) $array['port']=array($array['port']);
			foreach($array['port'] as $port)
				if(self::is_pattern($port)) $port_pattern = $port;
				else $port_value = $port;
		}
		
		list($port_value, $port_pattern) = self::defaults('port');
		if(array_key_exists('port', $array)){
			if(!is_array($array['port'])) $array['port']=array($array['port']);
			foreach($array['port'] as $port)
				if(self::is_pattern($port)) $port_pattern = $port;
				else $port_value = $port;
		}
		
		$options = array(
			'method'=>array($method_value, $method_pattern),
			'protocol'=>array($protocol_value, $protocol_pattern),
			'subdomain'=>array($subdomain_value, $subdomain_pattern),
			'domain'=>array($domain_value, $domain_pattern),
			'port'=>array($port_value, $port_pattern),
			'url'=>array(),
			'value'=>$value
		);
		$urls = explode('/', $array['url']);
		if(is_array($urls)) $options['url'] = array_filter($urls, 'strlen');
		
		if(!is_array(self::$aliases[$name]))
			self::$aliases[$name]=array();
		array_push(self::$aliases[$name], $options);
		return $options;
	}
	static function get($name){
		if(array_key_exists($name, self::$variables))
			return self::$variables[$name];
	}
	static function get_alias(){
		return self::$alias_name;
	}
	
	static function find(){
		self::$variables = array();
		self::$alias_name = array();
		list($subdomain, $domain) = self::parse_httphost($_SERVER['HTTP_HOST']);
		$request = array(
			'method'=>$_SERVER['REQUEST_METHOD'],
			'protocol'=>preg_replace('/[^a-z]/i', '', $_SERVER['SERVER_PROTOCOL']),
			'subdomain'=>$subdomain,
			'domain'=>$domain,
			'port'=>$_SERVER['SERVER_PORT'],
			'url'=>array_filter(explode("/", $_SERVER['REQUEST_URI']), 'strlen'),
		);
		foreach(self::$aliases as $alias_name=>$alias_group){
			foreach($alias_group as $index=>$alias){
				$variables = array();
				//~ Method
				list($method, $pattern) = (is_array($alias['method'])) ? $alias['method'] : array($alias['method']);
				if(!$pattern){
					$regex="/($method)/i"; 
					$var='method';
				}elseif(preg_match("/(.*)?:(.*)/i", $pattern, $matched)){
					$var = ($matched[1]) ? $matched[1] : 'method';
					$regex=self::pattern($matched[2]);
				}
				if(!preg_match($regex, $request['method'])){
					continue;
				}
				$variables[$var] = $request['method'];
				
				//~ Protocol
				list($protocol, $pattern) = (is_array($alias['protocol'])) ? $alias['protocol'] : array($alias['protocol']);
				if(!$pattern){
					$regex="/($protocol)/i"; 
					$var='protocol';
				}elseif(preg_match("/(.*)?:(.*)/i", $pattern, $matched)){
					$var = ($matched[1]) ? $matched[1] : 'protocol';
					$regex=self::pattern($matched[2]);
				}
				if(!preg_match($regex, $request['protocol'])){
					continue;
				}
				$variables[$var] = $request['protocol'];
				
				//~ subdomain
				list($subdomain, $pattern) = (is_array($alias['subdomain'])) ? $alias['subdomain'] : array($alias['subdomain']);
				if(!$pattern){
					$regex="/($subdomain)/i"; 
					$var='subdomain';
				}elseif(preg_match("/(.*)?:(.*)/i", $pattern, $matched)){
					$var = ($matched[1]) ? $matched[1] : 'subdomain';
					$regex=self::pattern($matched[2]);
				}
				if(!preg_match($regex, $request['subdomain'])){
					continue;
				}
				$variables[$var] = $request['subdomain'];
				
				//~ domain
				list($domain, $pattern) = (is_array($alias['domain'])) ? $alias['domain'] : array($alias['domain']);
				if(!$pattern){
					$regex="/($domain)/i"; 
					$var='domain';
				}elseif(preg_match("/(.*)?:(.*)/i", $pattern, $matched)){
					$var = ($matched[1]) ? $matched[1] : 'domain';
					$regex=self::pattern($matched[2]);
				}
				if(!preg_match($regex, $request['domain'])){
					continue;
				}
				$variables[$var] = $request['domain'];
				
				
				//~ port
				list($port, $pattern) = (is_array($alias['port'])) ? $alias['port'] : array($alias['port']);
				if(!$pattern){
					$regex="/($port)/i"; 
					$var='port';
				}elseif(preg_match("/(.*)?:(.*)/i", $pattern, $matched)){
					$var = ($matched[1]) ? $matched[1] : 'port';
					$regex=self::pattern($matched[2]);
				}
				if(!preg_match($regex, $request['port'])){
					continue;
				}
				$variables[$var] = $request['port'];
				
				$found = true;
				$urls = array_reverse($request['url']);
				foreach($alias['url'] as $alias_url){
					$url = array_pop($urls);
					if(preg_match("/(.*)?:(.*)/", $alias_url, $matched)){
						$pattern_name = $matched[2];
						$pattern_var = $matched[1];
						$pattern_regex=self::pattern($pattern_name);
						if(preg_match($pattern_regex, $url)){
							if($pattern_var) $variables[$pattern_var] = $url;
							else $variables[$pattern_name] = $url;
						}else{
							$found = false;
							break;
						}
						
					}else{
						if($url!=$alias_url){
							$found = false;
							break;
						}
					}
				}
				
				if($found && count($urls)==0){
					self::$variables = $variables;
					self::$alias_name = $alias_name;
					return $alias['value'];
				}
			}
		}
	}
	static function link($name, $param, $fqdn=true){
		$link = "";
		if(!array_key_exists($name, self::$aliases)){
			return null;
		}
		foreach(self::$aliases[$name] as $index=>$aliases){
			$params = $param;
			$found = true;
			$link = "";
			foreach($aliases as $key=>$alias){
				if($key!='method'){
					switch($key){
						case 'protocol':
						case 'subdomain':
						case 'domain':
						case 'port':
							$$key = (($params[$key]) ? $params[$key] : $alias[0]);
							unset($params[$key]);
							break;
						case 'url':
							$url = "";
							$params = array_reverse($params);
							foreach($alias as $value){
								if(list($var, $pattern)=self::is_pattern($value)){
									if(!$var) $var = $pattern;
									if(array_key_exists($var ,$params)){
										$arg = $params[$var];
										unset($params[$var]);
									}else{
										$arg = array_pop($params);
									}
									$regex = self::pattern($pattern);
									if(preg_match($regex, $arg)){
										$url .= "/$arg";
									}else{
										$found = false;
										break;
									}
								}else{
									$url .= "/$value";
								}
							}
							break;
					}
					if(!$found) break;
				}
			}
			if($found && count($params)==0){
				if($fqdn){ 
					$host = (($subdomain)?"$subdomain.":"").$domain;
					if($port!=80) return "$protocol://$host:$port$url";
					else return "$protocol://$host$url";
				}elseif($url) return "$url";
				else return "/$url";
				return $link;
			}
		}
	}
}


?>
