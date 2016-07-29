<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// To override API URL. configure below constent.
//define('API_URL', 'https://uat.ourtradie.com.au/tradie/api/');

require_once 'src/api.php';

/** Please replace your client_id, client_secret & redirect_uri mentioned in Ourtradie Api settings page.**/
$options = array(
			'client_id'				=>	'XXXXXXXXXXX',
			'client_secret'			=>	'YYYYYYYYYYYYYYYYYYYYYYYYY',
			'redirect_uri'			=> 'http://localhost/PM-API-Client-Library/SyncProperties.php'
			);


$api = new OurtradieApi($options, $_REQUEST);
$response = $api->authenticate();


// If you are passing XML string, use XMLData field or sending XML file use XMLFile field. Must use any one of these two fields.
$params = array(
				'XMLData' 	 => '<?xml version="1.0" encoding="utf-8"?>
									<root>
									  <Managements />
									  <Creditors />
									  <PropertyStatistics />
									  <PaidJobs />
									  <TenantInvoices />
									  <Dissections />
									  <Transactions />
									  <Inactive>
										<Properties />
										<Owners />
										<Tenants />
										<Creditors />
									  </Inactive>
									  <LastUpdateDate>2016-07-12 08:12:35</LastUpdateDate>
									</root>'
			);

//$files['XMLFile'] = '/home/administrator/projects/EXCELPROP.xml';	//local xml file path

$response = $api->query('SyncProperties', $params, '', $files, true);
print_r($response);
