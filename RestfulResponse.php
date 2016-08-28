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
		 * @var array 
		 */
		private $_errors;
		
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
		public static function toArray() {
			if( !is_array($this->_data) ) {
				return $this->_data = [$this->data];
			}
		}
		
		/**
		 * Wandelt die Daten in einen JSON-String um
		 */
		public static function toJson( $data ) {
			return json_encode($data);
		}
		
		/**
		 * Wandelt die Daten in einen XML-String um
		 */
		public static function toXml( $data ) {
			$xml = new \SimpleXMLElement("<root/>");
			if( array_keys($data) !== range(0, count($data) - 1) ) {
				array_walk_recursive(array_flip($data), array ($xml, "addChild"));
			} else {
				foreach( $data as $d ) {
					array_walk_recursive(array_flip($d), array ($xml, "addChild"));
				}
			}
			var_dump($xml->asXML());exit;
			return $xml->asXML();
		}
		
		/**
		 * Ermittelt den Antwort-Typ anhand des angeforderten Typ aus dem Header
		 * ("Accept").
		 * @param array $allowedContentTypes Erlaubte Content-Types
		 * @return string|boolean Content-Type oder FALSE fall keine Übereinstimmung
		 */
		public function getSupportedContentType( $allowedContentTypes=[] ) {
			if( isset($this->_contentType) ) {
				return $this->_contentType;
			}
			if( is_string($allowedContentTypes) ) {
				$allowedContentTypes = [$allowedContentTypes];
			}
			if( !array_key_exists("HTTP_ACCEPT", $_SERVER) ) {
				if( sizeof($allowedContentTypes) > 0 ) {
					$this->_contentType = $allowedContentTypes[0];
					return $this->_contentType;
				} else {
					$this->_415_unsupportedMediaType();
					return FALSE;
				}
			}
			$acceptedTypes = explode(",", $_SERVER["HTTP_ACCEPT"]);
			foreach( $acceptedTypes as $type ) {
				if( in_array($type, $allowedContentTypes) ) {
					$this->_contentType = $type;
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
		 * Gibt die Antwortdaten zurück. Falls der HTTP-Status-Code einem Fehlercode
		 * entspricht (>=400), wird als Inhalt die Fehlerliste gesetzt.
		 * @return mixed
		 */
		public function getData() {
			if( $this->_httpCode < 400 ) {
				return $this->_data;
			} else {
				return RestfulError::toArrays($this->_errors);
			}
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
		 * Fügt einen Fehler der Fehlerliste hinzu.
		 * @param \Dansnet\Webservice\RestfulError $error
		 */
		public function addError( RestfulError $error ) {
			$this->_errors[] = $error;
		}
		
		/**
		 * Setzt die Fehler. Ein Fehler ist vom Typ RestfulError.
		 * @param array $errors
		 * @return boolean TRUE oder FALSE im Fehlerfall
		 */
		public function setError( array $errors ) {
			if( sizeof($errors) > 0 && !$errors[0] instanceof RestfulError ) {
				return FALSE;
			}
			$this->_errors = $errors;
			return TRUE;
		}
		
		/**
		 * Gibt die Fehler zurück.
		 * @return mixed
		 */
		public function getError() {
			return empty($this->_errors) ? [] :  $this->_errors;
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
			$this->addError(new RestfulError($this->_httpCode, $this->_message, RestfulError::URL_400));
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
			$this->addError(new RestfulError($this->_httpCode, $this->_message, RestfulError::URL_401));
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
			$this->addError(new RestfulError($this->_httpCode, $this->_message, RestfulError::URL_403));
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
			$this->addError(new RestfulError($this->_httpCode, $this->_message, RestfulError::URL_404));
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
			$this->addError(new RestfulError($this->_httpCode, $this->_message, RestfulError::URL_405));
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
			$this->addError(new RestfulError($this->_httpCode, $this->_message, RestfulError::URL_410));
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
			$this->addError(new RestfulError($this->_httpCode, $this->_message, RestfulError::URL_415));
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
			$this->addError(new RestfulError($this->_httpCode, $this->_message, RestfulError::URL_422));
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
			$this->addError(new RestfulError($this->_httpCode, $this->_message, RestfulError::URL_429));
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
