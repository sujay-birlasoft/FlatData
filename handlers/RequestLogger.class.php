<?php

  /**
   * This file contains the RequestLogger.class.php
   * @package The-Datatank/requests
   * @copyright (C) 2011 by iRail vzw/asbl
   * @license AGPLv3
   * @author Jan Vansteenlandt <jan@iRail.be>
   * @author Pieter Colpaert   <pieter@iRail.be>
   */ 
 
  /**
   * This RequestLogger class logs every request to a certain method of a ceratin module.
   * It will use a MySQL database and premade tables to store its data.
   */
class RequestLogger{

    /**
     * This function implements the logging part of the RequestLogger functionality.
     */
    public static function logRequest($matches,$requiredparams,$subresources) {
        R::setup(Config::$DB, Config::$DB_USER, Config::$DB_PASSWORD);
        
        //get the format out of the RESTparameters, if none specified fill in 'XML'!
        //@Jan: what if format is given through Content Type? Shouldn't we just ask
        //the printerfactory->getFormat() about what format it was?
        // the format should be something like this:
        // /module/resource/.json
        preg_match("/format=(.*)&.*/",$matches["RESTparameters"], $formatmatch); 
        if(!isset($formatmatch[1])){
            $format = "xml";
        }else{
            $format = $formatmatch[1];
        }

        $request = R::dispense('requests');
        $request->time = time();
        $request->user_agent = $_SERVER['HTTP_USER_AGENT'];
        $request->ip = $_SERVER['REMOTE_ADDR'];
        $request->url_request = TDT::getPageUrl();
        $request->module = $matches["module"];
        $request->resource = $matches['resource'];
        $request->format = $format;
        $request->subresources = implode(";",$subresources);
        $request->requiredparameter = implode(";",$requiredparams);
        $request->allparameters = $matches["RESTparameters"];
        R::store($request);
    }
}
?>
