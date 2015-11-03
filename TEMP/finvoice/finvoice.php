<?php

/**
 * <copyright file="finvoice.php" company="Visma Severa Oy">
 * Copyright (C) 2011 by Visma Severa Oy, http://dev.severa.com/
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * </copyright>
 * <summary>Visma Severa API usage example, Load list of FINVOICEs trough API</summary>
 */

/**
 * For this to work properly, following PHP extensions are required:
 * - soap: binary SOAP client
 * - openssl: HTTPS wrapper for all I/O methods (including SOAP)
 * - dom: DOM/XML Document Object Model for XML manipulation
 * - xml: libxml for XML manipulation
 * - session: Session Handling extension
 *
 * For any details about FINVOICE, see:
 * @link http://www.fkl.fi/verkkolasku/english/electronic_invoice_for_companies/tekniset_yrityksen_verkkolasku.htm
 */

/**
 * SOAP settings...
 */
ini_set('soap.wsdl_cache_enable' , 1);		// Enable WSDL-caching
ini_set('soap.wsdl_cache_ttl' , 3600);		// Store the WSDL for one hour

/**
 * Class to contain SOAP configuration for Visma Severa API
 */
class SeveraAPI {
	public static $WSDL_URL;			// Different URL
	public static $SOAP_NameSpace;		// Shared information
	public static $webServicePassword;	// Shared information
	public static $website_CharSet;		// Shared information
	
	/**
	 * Static "constructor"
	 */
	public static function Init()
	{
		SeveraAPI::$SOAP_NameSpace = "http://soap.severa.com/";
		SeveraAPI::$website_CharSet = "UTF-8";
		SeveraAPI::$WSDL_URL = "https://sync.severa.com/webservice/S3/API.svc/WSDL?wsdl";
		SeveraAPI::$webServicePassword = '------This-is-your-API-key------';
	}
}


/**
 * Initialize connection object
 * @return object a SOAP client object
 */
function GetClient()
{
	$WSDL_URL = SeveraAPI::$WSDL_URL;
	$SOAP_NameSpace = SeveraAPI::$SOAP_NameSpace;

	try {
		$soapClient = @new SoapClient($WSDL_URL, Array("uri" => $SOAP_NameSpace,
				'encoding' => 'UTF-8',	# 'ISO-8859-1',
				'soap_version' => SOAP_1_1,
				'trace' => true,
				# enable __getLastRequest()
				'exceptions' => true,
				'cache_wsdl' => WSDL_CACHE_BOTH));


	} catch (Exception $exc) {
		print "Failed: <code>" . $exc->getMessage() . "</code><hr/>\n";
		die("Crap! Failed while constructing new client.");
	}

	$soapHeader = new SoapHeader(SeveraAPI::$SOAP_NameSpace,
								"WebServicePassword", SeveraAPI::$webServicePassword);
	$soapClient->__setSoapHeaders($soapHeader);

	return $soapClient;
}

/**
 * For debugging purposes: See what the remote end knows
 * @param object	SOAP client
 */
function DisplayWhatICan(&$soapClient)
{
	$methods = $soapClient->__getFunctions();
	if ($methods) {
		print "<h1>Methods</h1>\n";
		print "<table border='1'>\n";
		foreach ($methods as $aMethod) {
			print "<tr><td><pre>" . htmlentities($aMethod) . "</pre></td></tr>\n";
		}
		print "</table>\n";
	}

	$types = $soapClient->__getTypes();
	if ($types) {
		print "<h1>Types</h1>\n";
		print "<table border='1'>\n";
		foreach ($types as $aType) {
			print "<tr><td><pre>" . htmlentities($aType) . "</pre></td></tr>\n";
		}
		print "</table>\n";
	}
}

/**
 * Get the FINVOICE XML
 * @param object	SOAP client
 * @param string	Invoice status to use for FINVOICE transfer
 * @return bool | array		False if no invoice status found or no invoice number for status or not active status.
 *							Array of FINVOICE XML othervise.
 */
