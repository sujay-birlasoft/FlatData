<?php
/**
 * Class for reading(fetching) a remote resource
 *
 * @package The-Datatank/model/resources/read
 * @copyright (C) 2011 by iRail vzw/asbl
 * @license AGPLv3
 * @author Jan Vansteenlandt
 * @author Pieter Colpaert
 */
include_once("model/DBQueries.class.php");

class RemoteResourceReader extends AReader{

    private $remoteResource;

    public function __construct($package,$resource, $RESTparameters, $remoteResourceDocumentation){
        parent::__construct($package,$resource, $RESTparameters);  
        $this->remoteResource = $remoteResourceDocumentation;
        $remoteRes = DBQueries::getRemoteResource($package,$resource);
        $this->base_url = $remoteRes["url"];
        $this->remote_package = $remoteRes["package"];
        $this->resource_name = $remoteRes["resource"];
    }

    /**
     * read method
     */
    public function read(){

	//extract the right parameters (the non optional ones) and concatenate them to create the right URL
	$params = "?";
	foreach(array_keys($this->remoteResource->parameters) as $key){
            if(!isset($this->remoteResource->requiredparameters[$key]) && isset($this->$key)){   
                $params .= $key . "=" . urlencode($this->$key) . "&";
            }
	}
	$params = rtrim($params, "&");

	//the url consists of the baseurl (this has a trailing slash and contains the subdir) - the resource is a specifier in the baseurl
	//params is a url containing the possible
	$url = $this->base_url . $this->remote_package . "/".$this->resource_name . "/";

        foreach($this->remoteResource->requiredparameters as $param){
            $url = $url . $this->$param."/";
        }

        $url= rtrim($url, "/");
        //add format: php because we're going to deserialize this
        $url .= ".php";
        
        $url .= $params;

	//Request the remote server and check for errors. If no error, unserialize the data
	$options = array("cache-time" => 0, "headers" => array("User-Agent" => isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:""));
	$request = TDT::HttpRequest($url, $options);
	if(isset($request->error)){
	    throw new RemoteServerTDTException($request->data);
	}

	//unserialize the data of the request and return it!
        $res = $this->resource_name;
	$obj =  unserialize($request->data);
        return $obj[$res];
    }
    
    protected function setParameter($name,$val){
	$this->$name = $val;
    }

    /**
     * get the documentation about getting of a resource
     * @return String with some documentation about the resource
     */
    public function getDocumentation(){
        return $this->remoteResource;
    }
}
?>