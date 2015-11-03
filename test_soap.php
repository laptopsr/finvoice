<?php
error_reporting(E_ALL ^ ( E_NOTICE | E_WARNING | E_DEPRECATED | E_STRICT));

$client = new SoapClient("https://filetransfer.nordea.com/services/CorporateFileService.wsdl",array());

$auth = array(
        'User ID'=>'11111111',
        'PIN'=>'WSNDEA1234',
        'SystemId'=> array('_'=>'DATA','Param'=>'PARAM'),
        );
  $header = new SoapHeader('NAMESPACE','Auth',$auth,false);
  $client->__setSoapHeaders($header);

echo '<pre>';
print_r($client->__getFunctions());
echo '</pre>';
?>
