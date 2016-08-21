<?php

	namespace Dansnet\Webservice;

	class RestfulResponse {

		/**
		 * Die eigentlichen Daten der Antwort
		 * @var mixed
		 */
		private $_data;
		
		/**
		 * Fehler
		 * @var mixed 
		 */
		private $_error;
		
		/**
		 * HTTP-Status-Code
		 * @var integer
		 */
		private $_httpCode;
		
		/**
		 * Nachricht der Antwort in Prosa
		 * @var string 
		 */
		private $_message;
		
		/**
		 * Typ der Antwort
		 * @var string 
		 */
		private $_contentType;
		
		/**
		 * Wandelt die Daten in ein Array um
		 */
		public function toArray() {
			if( !is_array($this->_data) ) {
				return $this->_data = [$this->data];
			}
		}
		
		/**
		 * Wandelt die Daten in einen JSON-String um
		 */
		public function toJson() {
			$this->_data = json_encode($this->_data);
		}
		
		/**
		 * Wandelt die Daten in einen XML-String um
		 */
		public function toXml() {
			$xml = new SimpleXMLElement("<root/>");
			array_walk_recursive($this->_data, array ($xml, "addChild"));
			$this->_data = $xml->asXML();
		}
		
		/**
		 * Ermittelt den Antwort-Typ anhand des angeforderten Typ aus dem Header
		 * ("Accept").
		 * @param array $allowedContentTypes Erlaubte Content-Types
		 * @return string|boolean Content-Type oder FALSE fall keine Übereinstimmung
		 */
		public function getSupportedContentType( $allowedContentTypes=[] ) {
			if( is_string($allowedContentTypes) ) {
				$allowedContentTypes = [$allowedContentTypes];
			}
			$acceptedTypes = explode(",", $_SERVER["HTTP_ACCEPT"]);
			foreach( $acceptedTypes as $type ) {
				if( in_array($type, $allowedContentTypes) ) {
					return $type;
				}
			}
			$this->_415_unsupportedMediaType();
			return FALSE;
		}
		
		/**
		 * Setzt die Antwortdaten und den Antwort-Content-Type
		 * @param mixed $data
		 * @param string $contentType
		 */
		public function setData( $data, $contentType="application/json" ) {
			$this->setContentType($contentType);
			$this->_data = $data;
		}
		
		/**
		 * Gibt die Antwortdaten zurück. Falls die Antwort leer ist, wird automatisch
		 * ein leeres Array erzeugt.
		 * @return mixed
		 */
		public function getData() {
			return empty($this->_data) ? [] :  $this->_data;
		}
		
		/**
		 * Setzt den Antwort-Content-Type.
		 * @param string $contentType
		 */
		public function setContentType( $contentType ) {
			$this->_contentType = $contentType;
		}
		
		/**
		 * Gibt den Antwort-Content-Type zurück.
		 * @return string
		 */
		public function getContentType() {
			return $this->_contentType;
		}
		
		/**
		 * Setzt die Fehler.
		 * @param mixed $error
		 */
		public function setError( $error ) {
			$this->_error = $error;
		}
		
		/**
		 * Gibt die Fehler zurück.
		 * @return mixed
		 */
		public function getError() {
			return empty($this->_error) ? [] :  $this->_error;
		}
		
		/**
		 * Setzt den HTTP-Status-Code der Antwort.
		 * @param integer $httpCode
		 */
		public function setHttpCode( $httpCode ) {
			$this->_httpCode = $httpCode;
		}
		
		/**
		 * Gibt den HTTP-Status-Code der Antwort zurück.
		 * @return integer
		 */
		public function getHttpCode() {
			if( empty($this->_httpCode) ) return 200;
			return $this->_httpCode;
		}
		
		/**
		 * Setzt die Nachricht der Antwort.
		 * @param string $message
		 */
		public function setMessage( $message ) {
			$this->_message = $message;
		}
		
		/**
		 * Gibt die Nachricht der Antwort zurück.
		 * @return type
		 */
		public function getMessage() {
			return empty($this->_message) ? "" :  $this->_message;
		}

		/**
		 * Setzt den HTTP-Status-Code der Antwort auf 200.
		 * @param string $message
		 * @param mixed $data
		 */
		public function _200_ok( $message="", $data=[] ) {
			$this->_httpCode = 200;
			$this->_data = $data;
			if( empty($message) ) {
				$this->_message = "OK";
			} else {
				$this->_message = $message;
			}
		}
		
		/**
		 * Setzt den HTTP-Status-Code der Antwort auf 201.
		 * @param string $message
		 * @param mixed $data
		 */
		public function _201_created( $message="", $data=[] ) {
			$this->_httpCode = 201;
			$this->_data = $data;
			if( empty($message) ) {
				$this->_message = "Created";
			} else {
				$this->_message = $message;
			}
		}
		
		/**
		 * Setzt den HTTP-Status-Code der Antwort auf 204.
		 * @param string $message
		 * @param mixed $data
		 */
		public function _204_noContent( $message="", $data=[] ) {
			$this->_httpCode = 204;
			$this->_data = $data;
			if( empty($message) ) {
				$this->_message = "No Content";
			} else {
				$this->_message = $message;
			}
		}
		
		/**
		 * Setzt den HTTP-Status-Code der Antwort auf 304.
		 * @param string $message
		 * @param mixed $data
		 */
		public function _304_notModified( $message="", $data=[] ) {
			$this->_httpCode = 304;
			$this->_data = $data;
			if( empty($message) ) {
				$this->_message = "Not Modified";
			} else {
				$this->_message = $message;
			}
		}
		
		/**
		 * Setzt den HTTP-Status-Code der Antwort auf 400.
		 * @param string $message
		 * @param mixed $data
		 */
		public function _400_badRequest( $message="", $data=[] ) {
			$this->_httpCode = 400;
			$this->_data = $data;
			if( empty($message) ) {
				$this->_message = "Bad Request";
			} else {
				$this->_message = $message;
			}
		}
		
		/**
		 * Setzt den HTTP-Status-Code der Antwort auf 401.
		 * @param string $message
		 * @param mixed $data
		 */
		public function _401_unauthorized( $message="", $data=[] ) {
			$this->_httpCode = 401;
			$this->_data = $data;
			if( empty($message) ) {
				$this->_message = "Unauthorized";
			} else {
				$this->_message = $message;
			}
		}
		
		/**
		 * Setzt den HTTP-Status-Code der Antwort auf 403.
		 * @param string $message
		 * @param mixed $data
		 */
		public function _403_forbidden( $message="", $data=[] ) {
			$this->_httpCode = 403;
			$this->_data = $data;
			if( empty($message) ) {
				$this->_message = "Forbidden";
			} else {
				$this->_message = $message;
			}
		}
		
		/**
		 * Setzt den HTTP-Status-Code der Antwort auf 404.
		 * @param string $message
		 * @param mixed $data
		 */
		public function _404_notFound( $message="", $data=[] ) {
			$this->_httpCode = 404;
			$this->_data = $data;
			if( empty($message) ) {
				$this->_message = "Not Found";
			} else {
				$this->_message = $message;
			}
		}
		
		/**
		 * Setzt den HTTP-Status-Code der Antwort auf 405.
		 * @param string $message
		 * @param mixed $data
		 */
		public function _405_methodNotAllowed( $message="", $data=[] ) {
			$this->_httpCode = 405;
			$this->_data = $data;
			if( empty($message) ) {
				$this->_message = "Method Not Allowed";
			} else {
				$this->_message = $message;
			}
		}
		
		/**
		 * Setzt den HTTP-Status-Code der Antwort auf 410.
		 * @param string $message
		 * @param mixed $data
		 */
		public function _410_gone( $message="", $data=[] ) {
			$this->_httpCode = 410;
			$this->_data = $data;
			if( empty($message) ) {
				$this->_message = "Gone";
			} else {
				$this->_message = $message;
			}
		}
		
		/**
		 * Setzt den HTTP-Status-Code der Antwort auf 415.
		 * @param string $message
		 * @param mixed $data
		 */
		public function _415_unsupportedMediaType( $message="", $data=[] ) {
			$this->_httpCode = 415;
			$this->_data = $data;
			if( empty($message) ) {
				$this->_message = "Unsupported Media Type";
			} else {
				$this->_message = $message;
			}
		}
		
		/**
		 * Setzt den HTTP-Status-Code der Antwort auf 422.
		 * @param string $message
		 * @param mixed $data
		 */
		public function _422_unprocessableEntity( $message="", $data=[] ) {
			$this->_httpCode = 422;
			$this->_data = $data;
			if( empty($message) ) {
				$this->_message = "Unprocessable Entity";
			} else {
				$this->_message = $message;
			}
		}
		
		/**
		 * Setzt den HTTP-Status-Code der Antwort auf 429.
		 * @param string $message
		 * @param mixed $data
		 */
		public function _429_tooManyRequests( $message="", $data=[] ) {
			$this->_httpCode = 429;
			$this->_data = $data;
			if( empty($message) ) {
				$this->_message = "Too Many Requests";
			} else {
				$this->_message = $message;
			}
		}
		
		/**
		 * Setzt den HTTP-Status-Code der Antwort auf 500.
		 * @param string $message
		 * @param mixed $data
		 */
		public function _500_internalServerError( $message="", $data=[] ) {
			$this->_httpCode = 500;
			$this->_data = $data;
			if( empty($message) ) {
				$this->_message = "Internal Server Error";
			} else {
				$this->_message = $message;
			}
		}
		
	}
