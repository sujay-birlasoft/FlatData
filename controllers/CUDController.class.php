<?php
/**
 * This is the controller which will handle Real-World objects. This means mainly write actions. 
 *
 * @package The-Datatank/controllers
 * @copyright (C) 2011 by iRail vzw/asbl
 * @license AGPLv3
 * @author Pieter Colpaert
 * @author Jan Vansteenlandt
 */
include_once('formatters/FormatterFactory.class.php');
include_once('aspects/logging/RequestLogger.class.php');
include_once('model/filters/FilterFactory.class.php');

class CUDController extends AController{

    private $formatterfactory;

    /**
     * You cannot get a real-world object, only its representation. Therefore we're going to redirect you to .about which will do content negotiation.
     */
    function GET($matches) {
        //get the current URL
        $pageURL = 'http';
        if (isset($_SERVER["HTTPS"])) {$pageURL .= "s";}
        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        }
        $pageURL = rtrim($pageURL, "/");
        //add .about before the ?
        if(sizeof($_GET)>0){
            $pageURL = str_replace("?", ".about?", $pageURL);
        }else{
            $pageURL .= ".about";
        }
        header("HTTP/1.1 303 See Other");
        header("Location:" . $pageURL);
    }

    function PUT($matches){
        $package = $matches["package"];
        $resource = $matches["resource"];

        //fetch all the PUT variables in one array
        parse_str(file_get_contents("php://input"),$_PUT);

        //we need to be authenticated
        if($_SERVER['PHP_AUTH_USER'] == Config::$API_USER && $_SERVER['PHP_AUTH_PW'] == Config::$API_PASSWD){
            $model = ResourcesModel::getInstance();
            $model->addResource($package,$resource, $_PUT);
        }else{
            throw new AuthenticationTDTException("Cannot PUT");
        }
    }
 
    /**
     * Delete a resource (There is some room for improvement of queries, or division in subfunctions but for now, this'll do the trick)
     */
    public function DELETE($matches){
        $package = $matches["package"];
        $resource = "";
        if(isset($matches["resource"])){    
            $resource = $matches["resource"];
        }
        
        if($_SERVER['PHP_AUTH_USER'] == Config::$API_USER && $_SERVER['PHP_AUTH_PW'] == Config::$API_PASSWD){        
            //delete the package and resource when authenticated and authorized in the model
            $model = ResourcesModel::getInstance();
            if($resource == ""){
                $model->deletePackage($package);
            }else{
                $model->deleteResource($package,$resource);
            }
        }
    }

    public function POST($matches){
        $package = $matches["package"];
        $resource = $matches["resource"];
        //TODO
        if($_SERVER['PHP_AUTH_USER'] == Config::$API_USER && $_SERVER['PHP_AUTH_PW'] == Config::$API_PASSWD){        
            //delete the package and resource when authenticated and authorized in the model
            $model = ResourcesModel::getInstance();
            $model->updateResource($package,$resource,$_POST);
        }

    }
}

?>