function GetFINVOICEs(&$soapClient, $invoice_status)
{
	$params = new stdClass;
	$params->status = $invoice_status;
	$params->businessUnitGUID = null;
	$params->options = null;
	try {
		$invoice_statuses = $soapClient->GetAllInvoiceStatuses();
		$got_it = false;
		foreach ($invoice_statuses->GetAllInvoiceStatusesResult->InvoiceStatus as $idx => $status) {
			if (!$status->IsActive) {
				continue;
			}
			if ($invoice_status == $status->Name &&
				$status->HasInvoiceNumber) {
				$got_it = true;
			}
		}
		
		// No, does not look like a good invoice status to get.
		if (!$got_it) {
			return false;
		}
		
		// Get the invoices with given status
		$finvoices = $soapClient->GetFinvoicesByStatus($params);
	} catch (Exception $exc) {
		// Try to parse WCF exception
		print "Failed: <code>" . $exc->getMessage() . "</code>\n";
		if (isset($exc->detail->ExceptionDetail) && is_object($exc->detail->ExceptionDetail)) {
			$foundIt = false;
			foreach (get_object_vars($exc->detail->ExceptionDetail) as $vari => $valu) {
				if (preg_match("/Exception/i", $vari) && $valu) {
					$foundIt = true;
					print "<br/>Detail: <code>{$exc->detail->ExceptionDetail->$vari->Description}</code><hr/>\n";
				}
			}
			if (!$foundIt) {
				print "<br/>Detail: <code>" . print_r($exc->detail->ExceptionDetail, true) . "</code><hr/>\n";
				print "<br/>Detail: <code>" . print_r($exc->detail->ExceptionDetail->Type, true) . "</code><hr/>\n";
			}
		} elseif (isset($exc->detail) && is_object($exc->detail)) {
			$foundIt = false;
			foreach (get_object_vars($exc->detail) as $vari => $valu) {
				if (preg_match("/Exception/i", $vari) && $valu) {
					$foundIt = true;
					print "<br/>Detail: <code>{$exc->detail->$vari->Description}</code><hr/>\n";
				}
			}
			if (!$foundIt) {
				print "<br/>Detail: <code>" . print_r($exc->detail, true) . "</code><hr/>\n";
			}
		} else {
			print "<hr/>\n";
		}
		#print "<code>" . htmlentities($soapClient->__getLastRequest()) . "</code><hr/>\n";
		#print "<code>" . htmlentities($soapClient->__last_response) . "</code><hr/>\n";
		#var_dump($exc);
		die("GetFINVOICEs: Failed while making a call.");
	}
	$finvoices_out = Array();
	foreach ($finvoices->GetFinvoicesByStatusResult->Finvoice as $finvoice) {
		$finvoices_out[] = $finvoice->Finvoice;
	}
	
	return $finvoices_out;
}

/**
 * Validate XML
 * @param string	The FINVOICE XMl string to validate
 */ 
function ValidateFINVOICE($xml)
{
		libxml_use_internal_errors(true);

		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->loadXML($xml);

		$errors = libxml_get_errors();
		if (empty($errors)) {
				$validation_stat = $doc->validate();
				if (!$validation_stat) {
					$message = "FINVOICE does not pass validation! See Finvoice.dtd document type definition.";
					return $message;
				}
				return true;
		}

		$error = $errors[0];
		if ($error->level < 3) {
				return true;
		}

		$lines = explode("r", $xml);
		$line = $lines[($error->line)-1];

		$message = $error->message . ' at line ' . $error->line . ':<br /><code>' . htmlentities($line) . "</code>";

		return $message;
}

/**
 * Process the list of received FINVOICEs
 * @param array		The array of FINVOICEs we got from SOAP-call
 */ 
