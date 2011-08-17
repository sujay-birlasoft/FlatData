<?php

  /**
   * This handles a database related resource
   *
   * @package The-Datatank/resources/strategies
   * @copyright (C) 2011 by iRail vzw/asbl
   * @license AGPLv3
   * @author Jan Vansteenlandt
   */
class DB extends AResourceStrategy{
    
    public function __construct(){
        
    }
    
    public function call($module,$resource){
        /*
         * Here we'll extract all the db-related info and return an object for the RESTful call
         * As we're not using different tables per different type of database we'll use separate logic
         * per separate database. Perhaps this could also be implemented in a strategypattern.....
         */
        R::setup(Config::$DB,Config::$DB_USER,Config::$DB_PASSWORD);
	$param = array(':module' => $module, ':resource' => $resource);
	$results = R::getAll(
	    "select db_name,db_table,host,port,db_type,columns,db_user,db_password 
             from module,generic_resource_db,generic_resource 
             where module.module_name=:module and module.id=generic_resource.module_id 
             and generic_resource.resource_name=:resource 
             and generic_resource_db.resource_id=generic_resource.id",
	    $param
	);
        $dbtype = $results[0]["db_type"];
        $dbname = $results[0]["db_name"];
        $dbtable = $results[0]["db_table"];
        $dbport = $results[0]["port"];
        $dbhost = $results[0]["host"];
        $user = $results[0]["db_user"];
        $passwrd = $results[0]["db_password"];
        $dbcolumns = explode(";",$results[0]["columns"]);

        /*
         * According to the type of db we're going to connect with the database and 
         * retrieve the correct fields. Since we're using redbean, we might as well use it
         * to retrieve some data when the host is supported by the redbean. The only reason 
         * why this could/should be changed is to provide functionality for older non-compatible
         * versions of mysql/sqlite/postgresql.
         */
        
        $resultobject = new stdClass();
        if(strtolower($dbtype) == "mysql"){
            R::setup("mysql:host=$dbhost;dbname=$dbname",$user,$passwrd);
            $resultobject = $this->createResultObjectFromRB($resultobject,$dbcolumns,$dbtable);
        }elseif(strtolower($dbtype) == "sqlite"){
            //$dbtable is used as path to the sqlite file. 
            R::setup("sqlite:$dbtable",$user,$passwrd); //sqlite
            $resultobject = $this->createResultObjectFromRB($resultobject,$dbcolumns,$dbtable);
        }elseif(strtolower($dbtype) == "postgresql"){
            R::setup("pgsql:host=$dbhost;dbname=$dbname",$user,$passwrd); //postgresql
            $resultobject = $this->createResultObjectFromRB($resultobject,$dbcolumns,$dbtable);
        }else{
            // provide interfacing with other db's too.
            throw new DatabaseTDTException("The database you're trying to reach is not yet supported.");
        }   
        return $resultobject;
    }

    /**
     * Creates result from a resultset returned by a RedBean php query
     * Note: If similar functionality is found in other db-interfacing such as
     * NoSQL, this could be used as a general build-up method.
     */
    private function createResultObjectFromRB($resultobject,$dbcolumns,$dbtable){
        $columns = implode(",",$dbcolumns);
        $results = R::getAll(
            "select $columns from $dbtable"
        );
        $arrayOfRowObjects = array();

        foreach($results as $result){
            $rowobject = new stdClass();
            foreach($dbcolumns as $dbcolumn){
                $rowobject->$dbcolumn = $result[$dbcolumn];
            }
            array_push($arrayOfRowObjects,$rowobject);
        }
        $resultobject->object=$arrayOfRowObjects;
        return $resultobject;
    }
  }
?>