<?php

	namespace Dansnet\Webservice;

	class RestfulError {
		
		const URL_400 = "http://docs.dansnet.de/?search=name=400";
		const URL_401 = "http://docs.dansnet.de/?search=name=401";
		const URL_403 = "http://docs.dansnet.de/?search=name=403";
		const URL_404 = "http://docs.dansnet.de/?search=name=404";
		const URL_405 = "http://docs.dansnet.de/?search=name=405";
		const URL_410 = "http://docs.dansnet.de/?search=name=410";
		const URL_415 = "http://docs.dansnet.de/?search=name=415";
		const URL_422 = "http://docs.dansnet.de/?search=name=422";
		const URL_429 = "http://docs.dansnet.de/?search=name=429";
		
		/**
		 * Fehlernachricht
		 * @var string
		 */
		private $_msg;
		
		/**
		 * Fehlercode
		 * @var integer
		 */
		private $_code;
		
		/**
		 * URL zur Fehlerbeschreibung
		 * @var string
		 */
		private $_url;
		
		public function __construct( $code, $msg="", $url="" ) {
			$this->_code = $code;
			$this->_msg = $msg;
			$this->_url = $url;
		}
		
		public function getCode() {
			return $this->_code;
		}
		
		public function getMsg() {
			return $this->_msg;
		}
		
		
		public function getUrl() {
			return $this->_url;
		}
		
		public function toArray() {
			return [
				"code"	=> $this->_code,
				"msg"	=> $this->_msg,
				"url"	=> $this->_url
			];
		}
		
		/**
		 * Konvertiert eine Liste von RestfulErrors in eine Liste von Arrays um.
		 * @param array $errors
		 * @return array|boolean
		 */
		public static function toArrays( array $errors ) {
			$array = [];
			foreach( $errors as $error ) {
				if( !$error instanceof RestfulError ) {
					return FALSE;
				}
				$array[] = $error->toArray();
			}
			return $array;
		}
		
	}
