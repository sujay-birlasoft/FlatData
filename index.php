<?php
/**
 * This file is the router. It will accept a request en refer it elsewhere using glue
 *
 * @package The-Datatank
 * @copyright (C) 2011 by iRail vzw/asbl
 * @license AGPLv3
 * @author Werner Laurensse
 * @author Pieter Colpaert
 * @author Jan Vansteenlandt
 */

require_once ('glue.php');
require_once ('printer/PrinterFactory.php');
require_once ('handlers/Exceptions.class.php');
require_once ('handlers/RequestLogger.class.php');
require_once ('handlers/ErrorHandler.class.php');
require_once ('modules/ProxyModules.php');
require_once ('TDT.class.php');
require_once ('Config.class.php');
require_once('pages/Docs.class.php');
require_once('pages/Stats.class.php');
require_once('pages/Index.class.php');
require_once('pages/Resources.class.php');

set_error_handler('wrapper_handler');
date_default_timezone_set('UTC');

/*
 * This is the former url-rewrite: it will map all urls to a certain class which will get the request
 */


$urls = array(
     '/' => 'Index',
     '/resources/' => 'Resources',
     '/docs/' => 'Docs',
     '/docs/(?P<module>.*?)/(?P<method>.*?)/.*' => 'DocPage',
     '/stats/' => 'Stats',
     '/Feedback/Messages/(?P<module>.*?)/(?P<method>.*?)/.*' => 'FeedbackHandler',
     '/(?P<module>.*?)/(?P<method>.*?)/(?P<resources>.*)' => 'ModuleHandler'
     );

//This function will do the magic. See glue.php
try {
	glue::stick($urls);
} catch(Exception $e) {
	ErrorHandler::logException($e);
}

class FeedbackHandler {
	function GET($matches) {
		require_once ('modules/Feedback/Messages.class.php');
		$message = Messages();
		$result = $message -> call();
		$rootname = 'feedback';
		$printer = PrinterFactory::getPrinter($rootname, $_GET['format'], $result);
		$printer -> printAll();
	}

	function POST($matches) {
		require_once ('handlers/PostMessage.class.php');
		$post = new PostMessage();
		$post -> post();
	}
}

class ModuleHandler {
     function GET($matches) {
	  RequestLogger::logRequest();
	  $result = new stdClass();
	  $module = $matches['module'];
	  $methodname = $matches['method'];
	  // Make sure that format is set and that the first letter is uppercase.
	  $headerlines = getallheaders();
	  if(isset($headerlines["Content-type"])) {
	       if(preg_match('/.*\/(.*?);.*?/', $headerlines["Content-type"], $matches)) {
		    $match = $matches[1];
		    //See php doc for this [0] contains the full match, 1 contains the first group
		    $_GET['format'] = ucfirst(strtolower($match));
	       }
	       
	  } elseif(!isset($_GET['format'])) {
	       $_GET['format'] = 'Xml';
	  } else {
	       $_GET['format'] = ucfirst(strtolower($_GET['format']));
	  }

	  $resources=array();
	  if(isset($matches['resources'])){
	       $resources = explode("/",$matches['resources']);
	       array_pop($resources); // remove the last elemenet because that just contains the GET parameters
	  }
	  

	  if(file_exists("modules/$module/$methodname.class.php")) {
	       //get the new method
	       include_once ("modules/$module/$methodname.class.php");
	       $method = new $methodname();

	       //Now let's separate the required parameters from the filters
               $reqs = $methodname::getRequiredParameters();
               foreach($reqs as $param){
		   //set the parameter of the method
		   if(!isset($resources[0])){
		       throw new ParameterTDTException($param);
		   }
		   $method->setParameter($param, $resources[0]);
		   //removes the first element and reindex the array
		   array_shift($resources);
               }

	       // check if the given format is allowed by the method
	       // if not, throw an exception and return the allowed formats
	       // to the user.
	       if((!in_array(strtolower($_GET['format']),$method->getAllowedPrintMethods()))){
		   throw new FormatNotAllowedTDTException($_GET['format'],$method::getAllowedPrintMethods());
	       }
	       
	       //execute the method when no error occured
	       $result = $method->call();
	  } else if (array_key_exists($module,ProxyModules::$modules)) {
	      //If we cannot find the modulename locally, we're going to search for it through proxy
	      $result = ProxyModules::call($module, $methodname, $_GET);		
	  } else {
	       throw new MethodOrModuleNotFoundTDTException($module . "/" .$methodname);
	  }

	  //Support REST actions:
	  //allow to specify deeper requests
	  if(sizeof($resources) > 0){
	       foreach($resources as $resource){
		    if(is_object($result) && isset($result->$resource)){
			 $result = $result->$resource;
		    }elseif(is_array($result) && isset($result[$resource])){
			 $result = $result[$resource];
		    }else{
			 break;//on error, just return what we have so far
		    }
	       }
	       if(!is_object($result)){
		    $o = new stdClass();
		    $resource = $resources[sizeof($resources)-1];
		    $o->$resource = $result;
		    $result = $o;
	       }
	  }

	  $rootname = $methodname;
	  $rootname = strtolower($rootname);
 
	  $printer = PrinterFactory::getPrinter($rootname, $_GET['format'], $result);
	  $printer->printAll();
     }
}

?>
