<?php

	require_once "../Restful.php";
	//require_once "../bower_components/DataAccessObject/DataAccessObject.php";
	
	use Dansnet\Webservice\Restful;
	//use Dansnet\DataAccessObject;
	
	class RestfulTest extends PHPUnit_Framework_TestCase  {
		
		public function testRestulInit() {
			$restful = new Restful(
				"myresources", 
				[
					"key1"=>"value1",
					"key2"=>"value2",
					"key3"=>"value3",
					"key4"=>"value4",
					"key5"=>"value5"
				], 
				[],
				new ResourceManager()
			);
			$this->assertNotNull($restful);
			return $restful;
		}
		
		public function testRestulUnnamedParamsInit() {
			$restful = new Restful(
				"myresources", 
				[
					"key1"=>"value1",
					"key2"=>"value2",
					"key3"=>"value3",
					"key4"=>"value4",
					"key5"=>"value5"
				], 
				["1","subresource"],
				new ResourceManager()
			);
			$this->assertNotNull($restful);
			return $restful;
		}
		
		/**
		 * @depends testRestulInit
		 */
		public function testRequest( Restful $restful ) {
			$params = ["key1"=>"value1","key2"=>"value2","key3"=>"value3",
					"key4"=>"value4","key5"=>"value5"];
			$this->assertEquals($params, $restful->getRequest()->getParams());
			$this->assertEquals("myresources", $restful->getRequest()->getResourceFromPath());
			$this->assertEquals("", $restful->getRequest()->getIdFromPath());
			$this->assertEquals("", $restful->getRequest()->getRelationFromPath());
			$this->assertTrue($restful->getRequest()->validate(1));
			$this->assertFalse($restful->getRequest()->validate(0));
		}
		
		/**
		 * @depends testRestulUnnamedParamsInit
		 */
		public function testRequestUnnamedParams( Restful $restful ) {
			$params = ["key1"=>"value1","key2"=>"value2","key3"=>"value3",
					"key4"=>"value4","key5"=>"value5"];
			$this->assertEquals($params, $restful->getRequest()->getParams());
			$this->assertEquals("myresources", $restful->getRequest()->getResourceFromPath());
			$this->assertEquals(1, $restful->getRequest()->getIdFromPath());
			$this->assertEquals("subresource", $restful->getRequest()->getRelationFromPath());
		}
		
		public function testResources() {
			$restful1 = new Restful("myresources", [], ["1"], new ResourceManager());
			$restful2 = new Restful("myresources", [], ["2"], new ResourceManager());
			$restful3 = new Restful("myresources", ["key1"=>"value3"], [], new ResourceManager());
			$this->assertEquals(["id"=>1, "key1"=>"value1"] , $restful1->getResources()->getResource());
			$this->assertEquals([["id"=>3, "key1"=>"value3"]] , $restful3->getResources()->getResource());
		}
			
	}
	
	class ResourceManager extends \Dansnet\Webservice\RestfulResourceManager {
		
		private $resources = [
			["id"=>1, "key1"=>"value1"],
			["id"=>3, "key1"=>"value3"],
			["id"=>4, "key1"=>"value4"]
		];
		
		public function __construct() {
			parent::__construct(null);
		}
		
		protected function _getResource( $id, $table=NULL ) {
			foreach( $this->resources as $r) {
				if( $r["id"] == $id ) return $r;
			}
			return null;
		}
		
		protected function _getResources( array $data, $table=NULL ) {
			//$this->_database->setTable($table);
			if( empty($data) ) {
				$this->data = $this->resources;
			} else {
				$ret = [];
				foreach( $this->resources as $r) {
					foreach( $data as $key=>$value ) {
						if(array_key_exists($key, $r) && $r[$key]===$value ) $ret[] = $r; 
					}
				}
				$this->data = $ret;
			}
			return $this->data;
		}
		
		protected function _getResourcesByQuery($query) {
			return $this->_database->SqlGetLines($query, \PDO::FETCH_ASSOC);
		}

		protected function _saveResource( array $data, $id=NULL, $table=NULL ) {
			//$this->_database->setTable($table);
			if( !empty($id) && $this->_database->getById($id) === FALSE ) {
				return NULL;
			} else if( empty($id) ) {
				unset($data["id"]);
				$resource = $this->_database->save($data);
			} else {
				$data["id"] = $id;
				$resource = $this->_database->save($data);
			}
			return $resource;
		}
		
		protected function _deleteResource( $id=NULL, $table=NULL ) {
			//$this->_database->setTable($table);
			if( $this->_database->getById($id) === FALSE ) {
				return NULL;
			}
			$this->_database->delete($id);
			if( $this->_database->getById($id) !== FALSE ) {
				return FALSE;
			}
			return TRUE;
		}
		
	}

