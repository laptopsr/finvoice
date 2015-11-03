<?php # HelloServer.php
# Copyright (c) 2005 by Dr. Herong Yang
#
function hello($someone) { 
   return "Hello " . $someone . "!";
} 
   $server = new SoapServer(null, 
      array('uri' => "urn://www.herong.home/res"));
   $server->addFunction("hello"); 
   $server->handle(); 
?>
