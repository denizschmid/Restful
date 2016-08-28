<?php

	namespace Dansnet\Webservice;

	abstract class RestfulResourceManager {
	
		/**
		 * @var Dansnet\DataAccessObject
		 */
		protected $_database;
		
		/**
		 * Wird hier eine Zusammenfassung der Daten erwartet? Dient nur zur Minimierung
		 * der übertragenen Daten.
		 * @var boolean
		 */
		private $_isSummary;
		
		/**
		 * Der Content-Type der Antwort.
		 * @var string
		 */
		private $_contentType;
		
		/**
		 * @var Restful
		 */
		private $_parent;
		
		public function __construct( $databaseConnection ) {
			$this->_database = $databaseConnection;
		}
		
		/**
		 * Ermittelt eine Ressource anhand seiner ID. Wird die Resource nicht 
		 * gefunden, muss die Methode Null zurückgeben. Im Fehlerfall wird 
		 * FALSE erwartet.
		 * @param string $id 
		 * @param string $table
		 * @return mixed|boolean Resource, NULL oder FALSE im Fehlerfall
		 */
		protected abstract function _getResource( $id, $table );
		
		/**
		 * Ermittelt Ressourcen anhand von Eigenschaften. Entspricht keine Ressource
		 * den Eigenschaften, so muss NULL oder ein leeres Array zurückgegeben werden.
		 * Im Fehlerfall wird FALSE erwartet.
		 * @param array $data
		 * @param string $table
		 * @return mixed|boolean array, NULL oder FALSE im Fehlerfall
		 */
		protected abstract function _getResources( array $data, $table );
		
		/**
		 * Ermittelt Ressourcen anhand einer SQL-Query. Wird keine Ressource gefunden,
		 * so muss NULL oder ein leeres Array zurückgegeben werden. Im Fehlerfall 
		 * wird FALSE erwartet.
		 * @param array $data
		 * @return mixed array, NULL oder FALSE im Fehlerfall
		 */
		protected abstract function _getResourcesByQuery( $query );
		
		/**
		 * Speichert eine Ressource. Dabei kann es eine Neuanlage oder eine
		 * Aktualisierung handeln. Ist die Anfrage fehlerhaft, so wird FALSE erwartet.
		 * Wird die Ressource für eine Aktualisierung nicht gefunden, so wird NULL
		 * erwartet. Ansonsten sollte die aktualisierte/angelegte Ressource 
		 * zurückgegeben werden
		 * @param array $data
		 * @param integer $id
		 * @param string $table
		 * @return mixed|boolean Ressource oder NULL oder FALSE im Fehlerfall 
		 */
		protected abstract function _saveResource( array $data, $id, $table );
		
		/**
		 * Löscht eine Ressource. Ist die Anfrage fehlerhaft, so wird FALSE erwartet.
		 * Wird die Ressource für eine Aktualisierung nicht gefunden, so wird NULL
		 * erwartet.
		 * @param integer $id
		 * @param string $table
		 * @return mixed|boolean Ressource oder NULL oder FALSE im Fehlerfall
		 */
		protected abstract function _deleteResource( $id, $table );

		/**
		 * Setzt die Parent-Klasse (Restful), um Zugriff auf die Request- und
		 * Response-Objekte zu erhalten.
		 * @param \Dansnet\Webservice\Restful $parent
		 */
		public function setParent( Restful $parent ) {
			$this->_parent = $parent;
			$this->_isSummary = array_key_exists("summary", $this->_parent->getRequest()->getParams());
		}
		
		/**
		 * Gibt die Datenbanktabelle der Ressource zurück.
		 * @param string $table
		 * @return type
		 */
		private function _getTable( $table=NULL ) {
			if( empty($table) ) {
				return $this->_parent->getRequest()->getResourceFromPath();
			}
			return $table;
		}
		
		private function _init() {
			$this->_contentType = $this->_parent->getResponse()->getSupportedContentType();
		}
		
		/**
		 * Ermittelt eine Ressource anhand der Anfrage.
		 * 
		 * Mögliche HTTP-Status-Codes:
		 *	- 400 Bad Request
		 *	- 404 Not Found
		 * 
		 * @param string $table
		 * @return mixed|boolean Ressource oder FALSE im Fehlerfall
		 */
		public function getResource( $table=NULL ) {
			$this->_init();
			$table = $this->_getTable($table);
			$id = $this->_parent->getRequest()->getIdFromPath();
			if( $id === FALSE ) {
				$this->getResources($table);
				return $this->_parent->getResponse()->getData();
			}
			$result = $this->_getResource($id, $table);
			if( $result === FALSE ) {
				$this->_parent->getResponse()->_400_badRequest();
				return FALSE;
			} else if( $result === NULL || $result === [] ) {
				$this->_parent->getResponse()->_404_notFound("", []);
				return [];
			} else {
                $this->_extendResource($result);
				$original = $result;
				$this->_formatResource($result, 1, $this->_isSummary, $this->_contentType);
				$this->_parent->getResponse()->setData($result);
				return $original;
			}
		}
		
		/**
		 * Ermittelt mehrere Ressourcen anhand der Anfrage.
		 * 
		 * Mögliche HTTP-Status-Codes:
		 *	- 400 Bad Request
		 * 
		 * @param string $table
		 * @return array|boolean Ressourcen oder FALSE im Fehlerfall
		 */
		protected function getResources( $table=NULL) {
			$table = $this->_getTable($table);
			$data = $this->_parent->getRequest()->getParams();
			unset($data["summary"]);
			$result = $this->_getResources($data, $table);
			if( $result === FALSE ) {
				$this->_parent->getResponse()->_400_badRequest();
				return;
			} else {
				if( $result === NULL ) {
					$result = [];
				}
                $original = [];
                foreach( $result as $res ) {
                    $this->_extendResource($res);
                    $original[] = $res;
                }
				$this->_formatResource($result, sizeof($result), $this->_isSummary, $this->_contentType);
				$this->_parent->getResponse()->setData($result);
				return $original;
			}
		}
		
		/**
		 * Ermittelt Ressourcen anhand einer SQL-Query.
		 * 
		 *  Mögliche HTTP-Status-Codes:
		 *	- 400 Bad Request
		 * 
		 * @param string $query
		 * @return array
		 */
		public function getResourcesByQuery( $query ) {
			$result = $this->_getResourcesByQuery($query);
			if( $result === FALSE ) {
				$this->_parent->getResponse()->_400_badRequest();
				return;
			} else {
				$original = [];
                foreach( $result as $res ) {
                    $this->_extendResource($res);
                    $original[] = $res;
                }
				$this->_formatResource($result, sizeof($result), $this->_isSummary, $this->_contentType);
				$this->_parent->getResponse()->setData($result);
				return $original;
			}
		}
		
		/**
		 * Speichert eine Ressource. Dabei kann es eine Neuanlage oder eine
		 * Aktualisierung handeln. 
		 * 
		 * Mögliche HTTP-Status-Codes:
		 *	- 200 OK 
		 *	- 400 Bad Request
		 *	- 404 Not Found
		 * 
		 * @param array $data
		 * @param string $table
		 * @return mixed|boolean Ressource oder FALSE im Fehlerfall
		 */
		public function saveResource( array $data, $table=NULL ) {
			$table = $this->_getTable($table);
			$id = $this->_parent->getRequest()->getIdFromPath();
			$result = $this->_saveResource($data, $id===FALSE?NULL:$id, $table);
			if( $result === FALSE ) {
				$this->_parent->getResponse()->_400_badRequest();
				return;
			} else if( $result === NULL ) {
				$this->_parent->getResponse()->_404_notFound();
				return;
			} else {
				if( $id !== FALSE ) {
					$data["id"] = $id;
				}
				$this->_extendResource($result);
				if( $data == $result ) {
					$this->_formatResource($result, 1, $this->_isSummary, $this->_contentType);
					$this->_parent->getResponse()->_200_ok("", $result);
				} else {
					$this->_formatResource($result, 1, $this->_isSummary, $this->_contentType);
					$this->_parent->getResponse()->_201_created("", $result);
				}
				$this->_parent->getResponse()->setData($result);
				return $result;
			}
		}
		
		/**
		 * Löscht eine Ressource.
		 * 
		 * Mögliche HTTP-Status-Codes:
		 *	- 204 No Content 
		 *	- 400 Bad Request
		 *	- 404 Not Found
		 * 
		 * @param string $table
		 * @return boolean TRUE oder FALSE im Fehlerfall
		 */
		public function deleteResource( $table=NULL ) {
			$table = $this->_getTable($table);
			$id = $this->_parent->getRequest()->getIdFromPath();
			if( $id === FALSE ) {
				$this->_parent->getResponse()->_404_notFound();
				return;
			}
			$result = $this->_deleteResource( $id, $table );
			if( $result === FALSE ) {
				$this->_parent->getResponse()->_400_badRequest();
				return;
			} else if( $result === NULL ) {
				$this->_parent->getResponse()->_404_notFound();
				return;
			} else {
				$this->_parent->getResponse()->_204_noContent("", $result);
				return TRUE;
			}
		}
        
        /**
         * Nachdem die Ressource ermittelt wurde, kann diese mit dieser Methode
         * mit Daten angereichert werden. 
         * @param array $resource
         * @return boolean
         */
        protected function _extendResource( &$resource ) {
            return TRUE;
        } 


        /**
		 * Formatiert die Ressource, bevor sie als Antwort geschickt wird.
		 * @param mixed $data
		 * @return mixed
		 */
		protected function _beforeSend( &$data, $resultCount, $summary=FALSE, $contentType="application/json" ) {
			if( $contentType === "application/json" || $contentType === "text/html" ) {
				$data = RestfulResponse::toJson($data);
			} else if( $contentType === "application/xml" ) {
				$data = RestfulResponse::toXml($data, $resultCount);
			}
			return $data;
		}
		
	}
