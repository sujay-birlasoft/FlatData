<?php
/**
 * This class handles a SHP file
 *
 * @package The-Datatank/custom/strategies
 * @copyright (C) 2011 by iRail vzw/asbl
 * @license AGPLv3
 * @author Lieven Janssen
 */
include_once("custom/strategies/SHP.class.php");

class ZippedSHP extends SHP {

    public function documentCreateParameters(){
        return array("url" => "The path to the zipped shape file (can be a url).",
					 "shppath" => "The path to the shape file within the zip.",
                     "EPSG" => "EPSG coordinate system code.",
                     "columns" => "The columns that are to be published.",
                     "PK" => "The primary key for each row.",
        );
    }
    
    public function documentCreateRequiredParameters(){
        return array("url","shppath");    
    }

    public function documentReadRequiredParameters(){
        return array();
    }
    
    public function documentReadParameters(){
        return array();
    }

    protected function isValid($package_id,$generic_resource_id) {
        if(isset($this->url)){
			$url = $this->url;
		} else {
			$this->throwException($package_id,$generic_resource_id, "Can't find url of the zipfile.");
        }
		
		$isUrl = (substr($url , 0, 4) == "http");
		$tmpGuid = com_create_guid();
		$tmpGuid = substr($tmpGuid, 1, strlen($tmpGuid) - 2);

		if (!is_dir("tmp")) {
			mkdir("tmp");
		}
		
		if ($isUrl) {
			file_put_contents("tmp/" . $tmpGuid . ".zip", file_get_contents($url));

			$zipFile = "tmp/" . $tmpGuid . ".zip";
		} else {
			$zipFile = $url;
		}
		
		$zip = new ZipArchive;
		$res = $zip->open($zipFile);
		if ($res === TRUE) {
			 $zip->extractTo("tmp/" . $tmpGuid);
			 $zip->close();
		} else {
			$this->throwException($package_id,$generic_resource_id, "Can't unzip zipfile.");
		}
		 
		$this->url = "tmp/" . $tmpGuid . "/" . $this->shppath;
		
		$retVal = parent::isValid($package_id,$generic_resource_id);
		
		$this->url = $url;
		
		if ($isUrl) {
			unlink("tmp/" . $tmpGuid . ".zip");
		}
		$this->deleteDir("tmp/" . $tmpGuid);
		
        return $retVal;
    }	
	
    public function read(&$configObject) {
		set_time_limit(1000);

        if(isset($configObject->url)){
            $url = $configObject->url;
        }else{
            throw new ResourceTDTException("Can't find url of the zipfile.");
        }
	
		$isUrl = (substr($url , 0, 4) == "http");
		$tmpGuid = com_create_guid();
		$tmpGuid = substr($tmpGuid, 1, strlen($tmpGuid) - 2);

		if (!is_dir("tmp")) {
			mkdir("tmp");
		}

		if ($isUrl) {
			file_put_contents("tmp/" . $tmpGuid . ".zip", file_get_contents($url));

			$zipFile = "tmp/" . $tmpGuid . ".zip";
		} else {
			$zipFile = $url;
		}
		
		$zip = new ZipArchive;
		$res = $zip->open($zipFile);
		if ($res === TRUE) {
			 $zip->extractTo("tmp/" . $tmpGuid);
			 $zip->close();
		} else {
			throw new ResourceTDTException("Can't unzip zipfile.");
		}
		 
		$configObject->url = "tmp/" . $tmpGuid . "/" . $configObject->shppath;

		$retVal = parent::read($configObject);

		if ($isUrl) {
			unlink("tmp/" . $tmpGuid . ".zip");
		}
		$this->deleteDir("tmp/" . $tmpGuid);
		
		return $retVal;
    }
	
	private function deleteDir($dir)
	{
	   if (substr($dir, strlen($dir)-1, 1) != '/')
		   $dir .= '/';

	   if ($handle = opendir($dir))
	   {
		   while ($obj = readdir($handle))
		   {
			   if ($obj != '.' && $obj != '..')
			   {
				   if (is_dir($dir.$obj))
				   {
					   if (!deleteDir($dir.$obj))
						   return false;
				   }
				   elseif (is_file($dir.$obj))
				   {
					   if (!unlink($dir.$obj))
						   return false;
				   }
			   }
		   }

		   closedir($handle);

		   if (!@rmdir($dir))
			   return false;
		   return true;
	   }
	   return false;
	}
}
?>