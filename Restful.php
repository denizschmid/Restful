<?php
	namespace Dansnet\Webservice;
    require_once "RestfulUtil.php";
	require_once "RestfulRequest.php";
	require_once "RestfulResponse.php";
	require_once "RestfulResourceManager.php";
	require_once "RestfulError.php";

	/**
	 * Restful unterstützt das Lesen von HTTP-Request und die Erzeugung von korrekten
	 * Http-Responses.
	 *
	 * @author Deniz Schmid
	 */
	class Restful {
		
		/**
		 * Request-Parameter
		 * @var array
		 */
		private $params;
		
		/**
		 * @var RestfulRequest
		 */
		private $Request;
		
		/**
		 * @var RestfulResponse
		 */
		private $Response;
		
		/**
		 * @var RestfulResourceManager 
		 */
		private $Resources;
		
		public function __construct( $resource, array $params=[], array $path=[], RestfulResourceManager $resourceManager=NULL  ) {
			$this->params = $params;
			$this->httpCode = 200;
			$this->Response = new RestfulResponse();
			$this->Request = new RestfulRequest($resource, $params, $path, $this->Response);
			if( !empty($resourceManager) ) {
				$this->Resources = $resourceManager;
				$this->Resources->setParent($this);
			}
		}
		
		public function getRequest() { return $this->Request; }
		public function getResponse() { return $this->Response; }
		public function getResources() { return $this->Resources; }		
	}

