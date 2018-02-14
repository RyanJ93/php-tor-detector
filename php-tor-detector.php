<?php
/**
* A very simple library to detect Tor connections using PHP 7 without any dependency.
*
* @package     php-tor-detector
* @author      Enrico Sola <info@enricosola.com>
* @version     v.1.1.0
*/
 
namespace PHPTorDetector{
	class PHPTorDetector{
		/**
		* @const string DEFAULT_SESSION_NAME A string containing the name of the default session's index in where results will be cached for next uses.
		*/
		const DEFAULT_SESSION_NAME = 'PHPTorDetector';
		
		/**
		* @var string $listPath A string containing the path to the file that contains a list of Tor exit points separated by a breakline (\n).
		*/
		protected static $listPath = NULL;
		
		/**
		* @var string $list A string containing the content of the list, if it is going to be cached for next uses.
		*/
		protected static $list = NULL;
		
		/**
		* @var bool $cache If set to "true", the content of the list will be cached for next uses, otherwise not.
		*/
		protected static $cache = false;
		
		/**
		* @var bool $sessionCache If set to "true" results will be cached within the session for next uses, otherwise the list will be queried everytime giving fresh results.
		*/
		protected static $sessionCache = true;
		
		/**
		* @var string $sessionName A string containing the index name where results will be cached, separate multiple names with "@" to specify multiple levels in the array ("foo@bar" = ['foo']['bar']).
		*/
		protected static $sessionName = 'PHPTorDetector';
		
		/**
		* Enables session and returns the operation result.
		*
		* @return bool If the session has correctly been started or has already been started will be returned "true", otherwise "false".
		*/
		protected static function setupSessionCache(): bool{
			$status = session_status();
			if ( $status === \PHP_SESSION_DISABLED ){
				self::$sessionCache = false;
				return false;
			}
			if ( $status !== \PHP_SESSION_ACTIVE ){
				$status = @session_start();
				if ( $status === false ){
					self::$sessionCache = false;
					return false;
				}
				return true;
			}
			return true;
		}
		
		/**
		* Returns a reference to the index where the cache shall be saved within the $_SESSION array.
		*
		* @return array A reference to the index.
		*/
		protected static function & getSession(): array{
			$index = mb_split('@', self::$sessionName);
			$ref = &$_SESSION;
			foreach ( $index as $key => $value ){
				if ( isset($ref[$value]) === false || is_array($ref[$value]) === false ){
					$ref[$value] = array();
				}
				$ref = &$ref[$value];
			}
			return $ref;
		}
		
		/**
		* Sets the path to the list file.
		*
		* @param string $path A string containing the path to the list.
		*/
		public static function setListPath(string $path = NULL){
			if ( $path === '' ){
				$path = NULL;
			}
			if ( self::$listPath !== $path ){
				self::$list = self::$cache === false ? null : '';
				self::$listPath = $path;
			}
		}
		
		/**
		* Returns the path to the list.
		*
		* @return string A string containing the path to the list.
		*/		
		public static function getListPath(): string{
			$path = self::$listPath;
			return $path === NULL ? '' : $path;
		}
		
		/**
		* Sets if the list cache shall be used or not.
		*
		* @param bool $value If set to "true", the content of the list will be cached for next uses, otherwise not.
		*/
		public static function setListCache(bool $value = false){
			if ( $value !== true ){
				self::$cache = false;
				self::$list = NULL;
				return;
			}
			self::$cache = true;
		}
		
		/**
		* Returns if the list cache is enabled or not.
		*
		* @return bool If the list cache is enabled will be returned "true", otherwise "false".
		*/
		public static function getListCache(): bool{
			return self::$cache === true ? true : false;
		}
		
		/**
		* Cleares the content of the list cache.
		*/
		public static function invalidateDictionaryCache(){
			self::$list = NULL;
		}
		
		/*
		* Loads the content of the list that has been set.
		*
		* @return bool If some data is loaded from the file will be returned "true", otherwise "false".
		*
		* @throws Exception If an error occurs while reading list contents.
		*/
		public static function loadDictionaryCache(): bool{
			if ( self::$cache === false || self::$list === NULL ){
				return false;
			}
			$data = @file_get_contents(dirname(__FILE__) . '/' . self::$listPath);
			if ( $data === false ){
				throw new \Exception('Unable to load the dictionary.');
			}
			self::$list = $data;
			return true;
		}
		
