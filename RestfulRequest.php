<?php

	namespace Dansnet\Webservice;

	class RestfulRequest {
		/**
		 * Request-Parameter
		 * @var array
		 */
		private $params;
		
		/** 
		 * Request-Pfad
		 * @var array
		 */
		private $path;
		
		/**
		 * @var RestfulResponse 
		 */
		private $response;
		
		
		public function __construct( $resource, array $params, array $path, RestfulResponse $response ) {
			$this->params = $params;
			$this->path = array_merge([$resource], $path);
			$this->response = $response;
		}
		
		public function getParams() { return $this->params; }
		
		/**
		 * Führt eine Weiterleitung zu einer anderen Ressource durch. 
		 * Der Parameter $paths enthält dabei das Mapping der URL Bestandteile
		 * auf die Zielressource. Ein Eintrag in diesem Array sieht z.B. folgendermaßen aus:
		 *	[ "/%id%/groups" => "/groups/1" ]
		 * Dabei ist der Schlüssel das Pattern, dem die URL übereinstimmen muss.
		 * %id% ist hier der Platzhalter für die Ressource, die die Sub-Ressource
		 * enthält. 1 ist hier die ID der Zielressource. 
		 * @param array $paths
		 * @return boolean
		 */
		public function doRedirect( array $paths ) {
			if( $this->getRelationFromPath() === FALSE ) {
				return FALSE;
			}
			// Prüfen, ob der Pfad einem Pattern entspricht
			$redirectTo = false;
			foreach( $paths as $path=>$redirection ) {
				$path = trim($path, "/");
				$realRedirect = str_replace("%id%", $this->getIdFromPath(), $path);
				$realPath = $this->getIdFromPath()."/".$this->getRelationFromPath();
				if( $realPath === $realRedirect ) {
					$redirectTo = $redirection;
					break;
				}
			}
			if( !$redirectTo ) {
				$this->response->_404_notFound();
				return NULL;
			}
			$url = $this->getRedirectUrl($redirectTo);
			if( $url === FALSE ) {
				$this->response->_404_notFound();
				return NULL;
			}
			$method = $_SERVER['REQUEST_METHOD'];
			$content = $this->{$method}($url);
			if( $content === FALSE ) {	
				$this->response->_404_notFound("", []);
				return TRUE;
			}
			$this->response->setData($content);
			return TRUE;
		}
		
		/**
		 * Prüft, ob die Anfrage erlaubt ist.
		 * 
		 * Mögliche HTTP-Status-Codes:
		 *	- 400 Bad Request
		 * 
		 * @param boolean $isValid
		 * @return boolean
		 */
		public function validate( $isValid ) {
			if( !$isValid ) {
				$this->response->_400_badRequest();
				return false;
			} 
			return true;
		}
		
		/**
		 * Ermittelt die Ressource aus der Pfadangabe:
		 * http://ws.example.com/docs/documentations/1/groups => documentations
		 * @return string
		 */
		public function getResourceFromPath() {
			return sizeof($this->path)>0 ? $this->path[0] : FALSE;
		}
		
		/**
		 * Ermittelt die ID aus der Pfadangabe:
		 * http://ws.example.com/docs/documentations/1/groups => 1
		 * @return string
		 */
		public function getIdFromPath() {
			return sizeof($this->path)>1 ? $this->path[1] : FALSE;
		}
		
		/**
		 * Ermittelt die Sub-Ressource aus der Pfadangabe:
		 * http://ws.example.com/docs/documentations/1/groups => groups
		 * @return string
		 */
		public function getRelationFromPath() {
			return sizeof($this->path)>2 ? $this->path[2] : FALSE;
		}
		
		/**
		 * Falls eine Weiterleitung erfolgt wird hier die URL zusammengebaut,
		 * an die weitergeleitet wird.
		 * @param string $redirectTo Die Sub-Ressource
		 * @return string|boolean URL oder FALSE im Fehlerfall
		 */
		private function getRedirectUrl( $redirectTo ) {
			if( $this->getRelationFromPath() === FALSE ) {
				return FALSE;
			}
			$url = "https://".$_SERVER["HTTP_HOST"];
			$pos = strpos($_SERVER["REQUEST_URI"], $this->getResourceFromPath());
			$url .= substr($_SERVER["REQUEST_URI"], 0, $pos);
			$url .= ltrim($redirectTo, "/");
			return $url;
		}		
		
		/**
		 * Führt eine GET-Weiterleitung aus.
		 * @param string $url
		 * @param array $data
		 * @return string Die Antwort der Anfrage
		 */
		private function get( $url, array $data=[] ) {
			return $this->request($url, $data, "GET");
		}
		
		/**
		 * Führt eine POST-Weiterleitung aus.
		 * @param string $url
		 * @param array $data
		 * @return string Die Antwort der Anfrage
		 */
		private function post( $url, array $data=[] ) {
			return $this->request($url, $data, "POST");
		}
		
		/**
		 * Führt eine DELETE-Weiterleitung aus.
		 * @param string $url
		 * @param array $data
		 * @return string Die Antwort der Anfrage
		 */
		private function delete( $url, array $data=[] ) {
			return $this->request($url, $data, "DELETE");
		}
		
		/**
		 * Führt eine Weiterleitung aus.
		 * @param string $url
		 * @param array $data
		 * @param string $method
		 * @return string Die Antwort der Anfrage
		 */
		private function request( $url, array $data=[], $method="GET" ) {
			$header = "Content-type: application/x-www-form-urlencoded\r\n"
					. "Accept: " . $_SERVER["HTTP_ACCEPT"] . "\r\n"
					. "Connection: close\r\n";
			$options = array(
				"http" => array(
					"header"  => $header,
					"method"  => $method,
					"content" => http_build_query($data)
				),
				"ssl"=>array(
					"verify_peer"=>false,
					"verify_peer_name"=>false,
				),
			);
			$context  = stream_context_create($options);
			$result = file_get_contents($url, false, $context);
			return $result;
		}
	}
