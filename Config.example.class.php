<?php
  /* Copyright (C) 2011 by iRail vzw/asbl
   * License: AGPLv3
   *
   * Please configure this file by filling out the right elements and copy this to Config.class.php
   */
  /**
   * Please configure this file by filling out the right elements and copy this to Config.class.php. Ofcourse renaming this file to Config.class.php is equally good.
   * @package The-Datatank
   * @copyright (C) 2011 by iRail vzw/asbl
   * @license AGPLv3
   * @author Jan Vansteenlandt <Jan@iRail.be>
   * @author Pieter Colpaert   <pieter@iRail.be>
   */
class Config{
     public static $MySQL_USER_NAME = "...";
     public static $MySQL_PASSWORD  = "...";

     //The mysql database is the database where the errors and requests are being stored.
     public static $MySQL_DATABASE =  "...";

     //add a trailing slash!
     public static $HOSTNAME = "http://localhost/";

  }
?>