		/**
		* Sets if the results shall be cached for next uses or not.
		*
		* @param bool $value If set to "true", results will be cached for next uses, otherwise not.
		*/
		public static function setSessionCache(bool $value = true){
			if ( $value !== true ){
				if ( self::setupSessionCache() === true ){
					$session =& self::getSession();
					$session = array();
					self::$sessionCache = false;
					return;
				}
			}
			self::$sessionCache = true;
		}
		
		/**
		* Returns if the results shall be cached for next uses or not.
		*
		* @return bool If results will be cached will be returned "true", otherwise "false".
		*/
		public static function getSessionCache(): bool{
			return self::$sessionCache === false ? false : true;
		}
		
		/**
		* Sets the name of the session's index in where results will be cached for next uses.
		*
		* @param string $name A string containing the index name, separate multiple names with "@" to specify multiple levels in the array ("foo@bar" = ['foo']['bar']).
		*
		* @throws InvalidArgumentException If an invalid name is provided.
		*/
		public static function setSessionCacheName(string $name){
			if ( $name === NULL || $name === '' ){
				throw new \InvalidArgumentException('Invalid name.');
			}
			self::$sessionName = $name;
		}
		
		/**
		* Returns the name of the session's index in where results will be cached for next uses.
		*
		* @return string A string containing the index name.
		*/
		public static function getSessionCacheName(): string{
			return self::$sessionName;
		}
		
		/**
		* Cleares the content of the cache stored within PHP session.
		*/
		public static function invalidateSessionCache(){
			if ( self::setupSessionCache() === true ){
				$session =& self::getSession();
				$session = array();
			}
		}
		
		/**
		* Updates the content of the list by downloading a new list of Tor exit points, this method is asynchronous and will return a promise used to handle method success or failure.
		*
		* @throws BadMethodCallException If no file path has been set previously.
		* @throws Exception If an error occurs while writing the file.
		* @throws Exception If an error occurs while downloading the data.
		*/
		public static function updateFile(){
			$listPath = self::$listPath;
			if ( $listPath === NULL || $listPath === '' ){
				throw new \BadMethodCallException('No path has been set.');
			}
			$request = curl_init('https://check.torproject.org/exit-addresses');
			curl_setopt($request, \CURLOPT_RETURNTRANSFER, true);
			curl_setopt($request, \CURLOPT_FOLLOWLOCATION, true);
			$content = curl_exec($request);
			if ( curl_errno($request) !== 0 ){
				throw new \Exception('An error occurred while getting the data.');
			}
			$content = explode("\n", $content);
			$list = '';
			foreach ( $content as $key => $value ){
				if ( strpos($value, 'ExitAddress') === false ){
					continue;
				}
				$buffer = substr($value, strpos($value, ' ') + 1);
				if ( $buffer === '' ){
					continue;
				}
				$list .= $list === '' ? strtolower(substr($buffer, 0, strpos($buffer, ' '))) : ( "\n" . strtolower(substr($buffer, 0, strpos($buffer, ' '))) );
			}
			if ( file_put_contents(dirname(__FILE__) . '/' . $listPath, $list) === false ){
				throw new \Exception('Unable to save the file.');
			}
		}
		
