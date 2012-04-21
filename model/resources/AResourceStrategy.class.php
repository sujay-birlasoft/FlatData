<?php
/**
 * This is the abstract class for a strategy.
 *
 * @package The-Datatank/model/resources
 * @license AGPLv3
 * @author Pieter Colpaert   <pieter@iRail.be>
 * @author Jan Vansteenlandt <jan@iRail.be>
 */

include_once("model/resources/GenericResource.class.php");

abstract class AResourceStrategy{

    /**
     * This functions contains the businesslogic of a read method (non paged reading)
     * @return StdClass object representing the result of the businesslogic.
     */
    abstract public function read(&$configObject,$package,$resource);

    /**
     * Delete all extra information on the server about this resource when it gets deleted
     */
    public function onDelete($package,$resource){
        // get the name of the class (=strategy)
        $strat = strtolower(get_class($this));
        $resource_table = (string)GenericResource::$TABLE_PREAMBLE . $strat;
        return R::exec(
            "DELETE FROM $resource_table
                    WHERE gen_resource_id IN 
                          (SELECT generic_resource.id FROM generic_resource,package,resource 
                           WHERE resource.resource_name=:resource
                                 and package.package_name=:package
                                 and resource_id = resource.id
                                 and package.id=package_id)",
            array(":package" => $package, ":resource" => $resource)
        );
    }
    

    /**
     * When a strategy is added, execute this piece of code.
     */
    public function onAdd($package_id, $gen_resource_id){
        if($this->isValid($package_id,$gen_resource_id)){
            // get the name of the class ( = strategyname)
            $strat = strtolower(get_class($this));
            $resource = R::dispense(GenericResource::$TABLE_PREAMBLE . $strat);
            $resource->gen_resource_id = $gen_resource_id;
            
            // for every parameter that has been passed for the creation of the strategy, make a datamember
            $createParams = array_keys($this->documentCreateParameters());
            
            foreach($createParams as $createParam){
                // dont add the columns parameter, this is a separate parameter that's been stored into another table
                // every parameter that requires separate tables, apart from the autogenerated one
                // must be included in the if else structure.
                if($createParam != "columns"){
                    if(!isset($this->$createParam)){
                        $resource->$createParam = "";
                    }else{
                        $resource->$createParam = $this->$createParam;
                    }   
                }
            }
            return R::store($resource);
        }
    }

    public function onUpdate($package, $resource){
        
    }
    

    public function setParameter($key,$value){
        $this->$key = $value;
    }

    /**
     * Gets all the required parameters to add a resource with this strategy
     * @return array with the required parameters to add a resource with this strategy
     */
    public function documentCreateRequiredParameters(){
        return array();
    }
    
    public function documentReadRequiredParameters(){
        return array();
    }
    
    public function documentUpdateRequiredParameters(){
        return array();
    }
    
    public function documentCreateParameters(){
        return array();
    }
    
    public function documentReadParameters(){
        return array();
    }
    
    public function documentUpdateParameters(){
        return array();
    }
    

    /**
     *  This function gets the fields in a resource
     * @param string $package
     * @param string $resource
     * @return array with column names mapped onto their aliases
     */
    public function getFields($package, $resource){
        return array();
    }
    

    /**
     * This functions performs the validation of the addition of a strategy
     * It does not contain any arguments, because the parameters are datamembers of the object 
     * Default: true, if you want your own validation, overwrite it in your strategy.
     * NOTE: this validation is not only meant to validate parameters, but also your dataresource.
     * For example in a CSV file, we also check for the column headers, and we store them in the published columns table
     * This table is linked to a generic resource, thus can be accessed by any strategy!
     * IMPORTANT !!: throw an exception when you want your personal error message for the validation.
     */
    protected function isValid($package_id,$gen_resource_id){
        return true;
    }
    
    /**
     * Throws an exception with a message, and prohibits the resource to be added
     */
    protected function throwException($package_id, $gen_resource_id,$message){
        $resource_id = DBQueries::getAssociatedResourceId($gen_resource_id);
        $package = DBQueries::getPackageById($package_id);
        $resource = DBQueries::getResourceById($resource_id);
        ResourcesModel::getInstance()->deleteResource($package, $resource, array());
        throw new ResourceAdditionTDTException("$message");
    }
}
?>