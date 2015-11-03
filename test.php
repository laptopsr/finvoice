<?php
$xml = file_get_contents("attach.xml");

$ssSeverUrlPath = 'https://filetransfer.nordea.com/services/CorporateFileService';
$ch = curl_init($ssSeverUrlPath);
curl_setopt($ch, CURLOPT_HEADER,0);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$output = curl_exec($ch);
curl_close($ch);
print_r($output);

?>