		/**
		* Returns the client's IP address.
		*
		* @param bool $proxy If set to "false" will be returned the IP address found in the request, otherwise will be checked for proxy presence, if a proxy were found, will be returned the IP of the client that is using this proxy.
		*
		* @return string A string containing the client's IP address, if no valid IP address were found, will be returned an empty string.
		*/
		public static function getClientIPAddress(bool $proxy = true): string{
			if ( $proxy === true ){
				if ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) === true && is_string($_SERVER['HTTP_X_FORWARDED_FOR']) === true && $_SERVER['HTTP_X_FORWARDED_FOR'] !== '' ){
					$address = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
					if ( filter_var($address, \FILTER_VALIDATE_IP) !== false ){
						return strtolower($address);
					}
				}
				if ( isset($_SERVER['HTTP_X_FORWARDED']) === true && is_string($_SERVER['HTTP_X_FORWARDED']) === true && $_SERVER['HTTP_X_FORWARDED'] !== '' ){
					$address = explode(',', $_SERVER['HTTP_X_FORWARDED'])[0];
					if ( filter_var($address, \FILTER_VALIDATE_IP) !== false ){
						return strtolower($address);
					}
				}
				if ( isset($_SERVER['HTTP_FORWARDED_FOR']) === true && is_string($_SERVER['HTTP_FORWARDED_FOR']) === true && $_SERVER['HTTP_FORWARDED_FOR'] !== '' ){
					$address = explode(',', $_SERVER['HTTP_FORWARDED_FOR'])[0];
					if ( filter_var($address, \FILTER_VALIDATE_IP) !== false ){
						return strtolower($address);
					}
				}
				if ( isset($_SERVER['HTTP_FORWARDED']) === true && is_string($_SERVER['HTTP_FORWARDED']) === true && $_SERVER['HTTP_FORWARDED'] !== '' ){
					$address = explode(',', $_SERVER['HTTP_FORWARDED'])[0];
					if ( filter_var($address, \FILTER_VALIDATE_IP) !== false ){
						return strtolower($address);
					}
				}
			}
			if ( isset($_SERVER['HTTP_CLIENT_IP']) === true && is_string($_SERVER['HTTP_CLIENT_IP']) === true && $_SERVER['HTTP_CLIENT_IP'] !== '' ){
				$address = explode(',', $_SERVER['HTTP_CLIENT_IP'])[0];
				if ( filter_var($address, \FILTER_VALIDATE_IP) !== false ){
					return strtolower($address);
				}
			}
			if ( isset($_SERVER['REMOTE_ADDR']) === true && is_string($_SERVER['REMOTE_ADDR']) === true && $_SERVER['REMOTE_ADDR'] !== '' ){
				$address = explode(',', $_SERVER['REMOTE_ADDR'])[0];
				if ( filter_var($address, \FILTER_VALIDATE_IP) !== false ){
					return strtolower($address);
				}
			}
			return '';
		}
		
		/**
		* Checks if a given IP address is assigned to a Tor exit point or not: basically, checks if a client is using Tor or not.
		*
		* @param string $address A string containing the IP address to check.
		*
		* @return bool If the IP address is part of Tor network will be returned "true", otherwise "false".
		*
		* @throws InvalidArgumentException If the given IP address is not valid.
		* @throws BadMethodCallException If no file path has been set previously.
		* @throws Exception If an error occurs while reading the content from the file.
		* @throws Exception If the list read from the file is empty.
		*/
		public static function isTor(string $address): bool{
			if ( $address === NULL || $address === '' || filter_var($address, \FILTER_VALIDATE_IP) === false ){
				throw new \InvalidArgumentException('Invalid IP address.');
			}
			$sessionCache = self::$sessionCache;
			$address = strtolower($address);
			if ( $sessionCache !== false ){
				if ( self::setupSessionCache() === true ){
					$session =& self::getSession();
					if ( isset($session[$address]) === true ){
						return $session[$address] === true ? true : false;
					}
				}
			}
			$listPath = self::$listPath;
			$cache = self::$cache;
			if ( $listPath === NULL || $listPath === '' ){
				throw new \BadMethodCallException('No path has been set.');
			}
			if ( $cache === true && self::$list !== NULL ){
				$result = strpos(self::$list, $address . "\n") !== false || strpos(self::$list, "\n" . $address) !== false || self::$list === $address ? true : false;
				if ( $sessionCache !== false && isset($session) === true ){
					$session[$address] = $result;
				}
				return $result;
			}
			$data = @file_get_contents(dirname(__FILE__) . '/' . $listPath);
			if ( $data === false ){
				throw new \Exception('An error occurred while reading the file content.');
			}
			if ( $data === '' ){
				throw new \Exception('The given list is empty.');
			}
			if ( $cache === true ){
				self::$list = $data;
			}
			$result = strpos($data, $address . "\n") !== false || strpos($data, "\n" . $address) !== false || $data === $address ? true : false;
			if ( $sessionCache !== false && isset($session) === true ){
				$session[$address] = $result;
			}
			return $result;
		}
	}
}