function ProcessFINVOICEs(Array& $finvoices_in)
{
	$finvoices = Array();
	$finvoice_numbers = Array();
	$content_types = Array();

	foreach ($finvoices_in as $finvoice_str) {
		if (!preg_match(";</SOAP-ENV:Envelope>(.+)\$;si", $finvoice_str, $matches)) {
			// Really bad FINVOICE
			continue;
		}
        $finvoice_str = trim($matches[1]);
		if (preg_match("/^<\?xml version=\"1.0\" encoding=\"([^\"]+)\"/", $finvoice_str, $matches)) {
			$content_types[] = "text/xml; charset=" . $matches[1];
		}
        $finvoices[] = $finvoice_str;
    }

	// Severa FINVOICE is valid when outputted, but we
	// validate the FINVOICE XML just to make sure it conforms with the DTD.
	foreach ($finvoices as $finvoice_idx => $xml) {
		$stat = ValidateFINVOICE($xml);
		if (is_string($stat)) {
			die("While parsing FINVOICE #{$finvoice_idx}. Failed:<br/>$stat");
		}
		$parser = xml_parser_create();
		xml_parse_into_struct($parser, $xml, $values, $indexes);
		xml_parser_free($parser);

		if (!isset($indexes["INVOICENUMBER"])) {
			die("While parsing ({$finvoice_idx}). bad FINVOICE file! no invoice number");
		}
		$invoice_number = $values[$indexes["INVOICENUMBER"][0]];
		$invoice_number = intval($invoice_number["value"]);
		$finvoice_numbers[$finvoice_idx] = $invoice_number;
	}

	if (count($finvoices) == 0 ||
		count($content_types) != count($finvoices) ||
		count($finvoice_numbers) != count($finvoices)) {
		die("FINVOICE file! Cannot parse.");
	}
	
	$_SESSION["finvoices"] = $finvoices;
	$_SESSION["finvoice_numbers"] = $finvoice_numbers;
	$_SESSION["content_types"] = $content_types;
}


	/**
	 * Begin script
	 */

	// Session
	session_start();

	// See if we already have a bunch of FINVOICEs stored into a session...
	if (!isset($_SESSION["finvoices"])) {
		// Get a handle of a SOAP-client and load some.
		SeveraAPI::Init();
		$api_client = GetClient();
#DisplayWhatICan($api_client);
		$finvoices = GetFINVOICEs($api_client, "Sent");
		if ($finvoices === false) {
			die("Bad status!");
		}
		ProcessFINVOICEs($finvoices);

		// If only 1 FINVOICE was uploaded, go display it!
		if (count($_SESSION["finvoices"]) == 1) {
			$finvoice_idx = 0;
			header("Content-Type: " . $_SESSION["content_types"][$finvoice_idx]);
			print $_SESSION["finvoices"][$finvoice_idx];
			exit();
		}
		
		// Fall trough to select one
	}

	// Are we displaying one?
	if (isset($_GET["finvoice_idx"]) && is_numeric($_GET["finvoice_idx"]) !== false) {
		// Output:
		$finvoice_idx = intval($_GET["finvoice_idx"]);
		if (!isset($_SESSION["finvoices"][$finvoice_idx])) {
			die("Bad index!");
		}
		
		// Just by setting proper content type, all browsers are capable of
		// doing the XSLT using the the .xsl -file in the same directory.
		// There is <?xml-stylesheet type="text/xsl" href="Finvoice.xsl" > in the FINVOICE XML
		// Alternatively we could do the XSLT in PHP here.
		header("Content-Type: " . $_SESSION["content_types"][$finvoice_idx]);
		print $_SESSION["finvoices"][$finvoice_idx];
		exit();
	}

	// Display the list of FINVOICEs we have.
	foreach ($_SESSION["finvoices"] as $finvoice_idx => $xml) {
		print "<a href=\"?finvoice_idx={$finvoice_idx}\">Invoice #{$_SESSION["finvoice_numbers"][$finvoice_idx]}</a><br/>\n";
	}
	
	// Clear out all stored finvoices (for testing purposes)
	//$_SESSION["finvoices"] = null;
?>
