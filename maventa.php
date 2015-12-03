<?php
error_reporting(E_ALL ^ ( E_NOTICE | E_WARNING | E_DEPRECATED | E_STRICT));

try{
$client = new SoapClient("https://secure.maventa.com/apis/bravo/wsdl/");

//$result = $client->__call('UserParamsOutArray',array('ApiKeys'=>'830656d2-73b5-4953-9f3b-3b5f0702e2e0'));
$api = $client->__soapCall('login',array('email'=>'eeva.ylimartimo@velox.fi','password'=>'Hd3h79r3'));
//$result = $client->__soapCall('company_lookup',array('api_keys'=>$api->message));


echo '<pre>';
print_r($api);
echo '</pre>';

echo '<pre>';
//print_r($client->__getFunctions());
echo '</pre>';
}
catch (SoapFault $e) { 
echo '<pre>';
print_r($e);
echo '</pre>';
} 


?>
