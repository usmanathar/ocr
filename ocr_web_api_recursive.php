<?php
set_time_limit(0); 
//require_once('C:/ocr/SFTP/vendor/autoload.php');
require_once('/var/www/html/ocr/SFTP/vendor/autoload.php');
use phpseclib\Net\SFTP;


$remote_host ='23.122.104.252';//lrwic.com   New IP
//$remote_host ='192.168.1.120';//lrwic.com   New IP
$remote_port ='22';

$login='ocr_lrwic';
$password='the_proxy@OCR';


$sftp = new SFTP($remote_host, $remote_port);
		  
if (!$sftp->login($login, $password)) {		  			
	echo 'Login to remote host'.$remote_host. 'failed';
}
else{
	echo "Login Successfull.<br>";  
}

$pathname ='/var/www/html/public_html/faxFiles/facility_21';

$files = $sftp->nlist($pathname);
print_r($files):
exit("=-098");
foreach ($files as $file) {
			  
	if (substr($file, 0, 1) == '.') continue;
	if ($file == '.' || $file == '..') continue;
	//++$filecount;
		
	$upload_path  = 'C:/ocr/fax_documents/'; 
	$upload_dir   = 'C:/ocr/fax_documents';
				
	
	if(!file_exists($upload_path))
	{
		mkdir($upload_dir, 0777, TRUE);	    
	}
	
	$local_file_name 	= "$upload_dir/$file";
	
	
	$sftp->get("$pathname/$file", "$local_file_name");// copies filename.remote to filename.local from the SFTP server
	
	////////////////////////////////////////////////	
	$upload_path  = 'C:/ocr/google_documents/'; 
	$upload_dir   = 'C:/ocr/google_documents';
				
	
	if(!file_exists($upload_path))
	{
		mkdir($upload_dir, 0777, TRUE);	    
	}
	
	$local_file_name 	= "$upload_dir/$file";
	
	
	$sftp->get("$pathname/$file", "$local_file_name");
	
	$path = $file;
	$fileName = basename($path);
	
		
	if (!$sftp->delete("$pathname/$file")) {				
		
	}
	
	
}
///////////////////////////////////CoverMyMeds//////////////////////////////////////////////////////////////////////////
$resultPath = 'C:\\ocr\\engine_results';

array_map( 'unlink', array_filter((array) glob("$resultPath/*") ) );//good working


$wdir = "C:\\Users\\EZ-ocr\\Desktop\\Image Decoder";
chdir($wdir);
// exec('java -jar app-assembly-1.0-SNAPSHOT.jar covermymeds C:/ocr/fax_documents C:\ocr\engine_results', $output, $return);
//exec('java -jar app-assembly-1.0-SNAPSHOT.jar covermymeds C:/ocr/fax_documents C:\ocr\engine_results', $output, $return);


$files = glob("C:/ocr/engine_results/*.txt");
if (count($files) > 0) {
	
	$i = 0;
	process_covermymeds($files, $i);    
}

function process_covermymeds($afiles, $i)
{
	$file = $afiles[$i];
	echo basename($file)."<br>"; //file name only, not path
	$email_addr = 'support@faxage.com';
	
	$end_point_url = 'https://lrwic.com/beta/api/Pdf_decoder';
	
	
	
	$pdfFileName = pathinfo($file, PATHINFO_FILENAME);
	
	$pdfFile = "C:/ocr/fax_documents/".$pdfFileName.""; 
	$file_name_with_full_path = realpath("$file");
	
	////////////////////////////////////////////////////////////////////////////////////
	
	$filenames = array("$file_name_with_full_path");
	$reportContents = file_get_contents($file_name_with_full_path);
	
	////////////////////////////////////////////////////////////////////////////////////////
	
	
	//Prior Authorization Assistance by CoverMyMeds
	$strPos = stripos($reportContents,'Prior Authorization Assistance by');
	$strPos1 = stripos($reportContents,'CoverMyMaeds');
	$strPos2 = stripos($reportContents,'CoverMyMeds');
	$strPos3 = stripos($reportContents,'key.covermymeds.com');
	$strPos4 = stripos($reportContents,'go.covermymeds.com');
	
	if($strPos1 !== false || $strPos2 !== false || $strPos3 !== false)	
	{
		$configName = 'CoverMyMedsPA';
		$reportContents = $reportContents;
		echo "<br>Matched:CoverMyMedsPA, break. <br>";
		
	}
	///////////////////////////////////////////////////////////////////////////////////////////
	
	
	$reportData = '';
	switch($configName){
						
			case "CoverMyMedsPA":
				
				$reportData = CoverMyMedsPAHandler(basename($pdfFile),$reportContents,'CoverMyMedsPA',$email_addr);
			break;	
			
	}
	
	if(!empty($reportData) && $configName == 'CoverMyMedsPA'){
		
		
		upload_fax_pdf($end_point_url, $email_addr, $pdfFile);
	
		/*print "<pre>";
		print_r($reportData);
		print "</pre>";*/
		
		$json_string = json_encode($reportData);
		$ch = curl_init();
					
		curl_setopt($ch, CURLOPT_URL, $end_point_url); //local
		
		//curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   
		//curl_setopt($ch, CURLOPT_USERPWD, $api_key.':'.$password);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		//curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		
		
		$result = curl_exec($ch);
		curl_close($ch);
		
		
		if (file_exists($pdfFile)) {
			unlink($pdfFile);
			
		}
	}
	else{
		echo "Not recognized by script.<br>";	
	}
	
	if (file_exists($file)) {
	    unlink($file);
	    
	} else {
	    // File not found.
	}	
	
	unset($afiles[$i]);
	if (count($afiles) > 0) {
		//echo "allfiles <pre>"; print_r($afiles);
		$i++;
		process_covermymeds($afiles, $i);
	}
}


$files_folder = "C:/ocr/fax_documents";


$ocrLabs = array("AmericanEsotericLabs","NexusLabs","BaptistHealthMedical","RadiologyAssociatesPA");
if (count($files) > 0) {
	
	$i = 0;
	processFaxFiles($files, $i, $ocrLabs);    
}

//////////////////////////////////////////////////////////////////

function processFaxFiles($afiles, $i, $ocrLabs){
	$file = $afiles[$i];
	echo basename($file)."<br>";
	$email_addr = 'support@faxage.com';
	
	$end_point_url = 'https://lrwic.com/beta/api/Pdf_decoder';
	
	
	$file_name_with_full_path = realpath("$file");
	
	////////////////////////////////////////////////////////////////////////////////////
	//$filenames = array("/tmp/1.jpg", "/tmp/2.png");
	$filenames = array("$file_name_with_full_path");
	
	$files = array();
	foreach ($filenames as $f){
	   $files[$f] = file_get_contents($f);
	}
	
	$url = "http://localhost:8100/api/v1/upload-simple";
	
	
	
	$defaultOptions = array("ocrLang" =>"eng","ipf" =>"xs6e7925");
	$coverMyMedOptions = array("ocrLang" =>"eng","ipf" =>"none");
	$rapaOptions = array("ocrLang" =>"rapa","ipf" =>"xs6e7995");
	$conwayOptions = array("ocrLang" =>"eng","ipf" =>"all");
	/////////////////////////////////////////////////////////////////////////////////
	$fields = array("ocrOptions"=>json_encode( $defaultOptions ));
	$url_data = http_build_query($fields);		
	$boundary = uniqid();
	$delimiter = '-------------' . $boundary;	
	$post_data = build_data_files($boundary, $fields, $files);
	//print "<pre>";print_r($post_data);	
	$defaultContents = get_report_contents($url,$post_data,$delimiter);
	////////////////////////////////////////////////////////////////////////////////////
	$fields = array("ocrOptions"=>json_encode( $rapaOptions ));	
	$url_data = http_build_query($fields);		
	$boundary = uniqid();
	$delimiter = '-------------' . $boundary;	
	$post_data = build_data_files($boundary, $fields, $files);
	//print "<pre>";print_r($post_data);	
	$rapaContents = get_report_contents($url,$post_data,$delimiter);
	////////////////////////////////////////////////////////////////////////////////////////
	$fields = array("ocrOptions"=>json_encode( $conwayOptions ));	
	$url_data = http_build_query($fields);		
	$boundary = uniqid();
	$delimiter = '-------------' . $boundary;	
	$post_data = build_data_files($boundary, $fields, $files);
	//print "<pre>";print_r($post_data);	
	$conwayContents = get_report_contents($url,$post_data,$delimiter);
	////////////////////////////////////////////////////////////////////////////////////////
	$fields = array("ocrOptions"=>json_encode( $coverMyMedOptions ));
	$url_data = http_build_query($fields);		
	$boundary = uniqid();
	$delimiter = '-------------' . $boundary;	
	$post_data = build_data_files($boundary, $fields, $files);
	//print "<pre>";print_r($post_data);	
	$coverMyMedContents = get_report_contents($url,$post_data,$delimiter);
	////////////////////////////////////////////////////////////////////////////////////////
	
	$isRAPA = 0;				
	$configName = '';
	$faxType = '';
	$faxCategory = '';
	$reportContents = $defaultContents;
	////////////////////////////////////////////////////////////////////////////////////////
	//AmericanEsotericLabs NexusLabs BaptistHealthMedical RadiologyAssociatesPA		
	$strPos = stripos($defaultContents,'AMERICAN ESOTERIC LABORATORIES');
	$strPos1 = stripos($defaultContents,'AMERICAN ESOTERIC LABORATGRIES');
	$strPos2 = stripos($defaultContents,'American Esoleric Laboratories');
	//$strPos = stripos($reportContents,'AEL');
	if($strPos !== false || $strPos1 !== false || $strPos2 !== false){
		$configName = 'AmericanEsotericLabs';
		$reportContents = $defaultContents;
		echo "<br>Matched:AmericanEsotericLabs, break. <br>";
		
	}
	$strPos1 = $strPos2 ='';
	
	
	// input misspelled word
	$input = 'CONWAY REGIONAL CLINICAL LABORATORIES';
	
	
	$strPos1 = stripos($conwayContents,'CONWAY REGIONAL CLINICAL LABORATORIES');
	$strPos2 = stripos($conwayContents,'CONWAY REGIONAL CLINICAL');
	$strPos3 = stripos($conwayContents,'CONWAY REGIONAL');
	$strPos4 = stripos($conwayContents,'CONWAY REGIONAL HEALTH SYSTEM');//MR Smart Route => Medical Record
	if($strPos1 !== false || $strPos2 !== false || $strPos3 !== false){
		$configName = 'NexusLabs';
		$reportContents = $conwayContents;
		//echo "<br>Matched:NexusLabs, break. <br>";	
		
	}
	
	$strPos1 = stripos($conwayContents,'DARDANELLE REGIONAL CLINICAL LABORATORIES');
	$strPos2 = stripos($conwayContents,'DARDANELLE REGIONAL CLINICAL');
	$strPos3 = stripos($conwayContents,'DAEDANELLE REGIONAL CLINICAL');
	$strPos4 = stripos($conwayContents,'DARDANELLE REGIONAL');
	if($strPos1 !== false || $strPos2 !== false || $strPos3 !== false || $strPos4 !== false){
		$configName = 'DardanelleLabs';
		$reportContents = $conwayContents;
		//echo "<br>Matched:NexusLabs, break. <br>";	
		
	}
	
	//Catapult Health
	$strPos1 = stripos($conwayContents,'Catapult Health');	
	if($strPos1 !== false){
		$configName = 'catapultHealth';
		$reportContents = $conwayContents;
		//echo "<br>Matched:NexusLabs, break. <br>";	
		
	}
	
	$strPos1 = stripos($defaultContents,'CONWAY REGIONAL HEALTH SYSTEM');
	$strPos2 = stripos($defaultContents,'Imaging Services');	
	if($strPos1 !== false && $strPos2 !== false){
		$configName = 'conwayRad';
		$reportContents = $defaultContents;
		//echo "<br>Matched:conwayRad, break. <br>";		
	}
	
	$strPos1 = stripos($rapaContents,'Radiology Associates');
	$strPos2 = stripos($rapaContents,'RADIOLOGY REPORT');
	
	//if(($strPos1 !== false || $strPos2 !== false) && $ocrLang == "rapa" && $ipf=="none")
	if($strPos1 !== false || $strPos2 !== false)
	{
		$configName = 'RadiologyAssociatesPA';
		$reportContents = $rapaContents;
		echo "<br>Matched:RadiologyAssociatesPA, break. <br>";
		
	}
	
	$strPos = stripos($defaultContents,'Quest Diagnostics');
	if($strPos !== false){
		$configName = 'questDiagnostics';
		$reportContents = $defaultContents;
		echo "<br>Matched:questDiagnostics, break. <br>";
		
	}
	$strPos1 = stripos($defaultContents,'Quest Diagnostics');//PERFORMING SITE:
	$strPos2 = stripos($defaultContents,'Patient Information');
	$strPos3 = stripos($defaultContents,'Specimen Information');
	$strPos4 = stripos($defaultContents,'Client Information');
	if($strPos1 !== false && $strPos2 !== false && $strPos3 !== false && $strPos4 !== false){
		$configName = 'questDiagnostics';
		$reportContents = $defaultContents;
		echo "<br>Matched:questDiagnostics, break. <br>";
	}
	
	
	$strPos = stripos($defaultContents,'Walgreens');
	if($strPos !== false){
		$configName = 'walgreensPharmacy';
		$reportContents = $defaultContents;
		echo "<br>Matched:walgreensPharmacy, break. <br>";
		
	}
	
	
	$strPos1 = stripos($defaultContents,'cvs/pharmacy');
	$strPos2 = stripos($defaultContents,'CVS PHARMACY');
	$strPos3 = stripos($defaultContents,'CVS Caremark');
	if($strPos1 !== false || $strPos2 !== false || $strPos3 !== false){
		$configName = 'cvsPharmacy';
		$reportContents = $defaultContents;
		echo "<br>Matched:cvsPharmacy, break. <br>";
		
	}
	
	//Wal-Mart Pharmacy
	$strPos = stripos($defaultContents,'Walmart Pharmacy');
	if($strPos !== false || stripos($defaultContents,'Wal-Mart Pharmacy') !== false){
		$configName = 'walmartPharmacy';
		$reportContents = $defaultContents;
		echo "<br>Matched:walmartPharmacy, break. <br>";
		
	}
	
	
	$strPos = stripos($defaultContents,'THE PHARMACY AT WELLINGTON');
	if($strPos !== false){
		$configName = 'wellingtonPharmacy';
		$reportContents = $defaultContents;
		echo "<br>Matched:wellingtonPharmacy, break. <br>";
		
	}
	
	$strPos = stripos($defaultContents,'THE PHARMACY AT WELLINGTON');
	if($strPos !== false){
		$configName = 'wellingtonPharmacy';
		$reportContents = $defaultContents;
		echo "<br>Matched:wellingtonPharmacy, break. <br>";
		
	}
	
	$strPos = stripos($defaultContents,'DAILY DOSE DRUGSTORE');
	if($strPos !== false){
		$configName = 'dailyDoseDrugStore';
		$reportContents = $defaultContents;
		echo "<br>Matched:dailyDoseDrugStore, break. <br>";
		
	}
	
	$strPos = stripos($defaultContents,'RISON PHARMACY');
	if($strPos !== false){
		$configName = 'risonPharmacy';
		$reportContents = $defaultContents;
		echo "<br>Matched:risonPharmacy, break. <br>";
		
	}
	
	$strPos = stripos($defaultContents,'WATSON PHARMACY');
	if($strPos !== false){
		$configName = 'watsonPharmacy';
		$reportContents = $defaultContents;
		echo "<br>Matched:watsonPharmacy, break. <br>";
		
	}
	//CORNERSTONE PHARMACY
	$strPos = stripos($defaultContents,'CORNERSTONE PHARMACY');
	if($strPos !== false || stripos($defaultContents,'CORNERSTONE') !== false ){
		$configName = 'cornerstonePharmacy';
		$reportContents = $defaultContents;
		echo "<br>Matched:cornerstonePharmacy, break. <br>";
		
	}
	
	//KROGER PHARMACY
	$strPos1 = stripos($defaultContents,'KROGER PHARMACY');
	$strPos2 = stripos($defaultContents,'KRQGER PHARMACY');
	if($strPos1 !== false || $strPos2 !== false){
		$configName = 'krogerPharmacy';
		$reportContents = $defaultContents;
		echo "<br>Matched:krogerPharmacy, break. <br>";
		
	}
	
	
	$strPos = stripos($defaultContents,'University of Arkansas for Medical Sciences');
	if($strPos !== false){
		$configName = 'uamsHospital';
		$reportContents = $defaultContents;
		echo "<br>Matched:uamsHospital, break. <br>";
		
	}
	//$strPos = stripos($defaultContents,'Express Rx on Cantrell');
	if(stripos($defaultContents,'Express Rx') !== false || stripos($defaultContents,'Express Scripts')!== false){
		$configName = 'expressRx';
		$reportContents = $defaultContents;
		echo "<br>Matched:expressRx, break. <br>";
		
	}
	
	if(stripos($defaultContents,'Remedy Drug') !== false){
		$configName = 'remedyDrug';
		$reportContents = $defaultContents;
		echo "<br>Matched:remedyDrug, break. <br>";
		
	}
	
	if(stripos($defaultContents,'Drug Emporium') !== false){
		$configName = 'drugEmporium';
		$reportContents = $defaultContents;
		echo "<br>Matched:drugEmporium, break. <br>";
		
	}
	if(stripos($defaultContents,'Smith Family Pharmacy') !== false){
		$configName = 'smithPharmacy';
		$reportContents = $defaultContents;
		echo "<br>Matched:smithPharmacy, break. <br>";
		
	}
	if(stripos($defaultContents,'Smith Drug and Compounding') !== false){
		$configName = 'smithDrug';
		$reportContents = $defaultContents;
		echo "<br>Matched:smithDrug, break. <br>";
		
	}
	
	//THE PRESCRIPTION PAD PHARMACY
	if(stripos($defaultContents,'THE PRESCRIPTION PAD PHARMACY') !== false){
		$configName = 'prescPadPharmacy';
		$reportContents = $defaultContents;
		echo "<br>Matched:prescPadPharmacy, break. <br>";
		
	}
	
	//BLANDFORD PHARMACY
	if(stripos($defaultContents,'BLANDFORD PHARMACY') !== false){
		$configName = 'blandfordPharmacy';
		$reportContents = $defaultContents;
		echo "<br>Matched:blandfordPharmacy, break. <br>";
		
	}
	
	//SUPER 1 PHARMACY
	if(stripos($defaultContents,'SUPER 1 PHARMACY') !== false){
		$configName = 'super1Pharmacy';
		$reportContents = $defaultContents;
		echo "<br>Matched:super1Pharmacy, break. <br>";
		
	}
	//Eagle Pharmacy
	if(stripos($defaultContents,'Eagle Pharmacy') !== false){
		$configName = 'eaglePharmacy';
		$reportContents = $defaultContents;
		echo "<br>Matched:eaglePharmacy, break. <br>";
		
	}
	//Envolve Pharmacy
	if(stripos($defaultContents,'Envolve Pharmacy') !== false){
		$configName = 'envolvePharmacy';
		$reportContents = $defaultContents;
		echo "<br>Matched:envolvePharmacy, break. <br>";
		
	}
	//CARTI Hematology/Oncology
	//if(stripos($defaultContents,'CARTI') !== false || stripos($defaultContents,'CARTI Hematology/Oncology') !== false){//creating issues with CARTI only word
	if(stripos($defaultContents,'CARTI Hematology/Oncology') !== false){
		$configName = 'cartiCenter';
		$reportContents = $defaultContents;
		echo "<br>Matched:cartiCenter, break. <br>";
		
	}
	//medicineManPharmacy
	if(stripos($defaultContents,'Medicine Man Pharmacy') !== false){
		$configName = 'medicineManPharmacy';
		$reportContents = $defaultContents;
		echo "<br>Matched:medicineManPharmacy, break. <br>";
		
	}
	//RHEA DRUG
	if(stripos($defaultContents,'RHEA DRUG') !== false){
		$configName = 'rheaDrug';
		$reportContents = $defaultContents;
		echo "<br>Matched:rheaDrug, break. <br>";
		
	}
	//freidericaPharmacy
	if(stripos($defaultContents,'Freiderica Pharmacy') !== false){
		$configName = 'freidericaPharmacy';
		$reportContents = $defaultContents;
		echo "<br>Matched:freidericaPharmacy, break. <br>";
		
	}
	
	//THE	DRUG	STORE,	INC
	if(stripos($defaultContents,'THE	DRUG	STORE') !== false || stripos($defaultContents,'THE DRUG STORE') !== false){
		$configName = 'theDrugStore';
		$reportContents = $defaultContents;
		echo "<br>Matched:theDrugStore, break. <br>";
		
	}
	//donsPharmacy
	if(stripos($defaultContents,"DON'S PHARMACY") !== false){
		$configName = 'donsPharmacy';
		$reportContents = $defaultContents;
		echo "<br>Matched:donsPharmacy, break. <br>";
		
	}

	
	//Burrows Pharmacy - Similar to wellington
	if(stripos($defaultContents,"BURROW'S DRUG STORE") !== false){
		$configName = 'burrowsPharmacy';
		$reportContents = $defaultContents;
		echo "<br>Matched:burrowsPharmacy, break. <br>";
		
	}
	
	//eastEnd Pharmacy - Similar to wellington
	if(stripos($defaultContents,"EAST END PHARMACY") !== false){
		$configName = 'eastEndPharmacy';
		$reportContents = $defaultContents;
		echo "<br>Matched:eastEndPharmacy, break. <br>";
		
	}
	
	
	//ProScan Radiology Arkansas
	if(stripos($defaultContents,"ProScan Radiology Arkansas") !== false){
		$configName = 'proScanRadiologyAR';
		$reportContents = $defaultContents;
		echo "<br>Matched:proScanRadiologyAR, break. <br>";
		
	}
	//The surgical Clinic
	if(stripos($defaultContents,"THE SURGICAL CLINIC OF CENTRAL ARKANSAS") !== false){
		$configName = 'surgicalClinicAR';
		$reportContents = $defaultContents;
		echo "<br>Matched:surgicalClinicAR, break. <br>";
		
	}
	
	//ARKANSAS HEART HOSPITAL
	if(stripos($defaultContents,"ARKANSAS HEART HOSPITAL") !== false || stripos($defaultContents,'AHHC MAIN')!== false){
		$configName = 'ahhcMain';
		$reportContents = $defaultContents;
		echo "<br>Matched:ahhcMain, break. <br>";
		
	}
	
	//CHI St. Vincent Heart Clinic (Ng Heart Clinic, CHI SL Vingent,SE Vincent)
	if(stripos($defaultContents,"Heart Clinic") !== false && (stripos($defaultContents,"CHI St") !== false || stripos($defaultContents,"CHI SL Vingent") !== false || stripos($defaultContents,"SE Vincent") !== false)){
		$configName = 'stVincentHeartClinicAR';
		$reportContents = $defaultContents;
		echo "<br>Matched:stVincentHeartClinicAR, break. <br>";
		
	}
	
	//ARKANSAS OTOLARYNGOLOGY CENTER
	if(stripos($defaultContents,"ARKANSAS OTOLARYNGOLOGY CENTER") !== false){
		$configName = 'aocCenter';
		$reportContents = $defaultContents;
		echo "<br>Matched:aocCenter, break. <br>";
		
	}
	
	//OrthoArkansas 
	if(stripos($defaultContents,"OrthoArkansas") !== false){
		$configName = 'orthoArkansasPA';
		$reportContents = $defaultContents;
		echo "<br>Matched:orthoArkansasPA, break. <br>";
		
	}
	
	//GastroArkansas 
	if(stripos($defaultContents,"GI Alliance") !== false && stripos($defaultContents,"Gastroenterology Associates") !== false){
		$configName = 'gastroArkansas';
		$reportContents = $defaultContents;
		echo "<br>Matched:gastroArkansas, break. <br>";
		
	}
	
	//Arkansas Pathology Assoc - CoPathPlus	
	$strPos1 = stripos($defaultContents,'Arkansas Pathology Assoc');
	$strPos2 = stripos($defaultContents,'SURGICAL PATHOLOGY REPORT');
	$strPos3 = stripos($defaultContents,'MOLECULAR PATHOLOGY REPORT');
	if($strPos1 !== false && ($strPos2 !== false || $strPos3 !== false)){
		$configName = 'pathologyAssocAR';
		$reportContents = $defaultContents;
		echo "<br>Matched:pathologyAssocAR, break. <br>";
		
	}
	
	//OPTUMRX
	if(stripos($defaultContents,"OPTUMRX") !== false){
		$configName = 'optumRx';
		$reportContents = $defaultContents;
		echo "<br>Matched:optumRx, break. <br>";
		
	}
	
	//Retina Associates
	if(stripos($defaultContents,"Retina Associates") !== false){
		$configName = 'retinaAssociatesPA';
		$reportContents = $defaultContents;
		echo "<br>Matched:retinaAssociatesPA, break. <br>";
		
	}
	
	$strPos = stripos($defaultContents,'BAPTIST');
	if($strPos !== false){
		$configName = 'BaptistHealthMedical';
		$reportContents = $defaultContents;
		//echo "<br>Matched:BaptistHealthMedical, break. <br>";
		
	}
	
	//Sherwood Urgent Care
	if(stripos($defaultContents,"Sherwood Urgent Care") !== false){
		$configName = 'sherwoodUrgentCare';
		$reportContents = $defaultContents;
		echo "<br>Matched:sherwoodUrgentCare, break. <br>";
		
	}
	
	//PREMIER SURGERY
	if(stripos($defaultContents,"PREMIER SURGERY CENTER") !== false){
		$configName = 'premierSurgeryCenter';
		$reportContents = $defaultContents;
		echo "<br>Matched:premierSurgeryCenter, break. <br>";
		
	}
	
	//eRxNetwork
	if(stripos($defaultContents,"Message ID:") !== false || stripos($defaultContents,'eRx ID:')!== false || stripos($defaultContents,'eRx Network')!== false){
		$configName = 'eRxNetwork';
		$reportContents = $defaultContents;
		echo "<br>Matched:eRxNetwork, break. <br>";
		
	}
	
	
	//Prior Authorization Assistance by CoverMyMeds
	$strPos = stripos($coverMyMedContents,'Prior Authorization Assistance by');
	$strPos1 = stripos($coverMyMedContents,'CoverMyMaeds');
	$strPos2 = stripos($coverMyMedContents,'CoverMyMeds');//Dear Prior Authorization staff
	$strPos3 = stripos($coverMyMedContents,'key.covermymeds.com');//key.covermymeds.com
	if($strPos !== false && ($strPos1 !== false || $strPos2 !== false || $strPos3 !== false)){
		$configName = 'CoverMyMedsPA';
		$reportContents = $coverMyMedContents;
		echo "<br>Matched:CoverMyMedsPA, break. <br>";
		
	}
	
	
	
	///////////////////////////////////////////////////////////////////////////////////////////
	
	//echo nl2br($reportContents);
	$reportData = "";
	if(!empty($reportContents)){ //Sometimes OCR timout & return error without any contents
		upload_fax_pdf($end_point_url, $email_addr, $file);
		switch($configName){
				case "AmericanEsotericLabs":
					$reportData = aelReportHandler(basename($file),$reportContents,'AmericanEsotericLabs',$email_addr);
				break;
				
				case "NexusLabs":
					$reportData = conwayReportHandler(basename($file),$reportContents,'NexusLabs',$email_addr);
				break;
				
				case "DardanelleLabs":
					$reportData = conwayReportHandler(basename($file),$reportContents,'DardanelleLabs',$email_addr);
				break;
				
				case "catapultHealth":
					$reportData = catapultHealthReportHandler(basename($file),$reportContents,'catapultHealth',$email_addr);
				break;
				
				case "conwayRad":
					$reportData = conwayRadHandler(basename($file),$reportContents,'conwayRad',$email_addr);
				break;
				
				case "ahhcMain":
					$reportData = ahhcMainHandler(basename($file),$reportContents,'ahhcMain',$email_addr);
				break;
				
				case "BaptistHealthMedical":
					$reportData = baptistReportHandler(basename($file),$reportContents,'BaptistHealthMedical',$email_addr);
				break;
				
				case "premierSurgeryCenter":
					$reportData = premierSurgeryCenterHandler(basename($file),$reportContents,'premierSurgeryCenter',$email_addr);
				break;
				
				case "sherwoodUrgentCare":
					$reportData = sherwoodUrgentCareHandler(basename($file),$reportContents,'sherwoodUrgentCare',$email_addr);
				break;
				
				
				case "RadiologyAssociatesPA":
					$reportData = rapaReportHandler(basename($file),$reportContents,'RadiologyAssociatesPA',$email_addr);
				break;
				
				case "questDiagnostics":
					$reportData = questDiagnosticsHandler(basename($file),$reportContents,'questDiagnostics',$email_addr);
				break;
				
				case "burrowsPharmacy":
					$reportData = burrowsPharmacyHandler(basename($file),$reportContents,'burrowsPharmacy',$email_addr);
				break;
				
				case "eastEndPharmacy":
					$reportData = eastEndPharmacyHandler(basename($file),$reportContents,'eastEndPharmacy',$email_addr);
				break;
				
				
				case "walgreensPharmacy":
					$reportData = walgreensPharmacyHandler(basename($file),$reportContents,'walgreensPharmacy',$email_addr);
				break;
				
				case "cvsPharmacy":
					$reportData = cvsPharmacyHandler(basename($file),$reportContents,'cvsPharmacy',$email_addr);
				break;
				
				case "walmartPharmacy":
					$reportData = walmartPharmacyHandler(basename($file),$reportContents,'walmartPharmacy',$email_addr);
				break;
				
				case "wellingtonPharmacy":
					$reportData = wellingtonPharmacyHandler(basename($file),$reportContents,'wellingtonPharmacy',$email_addr);
				break;
				
				case "dailyDoseDrugStore":
					$reportData = dailyDoseDrugStoreHandler(basename($file),$reportContents,'dailyDoseDrugStore',$email_addr);
				break;
				
				case "risonPharmacy":
					$reportData = risonPharmacyHandler(basename($file),$reportContents,'risonPharmacy',$email_addr);
				break;
				
				case "cornerstonePharmacy":
					$reportData = cornerstonePharmacyHandler(basename($file),$reportContents,'cornerstonePharmacy',$email_addr);
				break;
				
				case "krogerPharmacy":
					$reportData = krogerPharmacyHandler(basename($file),$reportContents,'krogerPharmacy',$email_addr);
				break;
				
				case "CoverMyMedsPA":
					$reportData = CoverMyMedsPAHandler(basename($file),$reportContents,'CoverMyMedsPA',$email_addr);
				break;
				
				case "uamsHospital":
					$reportData = uamsHandler(basename($file),$reportContents,'uamsHospital',$email_addr);
				break;
				
				case "expressRx":
					$reportData = expressRxHandler(basename($file),$reportContents,'expressRx',$email_addr);
				break;
				
				case "eRxNetwork":
					$reportData = eRxNetworkHandler(basename($file),$reportContents,'eRxNetwork',$email_addr);
				break;
				
				case "remedyDrug":
					$reportData = remedyDrugHandler(basename($file),$reportContents,'remedyDrug',$email_addr);
				break;
				
				case "drugEmporium":
					$reportData = drugEmporiumHandler(basename($file),$reportContents,'drugEmporium',$email_addr);
				break;
				
				case "prescPadPharmacy":
					$reportData = prescPadPharmacyHandler(basename($file),$reportContents,'prescPadPharmacy',$email_addr);
				break;
				
				case "blandfordPharmacy":
					$reportData = blandfordPharmacyHandler(basename($file),$reportContents,'blandfordPharmacy',$email_addr);
				break;
				
				case "smithPharmacy":
					$reportData = smithPharmacyHandler(basename($file),$reportContents,'smithPharmacy',$email_addr);
				break;
				
				case "smithDrug":
					$reportData = smithPharmacyHandler(basename($file),$reportContents,'smithDrug',$email_addr);
				break;
				
				case "watsonPharmacy":
					$reportData = watsonPharmacyHandler(basename($file),$reportContents,'watsonPharmacy',$email_addr);
				break;
				
				case "super1Pharmacy":
					$reportData = super1PharmacyHandler(basename($file),$reportContents,'super1Pharmacy',$email_addr);
				break;
				
				case "eaglePharmacy":
					$reportData = eaglePharmacyHandler(basename($file),$reportContents,'eaglePharmacy',$email_addr);
				break;
				
				case "envolvePharmacy":
					$reportData = envolvePharmacyHandler(basename($file),$reportContents,'envolvePharmacy',$email_addr);
				break;
				
				case "cartiCenter":
					$reportData = cartiCenterHandler(basename($file),$reportContents,'cartiCenter',$email_addr);
				break;
				
				case "medicineManPharmacy":
					$reportData = medicineManPharmacyHandler(basename($file),$reportContents,'medicineManPharmacy',$email_addr);
				break;
				
				case "rheaDrug":
					$reportData = rheaDrugHandler(basename($file),$reportContents,'rheaDrug',$email_addr);
				break;
				
				case "freidericaPharmacy":
					$reportData = freidericaPharmacyHandler(basename($file),$reportContents,'freidericaPharmacy',$email_addr);
				break;
				
				case "theDrugStore":
					$reportData = theDrugStoreHandler(basename($file),$reportContents,'theDrugStore',$email_addr);
				break;
				
				case "donsPharmacy":
					$reportData = donsPharmacyHandler(basename($file),$reportContents,'donsPharmacy',$email_addr);
				break;
				
				case "surgicalClinicAR":
					$reportData = surgicalClinicARHandler(basename($file),$reportContents,'surgicalClinicAR',$email_addr);
				break;
				
				case "stVincentHeartClinicAR":
					$reportData = stVincentHeartClinicARHandler(basename($file),$reportContents,'stVincentHeartClinicAR',$email_addr);
				break;
				
				case "aocCenter":
					$reportData = aocCenterHandler(basename($file),$reportContents,'aocCenter',$email_addr);
				break;
				
				case "pathologyAssocAR":
					$reportData = pathologyAssocARHandler(basename($file),$reportContents,'pathologyAssocAR',$email_addr);
				break;
				
				case "orthoArkansasPA":
					$reportData = orthoArkansasPAHandler(basename($file),$reportContents,'orthoArkansasPA',$email_addr);
				break;
				
				case "gastroArkansas":
					$reportData = gastroArkansasHandler(basename($file),$reportContents,'gastroArkansas',$email_addr);
				break;
				
				case "optumRx":
					$reportData = optumRxHandler(basename($file),$reportContents,'optumRx',$email_addr);
				break;
				
				
				
				
				
				default:
					$reportData = unknownReportHandler(basename($file),$reportContents,'unknown',$email_addr);
				break;
		}
		
		if(!empty($reportData)){
			$json_string = json_encode($reportData);
			$ch = curl_init();
						
			curl_setopt($ch, CURLOPT_URL, $end_point_url); //local
			
			//curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $json_string);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
			
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			//curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); 
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			
			$result = curl_exec($ch);
			curl_close($ch);
			echo "<pre>"; print_r($result); echo "</pre>";
		}
		else{
			echo "Not recognized by script.<br>";	
		}
	
		if (file_exists($file)) {
			unlink($file);
			
		} 
		else {
			// File not found.
		}
	}
	else{
		echo "Contents Not available.";	
	}
	/*print "<pre>";
	print_r($reportData);
	print "</pre>";*/
	
	unset($afiles[$i]);
	if (count($afiles) > 0) {
		//echo "allfiles <pre>"; print_r($afiles);
		$i++;
		processFaxFiles($afiles, $i, $ocrLabs);
	}
}





function save_data($reportData = array()){
	if(!empty($reportData)){
		$json_string = json_encode($reportData);
		
		$ch = curl_init();
		
		$end_point_url = 'https://lrwic.com/beta/api/Pdf_decoder';
		
	
		curl_setopt($ch, CURLOPT_URL, $end_point_url); //local
		
		//curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    
		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
				
		$result = curl_exec($ch);
		curl_close($ch);
		echo "<pre>"; print_r($result); echo "</pre>";
	
	}
}

function upload_fax_pdf($end_point_url, $email_addr, $file)
{
	$file_name_with_full_path = realpath("$file");
	
	$target_url=$end_point_url."/upload_pdf_report";// live server
	
	$original_file_name = pathinfo(basename($file), PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	
	if(!empty($tempArr) && count($tempArr) > 2){
		
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		if(isset($tempArr[2]) && is_numeric($tempArr[2])){
			$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		}
		
		if(isset($tempArr[3]) && is_numeric($tempArr[3])){
			$fax_data_id = isset($tempArr[3]) ? $tempArr[3] : 0;
		}
	}
	
	$post = array('file_contents' => '@' . $file_name_with_full_path);
	//the curl_setopt docs say that using '@' in postfields is deprecated in PHP 5.0
	$curl_file_upload = new CURLFile($file_name_with_full_path);
	$post = array("file_contents" => $curl_file_upload, 'email_addr' => $email_addr,'fax_date_time'=>$fax_date_time,'fax_data_id'=>$fax_data_id);
	$header = array('Content-Type: multipart/form-data');
	$ch = curl_init();
	//curl_setopt($ch, CURLOPT_VERBOSE,1); 
	curl_setopt($ch, CURLOPT_URL, $target_url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_POST, 1);
	//curl_setopt($ch, CURLOPT_POSTFIELDS, $file_to_upload); 
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	//curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
	//curl_setopt($ch, CURLOPT_FILE, $fh);
	//ssl certificate problem unable to get local issuer certificate
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	$result = curl_exec($ch);
	$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$curl_errno = curl_errno($ch);
	if (curl_errno($ch)) {
		echo 'Request Error:' . curl_error($ch); //ssl certificate problem unable to get local issuer certificate
	}
	curl_close($ch);
	print_r($result);
}

function get_report_contents($url, $post_data, $delimiter)
{
	$curl = curl_init();
	//header("Content-Transfer-Encoding: binary");
	curl_setopt_array($curl, array(
								  CURLOPT_URL => $url,
								  CURLOPT_RETURNTRANSFER => 1,
								  CURLOPT_MAXREDIRS => 100,
								  CURLOPT_CONNECTTIMEOUT => 0, 
								  CURLOPT_TIMEOUT => 500,
								  
								  CURLOPT_CUSTOMREQUEST => "POST",
								  CURLOPT_POST => 1,
								  CURLOPT_POSTFIELDS => $post_data,
								  CURLOPT_HTTPHEADER => array(
									
									"Content-Transfer-Encoding: binary",
									"Content-Type: multipart/form-data; boundary=" . $delimiter,
									"Content-Length: " . strlen($post_data)
								
								  ),

  
	));

	//
	$response = curl_exec($curl);
	
	$info = curl_getinfo($curl);
	
	
	curl_close($curl);
	
	$result = json_decode($response);	
	
	$reportContents = "";
	if(isset($result->text)){
		$reportContents = $result->text;
	}
	
	
	return $reportContents;
}

function build_data_files($boundary, $fields, $files){
    $data = '';
    $eol = "\r\n";

    $delimiter = '-------------' . $boundary;

    foreach ($fields as $name => $content) {
        $data .= "--" . $delimiter . $eol
            . 'Content-Disposition: form-data; name="' . $name . "\"".$eol.$eol
            . $content . $eol;
    }


    foreach ($files as $name => $content) {
       
		$data .= "--" . $delimiter . $eol
            . 'Content-Disposition: form-data; name="file"; filename="' . $name . '"' . $eol
            //. 'Content-Type: image/png'.$eol
            . 'Content-Transfer-Encoding: binary'.$eol
            ;
			
        $data .= $eol;
        $data .= $content . $eol;
    }
    $data .= "--" . $delimiter . "--".$eol;


    return $data;
}

function configName(){
	
}

function unknownReportHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;
	//$responseArray['tsv_name'] = $tsv_name;
	//$responseArray['email_addr'] = $email_addr;
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	
	if(!empty($tempArr) && count($tempArr) > 2){
		
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		if(isset($tempArr[2]) && is_numeric($tempArr[2])){
			$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		}
		
		if(isset($tempArr[3]) && is_numeric($tempArr[3])){
			$fax_data_id = isset($tempArr[3]) ? $tempArr[3] : 0;
		}
	}
	
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	//$responseArray['text_contents'] = $reportContents;
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$responseArray['fax_category'] = 'unknown';
	$responseArray['fax_type'] = 'unknown';//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
	$line_arr = array();
	
	return $responseArray;
}

function aelReportHandler($fileName='',$reportContents='', $configurationName = '',$email_addr=''){
	//It has two different formats
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	$testName = '';
	$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	$tempArr = array('key' => 'TYPE:', 'value' => 'Lab Results');
	array_push($responseArray['rpt_header'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;
	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	
	if(!empty($tempArr) && count($tempArr) > 2){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		if(isset($tempArr[2]) && is_numeric($tempArr[2])){
			$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		}
		if(isset($tempArr[3]) && is_numeric($tempArr[3])){
			$fax_data_id = isset($tempArr[3]) ? $tempArr[3] : 0;
		}
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$responseArray['fax_category'] = 'Results';
	$responseArray['fax_type'] = 'Lab Results';//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	//$responseArray['text_contents'] = $reportContents;
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	
	if(!empty($reportContents)){
		$rpt_lines = explode("\n",$reportContents);
		
		if(!empty($rpt_lines)){
			if(stripos($reportContents,'AEL MEMPHIS') !== false && stripos($reportContents,'Lab No:') !== false){
				$reportStart = 1;
				$headerStart = 1;
				$responseArray['file_version'] = 'new';
				$headerContents = $reportContents;
				$bodyContents 	= $reportContents; 
				if(stripos($reportContents,'Units') !== false){
					$posStart = stripos($reportContents,'Units');
					$headerContents = substr($reportContents,0,$posStart);
					
				}
				////////////////////////////////////////////////////////////////////
				$rpt_lines = explode("\n",$headerContents);
				
				foreach($rpt_lines as $key => $line){
					
					$elements = explode("\t",$line);
					
					
					
					if(stripos($line,'Provider:') !== false && stripos($line,'Collected:') !== false){
						
						///////////////////////
						$field_name = 'Patient:';
						//$field_val = trim(str_ireplace('Patient:','',$item));
						$lastName = (isset($elements[0]) ? $elements[0] : '');
						$tempArr = array('key' => 'last_name', 'value' => $lastName);
						array_push($responseArray['rpt_header'], $tempArr);
							
						$nextKey = $key+1;
						$nextLine = '';
						if(array_key_exists($nextKey,$rpt_lines)){
							$nextLine = trim($rpt_lines[$nextKey]);
						}
						$nextLine = explode("\t",$nextLine);
						$firstName = (isset($nextLine[0]) ? $nextLine[0] : '');
						$tempArr = array('key' => 'first_name', 'value' => $firstName);
						//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
						
						$field_val = $lastName.', '.$firstName; 
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
						
					}					
					else if(stripos($line,'DOB:') !== false){
						$field_name = 'dob';						
						$field_val = (isset($elements[0]) ? $elements[0] : '');
						$field_val = trim(str_ireplace('DOB:','',$field_val));
						if(stripos($field_val,'(') !== false){
							$agePos = stripos($field_val,'(');
							$field_val = trim(substr($field_val,0,($agePos-1)));
						}
						$tempArr = array('key' => 'dob', 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						$dob = $field_val;
						
						
						////////////////////////////////////////////////////////////////
						$field_name = 'DOB/Sex:';
						$nextKey = $key+1;
						$nextLine = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$nextLine = trim($rpt_lines[$nextKey]);
						}
						
						
						$nextLine = explode("\t",$nextLine);
						$sex = (isset($nextLine[0]) ? $nextLine[0] : '');
						if($sex =='Female'){
							$dob = $dob.'/F';
						}
						else{
							$dob = $dob.'/M';
						}
						$tempArr = array('key' => $field_name, 'value' => $dob);
						array_push($responseArray['rpt_header'], $tempArr);
						/////////////////////////////////////////////////////							
											
					}
					
				}
				
			}
			else
			{
				$reportStart = 1;
				$headerStart = 1;
				$responseArray['file_version'] = 'old';
				$headerContents = $reportContents;
				$bodyContents 	= $reportContents;
				$testRequested = '';
				if(stripos($reportContents,'Test Requested') !== false || stripos($reportContents,'Test Requeeted') !== false){
					$pos = stripos($reportContents,'Test Requested');
					$headerContents = substr($reportContents,0,$pos);
					
				}
				/////////////////////////////////////////////////////
				if(stripos($headerContents,'Patient:') !== false){
					$pos = stripos($headerContents,'Patient:');
					$headerContents = substr($headerContents,$pos);
				}
				
				$rpt_lines = explode("\n",$headerContents);
				foreach($rpt_lines as $key => $line){
					
					$elements = explode("\t",$line);
					
					
					if(stripos($line,'Patient:') !== false){
						$field_name = 'Patient:';
						//$field_val = trim(str_ireplace('Patient:','',$item));
						$field_val = (isset($elements[1]) ? trim($elements[1]) : '');
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(',',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$lastName = isset($pat_name[0]) ? trim($pat_name[0]) : '';
							$firstName = isset($pat_name[1]) ? trim($pat_name[1]) : ''; 							
							if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
								$firstNameArr = explode(' ',$firstName);
								$firstName = $firstNameArr[0];
							}
							$tempArr = array('key' => 'first_name', 'value' => $firstName);
							//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$tempArr = array('key' => 'last_name', 'value' => $lastName);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						
					}
					
					if(stripos($line,'DOB/Sex:') !== false){
						$field_name = 'DOB/Sex:';
						$field_val = trim(str_ireplace('DOB/Sex','',$line)); 
						$field_val = trim(str_ireplace(':','',$field_val));	
						$elements = explode("\t",$field_val);					
						$field_val = (isset($elements[0]) ? trim($elements[0]) : '');
						$field_val = trim(str_ireplace('@','0',$field_val));
						//Remove any character that isn't A-Z, a-z, 0-9 or a dot.
						//$field_val = preg_replace("/[^A-Za-z0-9.]/", '', $field_val);
						$field_val = preg_replace("/[^A-Za-z0-9-\/]/", '', $field_val);
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						//rtrim($arraynama, ", ");
						$field_val = substr($field_val,0,-2);
						$tempArr = array('key' => 'dob', 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}
						
						
				}
				
			}
		}
		
	}//end if reportContents
	return $responseArray;
}

function conwayReportHandler($fileName='',$reportContents='', $configurationName = '',$email_addr=''){	
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$format2 = 0;
	if(!empty($reportContents)){
		
		$rpt_lines = explode("\n",$reportContents);
		
		if(stripos($reportContents,'DISCHARGE SUMMARY') !== false){
			$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
			array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
			
			$responseArray['configurationName'] = $configurationName;
			$responseArray['pdf_name'] = $fileName;
			$responseArray['email_addr'] = $email_addr;
			
			$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
			$tempArr = explode('_',$original_file_name);
			
			$fax_date_time = date('Y-m-d H:i:s');
			$fax_data_id = 0;
			if(!empty($tempArr)){
				//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
				$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
				$fax_data_id = $tempArr[3];
			}
			$responseArray['fax_date_time'] = $fax_date_time;
			$responseArray['fax_data_id'] = $fax_data_id;
			array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
			
			$faxCategory = 'Hospital'; //MR => Medical Record
			$faxType = 'Discharge Summary';
			$faxTypeMain = 'Discharge Summary';
				
			///////////////////////////////////////////
			$posStart = $posEnd = 0;
			if(stripos($reportContents,'CONFIDENTIALITY NOTICE:') !== false){
				$posStart = stripos($reportContents,'CONFIDENTIALITY NOTICE:');
			}
			if(stripos($reportContents,'Discharge Plan') !== false){
				$posEnd = stripos($reportContents,'Discharge Plan');
			}
			if($posEnd == 0){
				$reportContents = substr($reportContents,$posStart);// To end of report
			}
			else if($posEnd > $posStart){
				$reportContents = substr($reportContents,$posStart,( $posEnd - $posStart));
			}
			
			$rpt_lines = explode("\n",$reportContents);
			foreach($rpt_lines as $key => $line)
			{
				if(stripos($line,'CONWAY REGIONAL HEALTH SYSTEM') !== false && stripos($line,'NAME:') !== false){
					
					$posName = stripos($line,'NAME:');
					$field_val = substr($line,$posName); //To end of line
					$field_val = trim(str_ireplace('NAME','',$field_val));
					$field_val = trim(str_ireplace(':','',$field_val));
					
					if(stripos($field_val,',') !== false){
						$pat_name = explode(',',$field_val);
					}
					else{
						$pat_name = explode(' ',$field_val);
					}
					if(!empty($pat_name) && count($pat_name) > 0){
						
						$lname = isset($pat_name[0]) ? trim($pat_name[0]) : '';
						$lname = trim(str_ireplace('NAME.','',$lname));
						$lname = trim(str_ireplace('NAME:','',$lname));
						$lname = trim(str_ireplace('NAME,','',$lname));
						$lname = trim(str_ireplace('NAME','',$lname));
						
						$tempArr = array('key' => 'last_name', 'value' => $lname);
						array_push($responseArray['rpt_header'], $tempArr);
						
						$fname = isset($pat_name[1]) ? trim($pat_name[1]) : '';
						if(!empty($fname)){ //Name:  HENDERSON,ROBERT W (Lastname, FirstName Mid)
							$fnameArr = explode(' ', $fname);								
							$fname = $fnameArr[0];
						}
						
						$tempArr = array('key' => 'first_name', 'value' => $fname);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}
					else{
						$tempArr = array('key' => 'first_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
						$tempArr = array('key' => 'last_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
				}
				else if(stripos($line,'DISCHARGE SUMMARY') !== false && stripos($line,'DOB:') !== false){
					$field_name = 'dob';
					$posDOB = stripos($line,'DOB:');
					$field_val = substr($line,$posDOB); //To end of line
					$field_val = trim(str_ireplace('DOB:','',$field_val));//09/17/1994
					
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);
					
				}
				
			}
			
			$responseArray['fax_category'] = $faxCategory;
			$responseArray['fax_type'] = $faxTypeMain;
			$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
			array_push($responseArray['rpt_header'], $tempArr);
			
			$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
			array_push($responseArray['rpt_header'], $tempArr);
		}
		else if(!empty($rpt_lines)){
			foreach($rpt_lines as $key => $line)
			{
				
				if($reportStart==0)
				{
					$strPos = stripos($line,'Page 2');
					if($strPos !== false){
						$reportStart = 1;
						$headerStart = 1;
					}
					
					$strPos = stripos($line,'RUN DATE');
					if($strPos !== false){
						$reportStart = 1;
						$headerStart = 1;
					}
					
					if($reportStart==0) continue;
				}
				
				
				
				$elements = explode("\t",$line);
				
				
				if($reportStart == 1){
					if(stripos($line,'Specimen:') !== false && stripos($line,'Collected:') !== false && (stripos($line,'Status:') !== false || stripos($line,'Statua:') !== false) && (stripos($line,'Req#:') !== false || stripos($line,'Reqg#:') !== false || stripos($line,'Regi:') !== false)){
						$format2 = 1;
						break;
					}
				}
				
				
			}
			
			if($format2== 1){
				$responseArray = conwayReportFormat2($fileName,$reportContents, $configurationName,$email_addr);
			}
			else{
				$responseArray = conwayReportFormat1($fileName,$reportContents, $configurationName,$email_addr);	
			}
		}
	}
	
	return $responseArray;
}

function conwayReportFormat1($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	$testName = '';
	$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	$tempArr = array('key' => 'TYPE:', 'value' => 'Lab Results');
	array_push($responseArray['rpt_header'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;
	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$responseArray['fax_category'] = 'Results';
	$responseArray['fax_type'] = 'Lab Results';//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	
	if(!empty($reportContents)){
		
		$rpt_lines = explode("\n",$reportContents);
		
		if(!empty($rpt_lines)){
			foreach($rpt_lines as $key => $line){
				
				if($reportStart==0){
					$strPos = stripos($line,'Page 2');
					if($strPos !== false){
						$reportStart = 1;
						$headerStart = 1;
					}
					
					$strPos = stripos($line,'RUN DATE');
					if($strPos !== false){
						$reportStart = 1;
						$headerStart = 1;
					}
					
					if($reportStart==0) continue;
				}
				
				$elements = explode("\t",$line);
				
				
				if($reportStart == 1){
					if(in_array('Test',$elements) && in_array('Low',$elements) && in_array('Normal',$elements) && in_array('High',$elements)){
						$headerStart = 0;

						$bodyStart = 1;
						continue;
					}
				}
				
				
				// no shortest distance found, yet
				$shortest = -1;
				foreach($elements as $key => $item){
					$input = $item;
					if($headerStart == 1){
						if(stripos($item,'Name') !== false){
							$field_name = 'Name:';
							//$field_val = trim(str_ireplace('Name:','',$item));
							$nextKey = $key+1;
							$field_val = '';
							if (array_key_exists($nextKey,$elements)){
								$field_val = trim($elements[$nextKey]);
							}
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
							/////////////////////////
							$pat_name = explode(',',$field_val);
							if(!empty($pat_name) && count($pat_name) > 0){
								$lname = isset($pat_name[0]) ? trim($pat_name[0]) : '';
								$tempArr = array('key' => 'last_name', 'value' => $lname);
								array_push($responseArray['rpt_header'], $tempArr);
								
								$fname = isset($pat_name[1]) ? trim($pat_name[1]) : '';
								if(!empty($fname)){
									$fnameArr = explode(' ',$fname);
									$fname = trim($fnameArr[0]);
								}	
								$tempArr = array('key' => 'first_name', 'value' => $fname);
								array_push($responseArray['rpt_header'], $tempArr);
							}
							else{
								$tempArr = array('key' => 'first_name', 'value' => '');
								array_push($responseArray['rpt_header'], $tempArr);
								$tempArr = array('key' => 'last_name', 'value' => '');
								array_push($responseArray['rpt_header'], $tempArr);
							}
						}					
						else if(stripos($item,'Birthdate') !== false){
							$field_name = 'Birthdate:';
							$field_val = trim(str_ireplace('Birthdate:','',$item));
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
							//////////////////////
							$field_name = 'dob';
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
						}
											
					}
				}
			}	
		}
	}
	
	return $responseArray;
}

function conwayReportFormat2($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	
	
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	$testName = '';
	$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	$tempArr = array('key' => 'TYPE:', 'value' => 'Lab Results');
	array_push($responseArray['rpt_header'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;
	//$responseArray['tsv_name'] = $tsv_name;
	//$responseArray['email_addr'] = $email_addr;
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); 
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$responseArray['fax_category'] = 'Results';
	$responseArray['fax_type'] = 'Lab Results';
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$format2 = 0;
	$specimenFlag = 0;
	$testFlag = 0;
	if(!empty($reportContents)){
		
		$rpt_lines = explode("\n",$reportContents);
		
		if(!empty($rpt_lines)){
			foreach($rpt_lines as $key => $line){
				
				if($reportStart==0){
					$strPos = stripos($line,'Page 2');
					if($strPos !== false){
						$reportStart = 1;
						$headerStart = 1;
					}
					
					$strPos = stripos($line,'RUN DATE');
					if($strPos !== false){
						$reportStart = 1;
						$headerStart = 1;
					}
					
					if($reportStart==0) continue;
				}
				
				
				
				$elements = explode("\t",$line);
				
				
				if($reportStart == 1)
				{
					
					
					if(stripos($line,'Specimen:') !== false && stripos($line,'Collected:') !== false 
						&& (stripos($line,'Status:') !== false || stripos($line,'Statua:') !== false) 
						&& (stripos($line,'Req#:') !== false || stripos($line,'Reqg#:') !== false || stripos($line,'Reg#:') !== false || stripos($line,'Regi:') !== false))
					{
						$headerStart = 0;
						$bodyStart = 1;
						
						$reqNumber = '';
						$reqPos = stripos($line,'Req#:');
						if($reqPos !== false){
							$reqNumber = substr($line,$reqPos);//to the end of line
							//$reqNumber = explode('\t',$reqNumber);
							$reqNumber = trim(str_ireplace('Req#:','',$reqNumber));	
							$field_name = 'Req#:';
							$field_val = trim($reqNumber);
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
							continue;
						}
						$reqPos = stripos($line,'Reg#:');
						if($reqPos !== false){
							$reqNumber = substr($line,$reqPos);//to the end of line
							//$reqNumber = explode('\t',$reqNumber);
							$reqNumber = trim(str_ireplace('Reg#:','',$reqNumber));	
							$field_name = 'Req#:';
							$field_val = trim($reqNumber);
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
							continue;
						}
						$reqPos = stripos($line,'Reqg#:');
						if($reqPos !== false){
							$reqNumber = substr($line,$reqPos);//to the end of line
							//$reqNumber = explode('\t',$reqNumber);
							$reqNumber = trim(str_ireplace('Reqg#:','',$reqNumber));	
							$field_name = 'Req#:';
							$field_val = trim($reqNumber);
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
							continue;
						}
						$reqPos = stripos($line,'Regi:');
						if($reqPos !== false){
							$reqNumber = substr($line,$reqPos);//to the end of line
							//$reqNumber = explode('\t',$reqNumber);
							$reqNumber = trim(str_ireplace('Regi:','',$reqNumber));	
							$field_name = 'Req#:';
							$field_val = trim($reqNumber);
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
							continue;
						}
						
					}
					
					if(stripos($line,'Ordered:') !== false || stripos($line,'Ordered :') !== false){						
						$testOrdered = trim(str_ireplace('Ordered:','',$line));	
						$field_name = 'Ordered:';
						$field_val = trim($testOrdered);
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						continue;
					}
				}
				
				foreach($elements as $key => $item){
					$input = $item;
					if($headerStart == 1){
						if(stripos($item,'Name') !== false){
							$field_name = 'Name:';
							//$field_val = trim(str_ireplace('Name:','',$item));
							$nextKey = $key+1;
							$field_val = '';
							if (array_key_exists($nextKey,$elements)){
								$field_val = trim($elements[$nextKey]);
							}
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
							/////////////////////////
							$pat_name = explode(',',$field_val);
							if(!empty($pat_name) && count($pat_name) > 0){
								$lname = isset($pat_name[0]) ? trim($pat_name[0]) : '';
								$tempArr = array('key' => 'last_name', 'value' => $lname);
								array_push($responseArray['rpt_header'], $tempArr);
								
								$fname = isset($pat_name[1]) ? trim($pat_name[1]) : '';
								if(!empty($fname)){
									$fnameArr = explode(' ',$fname);
									$fname = trim($fnameArr[0]);
								}
								$tempArr = array('key' => 'first_name', 'value' => $fname);
								array_push($responseArray['rpt_header'], $tempArr);
							}
							else{
								$tempArr = array('key' => 'first_name', 'value' => '');
								array_push($responseArray['rpt_header'], $tempArr);
								$tempArr = array('key' => 'last_name', 'value' => '');
								array_push($responseArray['rpt_header'], $tempArr);
							}
						}					
						else if(stripos($item,'Birthdate') !== false){
							$field_name = 'Birthdate:';
							$field_val = trim(str_ireplace('Birthdate:','',$item));
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
							//////////////////////
							$field_name = 'dob';
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						
											
					}
				}
				
			}
		}
	}
	
	return $responseArray;
}

function catapultHealthReportHandler($fileName='',$reportContents='', $configurationName = '',$email_addr=''){
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
				
	$faxCategory = 'Results'; //MR => Medical Record
	$faxType = 'Lab Report';
	$faxTypeMain = 'Lab Report';
	
	if(!empty($reportContents)){
		$headerContents = $reportContents;
		$bodyContents = $reportContents;
		
		if(stripos($reportContents,'The Catapult Health Team') !== false){
			$posStart = stripos($reportContents,'The Catapult Health Team');
			$headerContents = substr($reportContents,$posStart);
			$bodyContents = $headerContents;
		}
		if(stripos($headerContents,'Patient Information') !== false){
			$posStart = stripos($headerContents,'Patient Information');
			$headerContents = substr($headerContents,$posStart);
			$bodyContents = $headerContents;//start from 'Patient Information' to end of contents
		}
		
		//Test\tIn Range\tOut of Range\tReference Range\n
		if(stripos($headerContents,'Reference Range') !== false){
			$posEnd = stripos($headerContents,'Reference Range');
			$headerContents = substr($headerContents,0,$posEnd);//start from 'Patient Information' to 'Reference Range'
			$posStart = $posEnd + 16;//15=> number of characters in 'Reference Range'
			$bodyContents = substr($bodyContents,$posStart);
		}
		
		if(stripos($bodyContents,'Catapult Health') !== false){
			$posEnd = stripos($bodyContents,'Catapult Health');
			$bodyContents = substr($bodyContents,0,$posEnd);
		}
		/////////////////////////////////
		$rpt_lines = explode("\n",$headerContents);
		
		/*print "<pre>";
		print_r($rpt_lines);
		print "</pre>";*/
		
		foreach($rpt_lines as $key => $line){
			
			if(stripos($line,'Name:') !== false && stripos($line,'Specimen ID:') !== false){
				//Name: DOUGLAS, AMY\tSpecimen ID: 166261710\tPhar Bescos M.D.\n
				$field_name = 'Patient:';//Name:  (Lastname, FirstName) HENDERSON,ROBERT
				$elements = explode("\t",$line);					
				$field_val = isset($elements[0]) ? $elements[0]: '';					
				$field_val = trim(str_ireplace('Name:','',$field_val));					
				
				$pat_name = explode(',',$field_val);
				if(!empty($pat_name) && count($pat_name) > 0){
					$firstName = isset($pat_name[1]) ? trim($pat_name[1]) : ''; 							
					if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
						$firstNameArr = explode(' ',$firstName);
						$firstName = $firstNameArr[0];
					}
					$tempArr = array('key' => 'first_name', 'value' => $firstName);
					//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
					array_push($responseArray['rpt_header'], $tempArr);
					
					$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
					array_push($responseArray['rpt_header'], $tempArr);
				}
				else{
					$tempArr = array('key' => 'first_name', 'value' => '');
					array_push($responseArray['rpt_header'], $tempArr);
					$tempArr = array('key' => 'last_name', 'value' => '');
					array_push($responseArray['rpt_header'], $tempArr);
				}
				/////////////////////////////
				$field_name = 'Specimen ID:';
				$field_val = isset($elements[1]) ? $elements[1]: '';
				$field_val = trim(str_ireplace('Specimen ID:','',$field_val));
				$tempArr = array('key' => $field_name, 'value' => $field_val);
				array_push($responseArray['rpt_header'], $tempArr);
			}
			else if(stripos($line,'DOB:') !== false && stripos($line,'Age:') !== false && stripos($line,'Date Collected:') !== false){
				//DOB: 01/13/1981 Age: 39\tDate Collected: 
				$field_name = 'dob';
				//$elements = explode("\t",$line);
				//$field_val = isset($elements[0]) ? $elements[0]: '';// 09/17/1994
				$posAge = stripos($line,'Age:');
				$field_val = substr($line,0,$posAge-1);//DOB: 01/13/1981
				$field_val = trim(str_ireplace('DOB','',$field_val));
				$field_val = trim(str_ireplace(':','',$field_val));											
				$tempArr = array('key' => $field_name, 'value' => $field_val);
				array_push($responseArray['rpt_header'], $tempArr);
								
			}
			
		}
		////////////////////////////////////////////////////////
		
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	//print "<pre>";print_r($responseArray);print "</pre>";exit;
	return $responseArray;
}

//conwayRad
function conwayRadHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	
	
	$titleKeys = array('PROCEDURE:','PROCEDURE :','DATE:','OATE:','COMPARISON:','COMPARISON :','COMP ARISCN:','COMP ARISCN :','HISTORY:','HISTORY :','TECHNIQUE:','TECHNIQUE :','FINDINGS:','FINDINGS :','IMPRESSION:','IMPRESSION :','CONCLUSION:','CONCLUSION :','INDICATION:','INDICATION :','RECOMMENDATION:','RECOMMENDATION :','Transcribed by','Signed by','Electronically Signed By');
	$titles2 = array('DATE','OATE','COMPARISON','HISTORY','TECHNIQUE','FINDINGS','IMPRESSION','CONCLUSION','INDICATION','RECOMMENDATION');
						
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;
	//$responseArray['tsv_name'] = $tsv_name;
	//$responseArray['email_addr'] = $email_addr;
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$faxCategory = 'Results';
	$faxType = $faxTypeMain = 'Radiology Report';//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	
	$reportType = $reportHeader = $reportBody = $procStr = $compStr = $findingsStr= $impressionStr = $dateStr = $historyStr = $techStr = $recommendStr = $conclusionStr = $indicationStr = '';
	
	$procFlag = $dateFlag = $historyFlag = $techFlag = $indFlag =$compFlag= $recommendFlag = $findingsFlag = $impFlag = $concFlag = false;
	
	if(!empty($reportContents))
	{
					
		$rpt_lines = explode("\n",$reportContents);
		
		
		if(stripos($reportContents,'Physician Office Request for Copies of Medical Records')!== false || stripos($reportContents,'MEDICIAD EXT REQ')!== false){
			$faxCategory = 'Correspondence';
			$faxType = 'Medical Records Request';
			$faxTypeMain = 'Medical Records Request';
			
			$responseArray['fax_category'] = $faxCategory;
			$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
			
			$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
			array_push($responseArray['rpt_header'], $tempArr);
			
			$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
			array_push($responseArray['rpt_header'], $tempArr);
			
			$searchStr = "Patient Name:";
			//$key = array_search($searchStr, $rpt_lines);
			$key = array_search_partial($rpt_lines,$searchStr);
			//echo "<br>Key: ".$key."<br>";
			if (array_key_exists($key,$rpt_lines))
			{
				$patientStr = $rpt_lines[$key];
				if(!empty($patientStr)){
					$elements = explode("\t",$patientStr);
					$field_val = isset($elements[0]) ? $elements[0]: '';
					$field_val = trim(str_ireplace('Patient Name:','',$field_val));
					$field_name = 'Patient:';
					//$tempArr = array('key' => $field_name, 'value' => $field_val);
					//array_push($responseArray['rpt_header'], $tempArr);
					
					$pat_name = explode(' ',$field_val);
					if(!empty($pat_name) && count($pat_name) > 0){
						
						if(count($pat_name) > 1){
							$fname = trim($pat_name[0]);										
							$lname = trim($pat_name[1]);
						}
						else{ //when only one part
							$lname = trim($pat_name[0]);
							$fname = trim($pat_name[0]);
						}
						$tempArr = array('key' => 'first_name', 'value' => $fname);
						array_push($responseArray['rpt_header'], $tempArr);
						$tempArr = array('key' => 'last_name', 'value' => $lname);
						array_push($responseArray['rpt_header'], $tempArr);
					}
					else{
						$tempArr = array('key' => 'first_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
						$tempArr = array('key' => 'last_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
					////////////////////////
					//$field_name = 'DOB:';
					$field_name = 'dob';
					$field_val = isset($elements[1]) ? $elements[1]: '';
					$field_val = trim(str_ireplace('DOB:','',$field_val));
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);
				}
			}			
		}
		else
		{
			$patientStr = $dobStr = $acctStr = $entryDate = '';
			
			$posStart = stripos($reportContents,'Imaging Services');
			//$posStart = stripos($reportContents,'Imaging Services');
			$reportContents = substr($reportContents,$posStart);//to the end of report	
			//$reportContents = trim(str_ireplace('Patient:','',$reportContents));
			$posEnd = stripos($reportContents,'PROCEDURE:');
			$headerContents = substr($reportContents,0,$posEnd);
			
			$posStart = stripos($reportContents,'PROCEDURE:');
			$bodyContents = substr($reportContents,$posStart);
			///////////////////////////////////////////////////////////////
			$rpt_lines = explode("\n",$headerContents);
			foreach($rpt_lines as $key => $line){
				$elements = explode("\t",$line);
				
				
				if(stripos($line,'Patient:') !== false && stripos($line,'MR#:') !== false){
					$field_name = 'Patient:';//Name:  (Lastname, FirstName) HENDERSON,ROBERT
					$elements = explode("\t",$line);					
					$field_val = isset($elements[0]) ? $elements[0]: '';					
					$field_val = trim(str_ireplace('Patient:','',$field_val));					
					
					$pat_name = explode(',',$field_val);
					if(!empty($pat_name) && count($pat_name) > 0){
						$firstName = isset($pat_name[1]) ? trim($pat_name[1]) : ''; 							
						if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
							$firstNameArr = explode(' ',$firstName);
							$firstName = $firstNameArr[0];
						}
						$tempArr = array('key' => 'first_name', 'value' => $firstName);
						//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
						
						$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
					else{
						$tempArr = array('key' => 'first_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
						$tempArr = array('key' => 'last_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
					/////////////////////////////
									
				}
				else if(stripos($line,'Date Of Birth:') !== false){					
					$field_name = 'dob';
					$elements = explode("\t",$line);
					$field_val = isset($elements[0]) ? $elements[0]: '';// 09/17/1994
					//$posAge = stripos($line,'Age/Sex:');
					//$field_val = substr($line,0,$posAge-1);//DOB: 02/25/1950
					$field_val = trim(str_ireplace('Date Of Birth','',$field_val));
					$field_val = trim(str_ireplace(':','',$field_val));											
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);
				}
				
								
			}// end foreach
			///////////////////////////////////////////////////////////////
			$reportHeader = 1;			
			$tempArr = array('key' => 'TYPE:', 'value' => 'RADIOLOGICAL EXAMINATION');
			array_push($responseArray['rpt_header'], $tempArr);
			$faxType = $faxTypeMain = $reportType = "RADIOLOGICAL EXAMINATION";
			//////////////////////////////////////////////////////////////
			$reportBodyPos = 0;
			$reportBodyArr = array();
			$searchStr = "RADIOLOGY REPORT";
			$key = array_search($searchStr, $rpt_lines); // $key = 2;//If needle is a string, the comparison is done in a case-sensitive manner.
			//echo "Key => ".$key.'<br>';		
			$nextKey = $key+1;		
			if (array_key_exists($nextKey,$rpt_lines)){
				$reportBody = $rpt_lines[$nextKey];
				$reportBodyPos = $nextKey;
				$reportBodyArr = array_splice($rpt_lines, $nextKey);	
			}
			$rpt_lines = explode("\n",$bodyContents);
			$reportBodyArr = $rpt_lines;
			
			//////////////////////////////////////////////////////////////////
			$index = 0;
			$foundItems = array();
			//array_push($foundItems, 'PROCEDURE:');
			
			foreach($titleKeys as $item){			
				if(stripos($bodyContents,$item) !== false){
					$itemPos = stripos($bodyContents,$item);
					$foundItems[$index]['element'] = $item;
					$foundItems[$index]['pos'] = $itemPos;
					$index++;
				}
			}
			
			/*$itemPos = array();
			$itemPos = array_column($foundItems, 'pos');//PHP 5.5.0+
			array_multisort($itemPos, SORT_ASC, $foundItems); //array_multisort($itemPos, SORT_DESC, $pos);*/
			
			//usort($foundItems, build_sorter('pos'));
			
			usort($foundItems, function ($item1, $item2) {
				return $item1['pos'] <=> $item2['pos'];
			});
						
			
			
			//////////////////////////////////////////////////////////////////				
			$responseArray['fax_category'] = $faxCategory;
			$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
			
			$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
			array_push($responseArray['rpt_header'], $tempArr);
		}		
	} //end if reportContents
	
	return $responseArray;
}



function questDiagnosticsHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	
	$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	$testName = '';
	$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	
	//$responseArray['tsv_name'] = $tsv_name;
	//$responseArray['email_addr'] = $email_addr;
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	
	if(!empty($tempArr) && count($tempArr) > 2){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		if(isset($tempArr[2]) && is_numeric($tempArr[2])){
			$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		}
		if(isset($tempArr[3]) && is_numeric($tempArr[3])){
			$fax_data_id = isset($tempArr[3]) ? $tempArr[3] : 0;
		}
	}
	
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$strHeader = '';
	$strBody = '';
	
	
	$responseArray = array();
	return $responseArray;	
}

function pathologyAssocARHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
		
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	//print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$posStart = 0;
	$faxCategory = $faxTypeMain = $faxType = 'Pathology Report';//Results/Medical Center/UAMS Hospital	
	//$responseArray['fax_type'] = 'Lab Report';
	
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$reportCount = 0;
		$count_1 = substr_count($reportContents,'END OF REPORT');//Note: The substring is case-sensitive.
		$count_2 = substr_count($reportContents,'END OF RFPORT');
		if(!empty($count_1) &&  $count_1 > 0){
			$reportCount = $reportCount + $count_1;
		}
		
		if(!empty($count_2) &&  $count_2 > 0){
			$reportCount = $reportCount + $count_2;
		}
		//echo "No. of Reports Found => ".$reportCount."<br>";
		
		if(stripos($reportContents,'MOLECULAR PATHOLOGY REPORT') !== false)
		{
			$reportStart = 1;
			$headerStart = 1;			
			$faxType = "MOLECULAR PATHOLOGY REPORT";
			$posStart = stripos($reportContents,'MOLECULAR PATHOLOGY REPORT');
			
			
		}
		else if(stripos($reportContents,'SURGICAL PATHOLOGY REPORT') !== false){
			$reportStart = 1;
			$headerStart = 1;			
			$faxType = "SURGICAL PATHOLOGY REPORT";
			$posStart = stripos($reportContents,'SURGICAL PATHOLOGY REPORT');
		}			
		$reportContents = substr($reportContents,$posStart);//to end of contents
		$rpt_lines = explode("\n",$reportContents);
		/*print "<pre>";
		print_r($reportContents);
		print "</pre>";*/
		
		$posEnd = $posEnd1 = $posEnd2 = $posStart + strlen($reportContents);
		//echo 'Start Pos: '.$posStart.'      End Pos: '.$posEnd."<br>";
		
		if(stripos($reportContents,'END OF REPORT') !==false){
			$posEnd1 = stripos($reportContents,'END OF REPORT');
		}
		if(stripos($reportContents,'END OF RFPORT') !==false){
			$posEnd2 = stripos($reportContents,'END OF RFPORT');
		}
		$posStart = 0;
		$posEnd = min($posEnd,$posEnd1,$posEnd2);
		//echo 'Start Pos: '.$posStart.'      End Pos: '.$posEnd."<br>";
		
		$reportContents = substr($reportContents,$posStart,$posEnd);//to end of contents
		
		$rpt_lines = explode("\n",$reportContents);
		/*print "<pre>";
		print_r($rpt_lines);
		print "</pre>";//exit;*/
		
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			//if(stripos($reportContents,'University of Arkansas for Medical Sciences')!== false)
			if($faxType =='MOLECULAR PATHOLOGY REPORT'){
				foreach($rpt_lines as $key => $line){
					$elements = explode("\t",$line);
					if(stripos($line,'patient Name:') !==false && stripos($line,'Accession #:') !==false){
						$field_val = isset($elements[0]) ? trim($elements[0]) : '';//Bunch, Timothy  => lName fName 
						$field_val = trim(str_ireplace('patient Name:','',$field_val));
						$pat_name = explode(',',$field_val);
						
						if(!empty($pat_name) && count($pat_name) > 0){
							$lastName = isset($pat_name[0]) ? trim($pat_name[0]) : '';
							$firstName = isset($pat_name[1]) ? trim($pat_name[1]) : ''; 							
							if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
								$firstNameArr = explode(' ',$firstName);
								$firstName = $firstNameArr[0];
							}
							$tempArr = array('key' => 'first_name', 'value' => $firstName);
							//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$tempArr = array('key' => 'last_name', 'value' => $lastName);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						
					}
					else if(stripos($line,'DOB/Age/Sex:') !==false && stripos($line,'Date Taken:') !==false){
						$field_name = 'dob';
						$field_val = isset($elements[1]) ? trim($elements[1]) : '';
						$field_val = trim(str_ireplace('DOB/Age/Sex:','',$field_val));	
						$posChar = stripos($field_val,'(');
						if($posChar !==false){
							$field_val = substr($field_val,0,$posChar-1);
						}
						
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						//////////////////////////////
						
						
					}
					
				}//end foreach $rpt_lines
			}
			else if($faxType =='SURGICAL PATHOLOGY REPORT'){
				foreach($rpt_lines as $key => $line){
					$elements = explode("\t",$line);
					if(stripos($line,'patient Name:') !==false && stripos($line,'Accession #:') !==false){
						$field_val = isset($elements[0]) ? trim($elements[0]) : '';//Bunch, Timothy  => lName fName 
						$field_val = trim(str_ireplace('patient Name:','',$field_val));
						
						$pat_name = explode(',',$field_val);
						
						if(!empty($pat_name) && count($pat_name) > 0){
							$lastName = isset($pat_name[0]) ? trim($pat_name[0]) : '';
							$firstName = isset($pat_name[1]) ? trim($pat_name[1]) : ''; 							
							if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
								$firstNameArr = explode(' ',$firstName);
								$firstName = $firstNameArr[0];
							}
							$tempArr = array('key' => 'first_name', 'value' => $firstName);
							//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$tempArr = array('key' => 'last_name', 'value' => $lastName);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						
					}
					else if(stripos($line,'DOB/Age/Sex:') !==false && stripos($line,'Date Received:') !==false){
						$field_name = 'dob';
						$field_val = isset($elements[0]) ? trim($elements[0]) : '';
						$field_val = trim(str_ireplace('DOB/Age/Sex:','',$field_val));	
						$posChar = stripos($field_val,'(');
						if($posChar !==false){
							$field_val = substr($field_val,0,$posChar-1);
						}
						
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
						
					}
					
				}//end foreach $rpt_lines
			}				
		}
	}// end if report contents
	
	$responseArray['fax_category'] = $faxCategory;
	//$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	$responseArray['fax_type'] = $faxType;
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
		
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	return $responseArray;
	
}

//Baptist
function baptistReportHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	$header_keys = array('NAME:','PHY:','MRN :','HAR:','SEX:','RACE:','CSN :','PAT TYPE:','DOB:','ADM DATE:','DIS DATE:','ORD PHY:','DICT PHY:','TYPE:','Dictated by:');
	$docTitleArr = array(
											'ABSCESSOGRAM',
											'ABDOMINAL RADIOGRAPH',
											'BILATERAL DIGITAL DIAGNOSTIC MAMMOGRAMS',
											'CERVICAL SPINE RADIOGRAPHS',											
											'CHEST RADIOGRAPHS, 2 VIEWS',
											'CHEST RADIOGRAPH, ONE VIEW',
											'CHEST RADIOGRAPHS, TWO VIEWS',
											'CHEST RADIOGRAPHS',
											'CHEST RADIOGRAPH',
											'CHEST AND RIB RADIOGRAPHS, 7 VIEWS',
											'LEFT FOREARM RADIOGRAPHS',
											'LEFT HAND RADIOGRAPHS',
											'LEFT ANKLE RADIOGRAPHS',
											'LEFT KNEE RADIOGRAPHS',	
											'LEFT SHOULDER RADIOGRAPHS',		
											'11 HIP RADIOGRAPHS',		
											'CT ABDOMEN AND PELVIS WITHOUT CONTRAST',
											'CT ABDOMEN AND PELVIS WITH CONTRAST',	
											'CT ABDOMEN AND PELVIS WITH IV CONTRAST (over read)',
											'CT ABDOMEN AND PELVIS WITH IV CONTRAST',				
											'CT ABDOMEN AND PELVIS WITHOUT IV CONTRAST',
											'CT CERVICAL SPINE WITHOUT IV CONTRAST',
											'CT CHEST, ABDOMEN, AND PELVIS WITH IV CONTRAST',											
											'CT CHEST WITH IV CONTRAST - PULMONARY EMBOLISM PROTOCOL',
											'CT CHEST (PE PROTOCOL)',
											'CT guided percutaneous abdominal abscess drain placement.',
											'CT HEAD WITHOUT IV CONTRAST',
											'CTA HEAD',
											'CT LUMBAR THORACIC SPINE WITHOUT IV CONTRAST',
											'Digital Mammo Screening W CAD',
											'EEG',
											'ESOPHAGRAM',
											'Limited ultrasound of left upper extremity dialysis access',
											'LEFT HUMERUS RADIOGRAPHS',
											'Left upper extremity fistulogram with central venous runoff and ballon angioplasty',
											'LEFT WRIST RADIOGRAPHS',
											'MR1 BRAIN WITHOUT AND WlTH 1V CONTRAST',
											'MRI RIGHT LEG WITHOUT AND WITH IV CONTRAST',
											'MRI RIGHT SHOULDER WITHOUT CONTRAST',
											'MRI LUMBAR SPINE WITHOUT IV CONTRAST',
											'MRI LOWER EXTREMITY WITHOUT IV CONTRAST',
											'MRI THORACIC SPINE WITHOUT IV CONTRAST',
											'NICL ECHO W/COLOR FLOW DOPPLER',
											'NUCLEAR MEDICINE HEPATOBILIARY SCAN WITH EJECTION FRACTION',
											'Procedure: IED of abscess',
											'Procedure: Esphagogastroduodenoscopy --diagnostic, with biopsy',
											'PROCEDURE: This is a 20-minute EEG. A standard 10/20 electrode system was$$used for recording.',
											'Total thyroidectomy with isthmusectomy',
											'TTE procedure: NICL ECHO W/COLOR FLOW DOPPLER.',
											'ULTRASOUND OF THE ABDOMEN, RIGHT UPPER QUADRANT',
											'ULTRASOUND OF THE ABDOMEN, COMPLETE',
											'ULTRASOUND OF THE PELVIS',
											'ULTRASOUND VENOUS DOPPLER OF THE LEGS',
											'ULTRASOUND VENOUS DOPPLER OF THE LEFT LEG',
											'ULTRASOUND VENOUS DOPPLER OF THE RIGHT LEG',
											'ULTRASOUND OF THE KIDNEYS AND URINARY BLADDER',
											'UPPER GASTROINTESTINAL EXAMINATION',
											'CT KNEE WITHOUT IV CONTRAST',
											'CT SCAN OF THE HEAD WITHOUT CONTRAST',
											'CT ORBITS WITHOUT IV CONTRAST',
											'FLUCROSCOPICALLY GUIDED RIGHT HIP ARTHROGRAM',
											'RIGHT KNEE RADIOGRAPHS',
											'RIGHT HIP RADIOGRAPHS',
											'NUCLEAR MEDICINE PERFUSION LUNG SCAN',
											'MRI BRAIN WITH AND WITHOUT IV CONTRAST',
											'MRI BRAIN WITHOUT IV CONTRAST',
											'MRI CERVICAL SPINE WITHOUT IV CONTRAST',
											'MRI RIGHT KNEE WITHOUT CONTRAST',
											'MRI RIGHT HIP WITH INTRA-ARTICULAR CONTRAST (ARTHROGRAM)',
											'ULTRASOUND VENOUS DOPPLER OF THE LEFT ARM',
											'X-RAY CHEST, PORTABLE',
											'CT Head Without Contrast',
											'CT Maxillofacial Without Contrast',
											'CTA OF THE HEART (Arkansas Cardiology Clinic over read)',
											'CT HEAD WITH IV CONTRAST',
											'CT CHEST WITH IV CONTRAST',
											'CT CARDIAC CALCIUM SCORING (Cardiology over read)',
											'CT SCAN OF THE ABDOMEN AND PELVIS WITHOUT CONTRAST (Renal Stone Protocol)',
											'CT HEAD WITHOUT AND WITH IV CONTRAST',
											'CT ABDOMEN AND PELVIS WITH IV AND ORAL CONTRAST',
											'CT RIGHT HIP WITHCUT IV CONTRAST'
						);
	//$reportTypeBaptist $reportStrBaptist
	$reportTypeArr = array(							
							'ECHOCARDIOGRAM',
							'EEG',							
							'MAMMOGRAPHY',
							'RADIOLOGICAL EXAMINATION'
					);
	
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;
	//$responseArray['tsv_name'] = $tsv_name;
	//$responseArray['email_addr'] = $email_addr;
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	/*if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}*/
	if(!empty($tempArr) && count($tempArr) > 2){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		if(isset($tempArr[2]) && is_numeric($tempArr[2])){
			$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		}
		if(isset($tempArr[3]) && is_numeric($tempArr[3])){
			$fax_data_id = isset($tempArr[3]) ? $tempArr[3] : 0;
		}
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	//$responseArray['text_contents'] = $reportContents;
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$faxCategory = 'Results';
	$faxType = $faxTypeMain = 'Radiology Report';//RADIOLOGICAL EXAMINATION, Refill Request, 90 DAYS SUPPLY,Prior Authorization
	$department = 'BHMC'; //Baptist Health Medical Center - Little Rock AR
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$reportType = $procDateTime = $procStr = $findingsStr = $impressionStr ='';
	if(!empty($reportContents)){
		//\xEF\xBF\xBDIME
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents));
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //x20 is space in hex
		
		//if cover page is first page
		$posStart = stripos($reportContents,'IMPORTANT NOTICE');
		if($posStart !== false){
			$reportContents = substr($reportContents,$posStart);// To end of contents				
		}
		else{
			$posStart = stripos($reportContents,'THE FOLLOWING INFORMATION IS CONFIDENTIAL');
			if($posStart !== false){
				$reportContents = substr($reportContents,$posStart);// To end of contents				
			}
		}
		$posStart = stripos($reportContents,'Baptist Health');
		$reportContents = substr($reportContents,$posStart);// To end of contents
		/////////////////////////////////////////////////////////////////
		//Master Patient Index
		//UNIT:   RM:    MPI:    NUMBER:   DIAGNGSIS:   BHIN:
		if(stripos($reportContents,'RM:') !== false && stripos($reportContents,'DIAGNOSIS:') !== false && stripos($reportContents,'BHIN') !== false){
			/*$rpt_lines = explode("\n",$reportContents);
			print "<pre>";
			print_r($rpt_lines);
			print "</pre>";*/
			$faxCategory = 'Results';
			$faxType = 'Lab Report';
			$faxTypeMain = 'Lab Report';
			
			//$respArray = baptistLabReportHandler($fileName,$reportContents, $configurationName,$email_addr,$responseArray);
			$responseArray = baptistLabReportHandler($fileName,$reportContents, $configurationName,$email_addr,$responseArray);
			//print "<pre>";print_r($respArray);print "</pre>";
			//$responseArray = array_merge($responseArray,$respArray);
			//return $responseArray;
		}
		else
		{
		if(stripos($reportContents,'Summary of Care Report')!== false){
			$faxCategory = 'Hospital';
			$faxType = 'Summary of Care Report';
			$faxTypeMain = 'Progress Note';//Refill Request, 90 DAYS SUPPLY,Prior Authorization
			//Patient Demographics
			$demoPos = stripos($reportContents,'Patient Demographics');
			if($demoPos!=false){
				$reportContents = substr($reportContents,$demoPos);//To end of contents
				$rpt_lines = explode("\n",$reportContents);
				/*print "<pre>";
				print_r($rpt_lines);
				print "</pre>";*/
				$lineContents = isset($rpt_lines[1]) ? trim($rpt_lines[1]) : '';
				//Name	Patient ID	SSN	Gender Identity	Birth Date
				if(stripos($lineContents,'Name')!==false || stripos($lineContents,'Patient ID')!==false || stripos($lineContents,'SSN')!==false || stripos($lineContents,'Gender')!==false || stripos($lineContents,'Birth Date')!==false)
				{
					$lineContents = isset($rpt_lines[2]) ? trim($rpt_lines[2]) : '';
				}
				
				//if (array_key_exists(2,$rpt_lines))
				if(!empty($lineContents))
				{
					//$lineContents = $rpt_lines[2];
					//echo '<br>lineContents =>'.$lineContents;						
					$elements = explode("\t",$lineContents);
					
					$field_val = isset($elements[0]) ? trim($elements[0]): '';
					$tempArr = array('key' => 'NAME:', 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);
					
					$pat_name = explode(',',$field_val);//Leverton, Jami (Lastname, FirstName)
					if(!empty($pat_name) && count($pat_name) > 0){
						$firstName = isset($pat_name[1]) ? trim($pat_name[1]) : ''; 							
						if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
							$firstNameArr = explode(' ',$firstName);
							$firstName = $firstNameArr[0];
						}
						$tempArr = array('key' => 'first_name', 'value' => $firstName);
						//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
						
						$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
					else{
						$tempArr = array('key' => 'first_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
						$tempArr = array('key' => 'last_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
					///////////////////////////////////////
					$field_name = 'dob';
					$field_val = isset($elements[4]) ? trim($elements[4]): '';
					$tempPos = stripos($field_val,'(');
					if($tempPos!==false){
						$field_val = substr($field_val,0,($tempPos-1));
					}
					$tempArr = array('key' => $field_name, 'value' => trim($field_val));
					array_push($responseArray['rpt_header'], $tempArr);
					
					$tempArr = array('key' => 'DOB:', 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);
				}
				
				
			}
			//////////////////////////////////////////////////////////////////
			$responseArray['fax_category'] = $faxCategory;
			$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
			
			$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
			array_push($responseArray['rpt_header'], $tempArr);
			
			$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
			array_push($responseArray['rpt_header'], $tempArr);
		}
		else if(stripos($reportContents,'Summary of Care')!== false && stripos($reportContents,'Demographic Information')!== false){
			$faxCategory = 'Hospital';
			$faxType = 'Summary of Care Report';
			$faxTypeMain = 'Progress Note';//Refill Request, 90 DAYS SUPPLY,Prior Authorization
			//Patient Demographics
			$demoPos = stripos($reportContents,'Demographic Information');
			if($demoPos!=false){
				$posStart = stripos($reportContents,'Legal Name:');
				$reportContents = substr($reportContents,$posStart, ($demoPos - $posStart));//To end of contents
				$rpt_lines = explode("\n",$reportContents);
				/*print "<pre>";
				print_r($rpt_lines);
				print "</pre>";*/
				
				if(!empty($rpt_lines)){
								
					foreach($rpt_lines as $key => $line)
					{
						echo $key.'=>'.$line.'<br>';
						$elements = explode("\t",$line);
						print "<pre>";
						print_r($elements);
						print "</pre>";
						
						if((stripos($line,'Legal Name:') !== false || stripos($line,'Legal Name :') !== false)){
							$field_name = 'NAME:';
							//$field_val = trim(str_ireplace('Name:','',$item));
							/*$nextKey = $key+1;
							$field_val = '';
							if (array_key_exists($nextKey,$elements)){
								$field_val = trim($elements[$nextKey]);
							}*/
							
							//$elements = explode("\t",$line);
							//$field_val = isset($elements[0]) ? trim($elements[0]) : '';
							
							$field_val = trim(str_ireplace('Legal Name','',$line));
							$field_val = trim(str_ireplace(':','',$field_val));
							//$tempArr = array('key' => $field_name, 'value' => $field_val);
							//array_push($responseArray['rpt_header'], $tempArr);
							//////////////////////////////////////////								
							$pat_name = explode(' ',$field_val); //Legal NAME: (Fname Lname)					
							if(!empty($pat_name) && count($pat_name) > 0){
								if(count($pat_name) > 1){
									if(count($pat_name) > 3){
										$fname = trim($pat_name[0]);
										//$mname = $pat_name[1];
										$lname = trim($pat_name[2]);
									}
									else if(count($pat_name) == 3){
										$fname = trim($pat_name[0]);
										//$mname = $pat_name[1];
										$lname = trim($pat_name[2]);
									}
									else{
										$fname = trim($pat_name[0]);										
										$lname = trim($pat_name[1]);
									}
									
								}
								else{ //when only one part
									$lname = trim($pat_name[0]);
									$fname = trim($pat_name[0]);
								}
								$tempArr = array('key' => 'first_name', 'value' => $fname);
								array_push($responseArray['rpt_header'], $tempArr);
								$tempArr = array('key' => 'last_name', 'value' => $lname);
								array_push($responseArray['rpt_header'], $tempArr);
							}
							else{
								$tempArr = array('key' => 'first_name', 'value' => '');
								array_push($responseArray['rpt_header'], $tempArr);
								$tempArr = array('key' => 'last_name', 'value' => '');
								array_push($responseArray['rpt_header'], $tempArr);
							}
						}						
						else if(stripos($line,'DOB') !== false){	
							
							$field_name = 'dob';								
							$field_val = trim(str_ireplace('DOB','',$line));// m/d/Y
							$field_val = trim(str_ireplace(':','',$field_val));	
							$field_val = trim(str_ireplace(';','',$field_val));
							$field_val = trim(str_ireplace('.','',$field_val));
							if(stripos($field_val,'(') !== false){
								$posLast = stripos($field_val,'(');
								$field_val = trim(substr($field_val,0,$posLast));
							}
							/*else{
								$field_val = trim(substr($field_val,0,$posLast);// 8/29/1949
							}*/
							
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);							
						}
						else if(stripos($line,'MRN') !== false){	
							
							$field_name = 'MRN:';								
							$field_val = trim(str_ireplace('MRN','',$line));
							$field_val = trim(str_ireplace(':','',$field_val));	
							$field_val = trim(str_ireplace(';','',$field_val));
							$field_val = trim(str_ireplace('.','',$field_val));						
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);							
						}							
					}// end foreach
				}// end if
			}
			//////////////////////////////////////////////////////////////////
			$responseArray['fax_category'] = $faxCategory;
			$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
			
			$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
			array_push($responseArray['rpt_header'], $tempArr);
			
			$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
			array_push($responseArray['rpt_header'], $tempArr);
		}
		else if(stripos($reportContents,'Baptist Health Urgent Care')!== false){
			$faxCategory = 'Results';
			$faxType = 'Baptist Health Urgent Care';
			$faxTypeMain = 'Baptist Health Urgent Care';
			
			//HIGHLY CONFIDENTIAL.
			$posStart = 0;
			if(stripos($reportContents,'HIGHLY CONFIDENTIAL') !==false){
				$posStart = stripos($reportContents,'HIGHLY CONFIDENTIAL');
				$reportContents = substr($reportContents,$posStart);
			}
			if(stripos($reportContents,'Urgent Team') !==false){
				$posStart = stripos($reportContents,'Urgent Team');
				$reportContents = substr($reportContents,$posStart);
			}
			$posStart = 0;			
			$posEnd = stripos($reportContents,'Baptist Health Urgent Care');
			$reportContents = substr($reportContents,$posStart,($posEnd - $posStart));
			
			$rpt_lines = explode("\n",$reportContents);
			
			foreach($rpt_lines as $key => $line)
			{
				echo $key.'=>'.$line.'<br>';
				$elements = explode("\t",$line);
				print "<pre>";
				print_r($elements);
				print "</pre>";
				
				if((stripos($line,'Patient:') !== false || stripos($line,'Patlent:') !== false || stripos($line,'Pationt:') !== false) && stripos($line,'DOB') !== false){
					$field_name = 'NAME:';
					//$field_val = trim(str_ireplace('Name:','',$item));
					/*$nextKey = $key+1;
					$field_val = '';
					if (array_key_exists($nextKey,$elements)){
						$field_val = trim($elements[$nextKey]);
					}*/
					$elements = explode("\t",$line);
					$field_val = isset($elements[0]) ? trim($elements[0]) : '';	//Patlent: Luke Lucketta (DOB:3/26/2011)
					$posDOB = stripos($field_val,'DOB') - 1;
					$dobStr = substr($field_val,$posDOB); //To end of line
					$field_val = substr($field_val,0,$posDOB);
					$field_val = trim(str_ireplace('Patlent','',$field_val));
					$field_val = trim(str_ireplace('Patient','',$field_val));
					$field_val = trim(str_ireplace(':','',$field_val));
					
					//////////////////////////////////////////								
					$pat_name = explode(' ',$field_val); //Patlent: Luke Lucketta (Fname Lname)					
					if(!empty($pat_name) && count($pat_name) > 0){
						$firstName = isset($pat_name[0]) ? trim($pat_name[0]) : '';						
						$tempArr = array('key' => 'first_name', 'value' => $firstName);
						//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
						
						$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
					else{
						$tempArr = array('key' => 'first_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
						$tempArr = array('key' => 'last_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
					/////////////////////////////////////////////////
					$field_name = 'dob';					
					$field_val = $dobStr;
					$field_val = trim(str_ireplace(')','',$field_val));
					$field_val = trim(str_ireplace('(','',$field_val));
					$field_val = trim(str_ireplace('DOB','',$field_val));
					$field_val = trim(str_ireplace(':','',$field_val));
					//$field_val = substr($field_val,0,10);//DOB: 09/01/1977
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);
				}							
				
			} //end foreach
			
		}				
		else
		{
			//if cover page is first page
			$posStart = stripos($reportContents,'IMPORTANT NOTICE');
			if($posStart !== false){
				$reportContents = substr($reportContents,$posStart);// To end of contents				
			}
			$posStart = stripos($reportContents,'Baptist Health');
			$reportContents = substr($reportContents,$posStart);// To end of contents
			/////////////////////////////////////////////////////////////////
			$rpt_lines = explode("\n",$reportContents);
			/*print "<pre>";
			print_r($rpt_lines);
			print "</pre>";*/
			
			$reportStart = 1;
			$headerStart = 1;
		
			if(!empty($rpt_lines)){
									
				foreach($rpt_lines as $key => $line)
				{
					echo $key.'=>'.$line.'<br>';
					$elements = explode("\t",$line);
					print "<pre>";
					print_r($elements);
					print "</pre>";
					//ARRIVAL DATE/TIME: 02-19-2020 05:40
					if($headerStart == 1){
						if((stripos($line,'NAME:') !== false || stripos($line,'NAME :') !== false)){
							$field_name = 'NAME:';
							//$field_val = trim(str_ireplace('Name:','',$item));
							/*$nextKey = $key+1;
							$field_val = '';
							if (array_key_exists($nextKey,$elements)){
								$field_val = trim($elements[$nextKey]);
							}*/
							$elements = explode("\t",$line);
							$field_val = isset($elements[0]) ? trim($elements[0]) : '';
							$field_val = trim(str_ireplace('NAME:','',$field_val));
							$field_val = trim(str_ireplace('NAME :','',$field_val));
							//$tempArr = array('key' => $field_name, 'value' => $field_val);
							//array_push($responseArray['rpt_header'], $tempArr);
							//////////////////////////////////////////								
							$pat_name = explode(' ',$field_val); //NAME: (Fname Mname Lname)					
							if(!empty($pat_name) && count($pat_name) > 0){
								if(count($pat_name) > 1){
									if(count($pat_name) > 3){
										$fname = trim($pat_name[0]);
										//$mname = $pat_name[1];
										$lname = trim($pat_name[2]);
									}
									else if(count($pat_name) == 3){
										$fname = trim($pat_name[0]);
										//$mname = $pat_name[1];
										$lname = trim($pat_name[2]);
									}
									else{
										$fname = trim($pat_name[0]);										
										$lname = trim($pat_name[1]);
									}
									
								}
								else{ //when only one part
									$lname = trim($pat_name[0]);
									$fname = trim($pat_name[0]);
								}
								$tempArr = array('key' => 'first_name', 'value' => $fname);
								array_push($responseArray['rpt_header'], $tempArr);
								$tempArr = array('key' => 'last_name', 'value' => $lname);
								array_push($responseArray['rpt_header'], $tempArr);
							}
							else{
								$tempArr = array('key' => 'first_name', 'value' => '');
								array_push($responseArray['rpt_header'], $tempArr);
								$tempArr = array('key' => 'last_name', 'value' => '');
								array_push($responseArray['rpt_header'], $tempArr);
							}
						}						
						else if(stripos($line,'MRN') !== false && stripos($line,'HAR') !== false && stripos($line,'RACE') !== false && stripos($line,'SEX') !== false){	
							
							$posHAR = stripos($line,'HAR');
							$posSex = stripos($line,'SEX');
							$posRace = stripos($line,'RACE');
							
							$field_name = 'MRN:';
							$field_val = substr($line,0,$posHAR);
							$field_val = trim(str_ireplace('MRN','',$field_val));
							$field_val = trim(str_ireplace(':','',$field_val));	
							$field_val = trim(str_ireplace(';','',$field_val));
							$field_val = trim(str_ireplace('.','',$field_val));						
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
							////////////////////////////////////////////////////////
							$field_name = 'HAR:';
							$field_val = substr($line,$posHAR,($posSex - $posHAR));
							$field_val = trim(str_ireplace('HAR:','',$field_val));
							$field_val = trim(str_ireplace('HAR','',$field_val));
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
							/////////////////////////////////////////////////////////
							$field_name = 'RACE:';
							$field_val = substr($line,$posRace);
							$field_val = trim(str_ireplace('RACE:','',$field_val));
							$field_val = trim(str_ireplace('RACE','',$field_val));
							/*$nextKey = $key+1;
							$field_val = '';
							if (array_key_exists($nextKey,$elements)){
								$field_val = trim($elements[$nextKey]);
							}*/
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
							
						}						
						else if(stripos($line,'CSN') !== false && stripos($line,'PAT TYPE') !== false && stripos($line,'DOB') !== false){
							$posPatType = stripos($line,'PAT TYPE');
							$posDOB = stripos($line,'DOB');
							
							$field_name = 'CSN:';
							$field_val = substr($line,0,$posPatType);
							$field_val = trim(str_ireplace('CSN','',$field_val));
							$field_val = trim(str_ireplace(':','',$field_val));
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
							//////////////////////////////////////////
							$field_name = 'PAT TYPE:';
							$field_val = substr($line,$posPatType,($posDOB - $posPatType));
							$field_val = trim(str_ireplace('PAT TYPE','',$field_val));
							$field_val = trim(str_ireplace(':','',$field_val));
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
							///////////////////////////////////////////////////
							$field_name = 'dob';
							$field_val = substr($line,$posDOB);
							$field_val = trim(str_ireplace('DOB','',$field_val));
							$field_val = trim(str_ireplace(':','',$field_val));
							$field_val = trim(str_ireplace('(','',$field_val));
							$field_val = trim(str_ireplace(')','',$field_val));
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
						}						
						else if(stripos($line,'ADM DATE') !== false && stripos($line,'DICTATED') !== false){
							$posDictated = stripos($line,'DICTATED');
							$field_val = substr($line,0,$posDictated);
							
							$field_name = 'ADM DATE:';							
							$field_val = trim(str_ireplace('ADM DATE','',$field_val));
							$field_val = trim(str_ireplace(':','',$field_val));
							/*$nextKey = $key+1;
							$field_val = '';
							if (array_key_exists($nextKey,$elements)){
								$field_val = trim($elements[$nextKey]);
							}*/
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else if(stripos($line,'ADM DATE') !== false && stripos($line,'DIS DATE') !== false){
							//When Type = Radiological Examination
							$posDis = stripos($line,'DIS DATE');
							$field_val = substr($line,0,$posDis);
							
							$field_name = 'ADM DATE:';							
							$field_val = trim(str_ireplace('ADM DATE','',$field_val));
							$field_val = trim(str_ireplace(':','',$field_val));
							/*$nextKey = $key+1;
							$field_val = '';
							if (array_key_exists($nextKey,$elements)){
								$field_val = trim($elements[$nextKey]);
							}*/
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
							/////////////////////////////////////
							$field_val = substr($line,$posDis);
							$field_name = 'DIS DATE:';
							$field_val = trim(str_ireplace('DIS DATE','',$field_val));
							$field_val = trim(str_ireplace(':','',$field_val));
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
							
						}
						else if(stripos($line,'DIS DATE') !== false && stripos($line,'DICT PHY') !== false){
							$posDicPhy = stripos($line,'DICT PHY');
							$field_val = substr($line,0,$posDicPhy);
							
							$field_name = 'DIS DATE:';
							$field_val = trim(str_ireplace('DIS DATE','',$field_val));
							$field_val = trim(str_ireplace(':','',$field_val));
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
							///////////////////////////////
							$field_val = substr($line,$posDicPhy);
							$field_name = 'DICT PHY:';
							$field_val = trim(str_ireplace('DICT PHY','',$field_val));
							$field_val = trim(str_ireplace(':','',$field_val));
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else if(stripos($line,'ORD PHY') !== false && stripos($line,'DICT PHY') !== false){
							//When Type = Radiological Examination
							$posDicPhy = stripos($line,'DICT PHY');
							$field_val = substr($line,0,$posDicPhy);
							
							$field_name = 'ORD PHY:';
							$field_val = trim(str_ireplace('ORD PHY','',$field_val));
							$field_val = trim(str_ireplace(':','',$field_val));
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
							////////////////////////////////////////
							$field_val = substr($line,$posDicPhy);
							$field_name = 'DICT PHY:';
							$field_val = trim(str_ireplace('DICT PHY','',$field_val));
							$field_val = trim(str_ireplace(':','',$field_val));
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else if(stripos($line,'TYPE:') !== false || stripos($line,'TYPE :') !== false){							
							$field_name = 'TYPE:';
							$field_val = trim(str_ireplace('TYPE','',$line));
							$field_val = trim(str_ireplace(':','',$field_val));
							////////////////////////////////////////////////
							/*$shortest = -1;
							$input = $field_val; 
							foreach ($reportTypeArr as $word) {

								// calculate the distance between the input word, and the current word
								$lev = levenshtein($input, $word);								
								// check for an exact match
								if ($lev == 0) {
							
									// closest word is this one (exact match)
									$closest = $word;
									$shortest = 0;
							
									// break out of the loop; we've found an exact match
									break;
								}								
								// if this distance is less than the next found shortest distance, OR if a next shortest word has not yet been found
								if ($lev <= $shortest || $shortest < 0) {
									// set the closest match, and shortest distance
									$closest  = $word;
									$shortest = $lev;
								}
							}
							if ($shortest == 0) {
								//echo "Exact match found: $closest<br>";
								$reportType = $closest;
							} else {
								//echo "Did you mean: $closest?<br>";
								$reportType = $closest;
							}*/
							/////////////////////////////////////////////////////								
							$reportType = $field_val;
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else if(stripos($line,'ARRIVAL DATE/TIME:') !== false || stripos($line,'ARRIVAL DATE/TIME :') !== false){							
							$field_name = 'ARRIVAL DATE/TIME:';
							$field_val = trim(str_ireplace('ARRIVAL DATE/TIME','',$line));
							$field_val = trim(str_ireplace(':','',$field_val));							
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
							
							$headerStart = 0;
						}
						else if(stripos($line,'Dictated by:') !== false){
							//When Type = Radiological Examination
							$field_name = 'Dictated by:';
							$field_val = trim(str_ireplace('Dictated by','',$line));
							$field_val = trim(str_ireplace(':','',$field_val));
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else if(stripos($line,'Procedure DATE/TIME:') !== false || stripos($line,'Procedure DATE') !== false || stripos($line,'Exam date and time:') !== false || stripos($line,'DATE:') !== false){
							$field_name = 'Procedure DATE/TIME:';
							$field_val = trim(str_ireplace('Procedure DATE/TIME:','',$line));
							$field_val = trim(str_ireplace('Exam date and time:','',$line));
							$field_val = trim(str_ireplace('DATE:','',$field_val));
							$procDateTime = $field_val;
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							//array_push($responseArray['rpt_header'], $tempArr);
							$headerStart = 0;
						}
						/*else{
							$headerStart = 0;
							$bodyStart = 1;
						}*/
						
						/*if(stripos($item,'HISTORY:') !== false 
							|| stripos($item,'TECHNIQUE :') !== false || stripos($item,'TECHNIQUE:') !== false
							|| stripos($item,'FINDINGS:') !== false							  	
							|| stripos($item,'Procedure DATE/TIME:') !== false || stripos($item,'DATE:') !== false)
							{
								$headerStart = 0;
								$bodyStart = 1;
							}*/
					}
				}
				
				if(stripos($reportContents,'BAPTIST HEALTH REHABILITATION INSTITUTE')!== false){
					$department = 'REHABILITATION';
				}
				
				if(stripos($reportContents,'ARKANSAS CARDIOLOGY LITTLE ROCK')!== false){
					$procStr ='Cardiac';
					$department = 'CARDIOLOGY';
				}
				else
				{
					foreach($docTitleArr as $objTitle)
					{
						if(in_array($objTitle,$rpt_lines)){										
							$procStr = $objTitle;
							break;// the break statement terminates only the do, for, foreach, switch, or while statement that immediately encloses it.
						}
						else{
							$myIndex = array_search_partial($rpt_lines, $objTitle);
							if($myIndex > 0){
								$procStr = $objTitle;
								break;
							}
						}
					}
				}
				
				if(!empty($reportType)){
					$faxType = $faxTypeMain = $reportType;
				}
				//echo "<br>--------------".$reportType."------------------<br>";
				if(in_array($reportType,$reportTypeArr)){
					
					//$dicatedByPos = strpos($reportStrBaptist,'Dictated by:');
					//$procedurePos = strpos($reportStrBaptist,'Procedure:'); 
					/*$dateTimePos = stripos($reportContents,'Procedure DATE/TIME:');//Some times onle 'DATE:'
					if($dateTimePos !== false){
						$procDateTime = substr($reportContents,$dateTimePos);//to the end of report
						$dateTimeArr = explode('\t',$procDateTime);
						$procDateTime = trim(str_ireplace('Procedure DATE/TIME:','',$dateTimeArr[0]));	
					}
					else{
						$dateTimePos = stripos($reportContents,'DATE:');//Some times onle 'DATE:'
						if($dateTimePos !== false){
							$procDateTime = substr($reportContents,$dateTimePos);//to the end of report
							$dateTimeArr = explode('\t',$procDateTime);
							$procDateTime = trim(str_ireplace('DATE:','',$dateTimeArr[0]));	
						}
					}*/
					
					$historyPos = stripos($reportContents,'HISTORY:');//Clinical History:
					//$preprocedurePos = strpos($reportStrBaptist,'Preprocedure:');
					//$procedurePos = strpos($reportStrBaptist,'Procedure:');//Findings
					$comparisonPos = stripos($reportContents,'COMPARISON:');//Returns FALSE if the needle was not found.
					$techniquePos = stripos($reportContents,'TECHNIQUE:');
					$qualityPos = stripos($reportContents,'Quality:');
					$findingsPos = stripos($reportContents,'FINDINGS:');//Procedure:
					$impressionPos = stripos($reportContents,'IMPRESSION:');
					if($impressionPos === false){
						$impressionPos = stripos($reportContents,'TMPRESSTON:');
					}
					//$assessmentPos = strpos($reportStrBaptist,'FINAL ASSESSMENT:');
					//$recommendationPos = strpos($reportStrBaptist,'RECOMMENDATION:');
					if($findingsPos !== false && $impressionPos !== false)
					{
						if($impressionPos > $findingsPos)
						{
							$findingsStr = substr($reportContents,$findingsPos,($impressionPos - $findingsPos));
							//$findingsStr = str_replace("$$","\n",$findingsStr);	
							$findingsStr = trim(str_ireplace('FINDINGS:','',$findingsStr));
							
							$impressionStr = substr($reportContents,$impressionPos);//to the end of report
							//$impressionStr = str_replace("$$","\n",$impressionStr);
							$impressionStr = trim(str_ireplace('IMPRESSION:','',$impressionStr));	
							
						}
						
						/*if($findingsPos && $findingsPos > 0){
							$findingsStr = substr($reportStrBaptist,$findingsPos);
							$findingsStr = str_replace("$$","\n",$findingsStr);	
						}*/
					}
					else if($findingsPos !== false && $impressionPos === false)
					{
						$findingsStr = substr($reportContents,$findingsPos);
						//$findingsStr = str_replace("$$","\n",$findingsStr);
						$findingsStr = trim(str_ireplace('FINDINGS:','',$findingsStr));	
					}
					else if($findingsPos === false && $impressionPos !== false)
					{
						$impressionStr = substr($reportContents,$impressionPos);
						//$impressionStr = str_replace("$$","\n",$impressionStr);
						$impressionStr = trim(str_ireplace('IMPRESSION:','',$impressionStr));	
					}
				}
				else
				{
					$findingsStr = $reportContents;
					$impressionStr = $findingsStr;
				}
				
				///////////////////////////////////////////////////////////////////
				
				$tempArr = array('key' => 'Procedure:', 'value' => $procStr);
				array_push($responseArray['rpt_header'], $tempArr);
								
				$tempArr = array('key' => 'Procedure DATE/TIME:', 'value' => $procDateTime);
				array_push($responseArray['rpt_header'], $tempArr);
				
				$tempArr = array('key' => 'Findings:', 'value' => $findingsStr);
				array_push($responseArray['rpt_header'], $tempArr);
								
				$tempArr = array('key' => 'Impression:', 'value' => $impressionStr);
				array_push($responseArray['rpt_header'], $tempArr);
						
				
				$line_arr = array();
				$line_arr["testName"] = 'Procedure:';
				$line_arr["value"] = $procStr;
				$line_arr["flag"] = '';
				$line_arr["Reference"] = '';				
				//$line_arr["site"] = "";
				array_push($responseArray['rpt_detail'], $line_arr);
				
				$line_arr = array();
				$line_arr["testName"] = 'Findings:';
				$line_arr["value"] = $findingsStr;
				$line_arr["flag"] = '';
				$line_arr["Reference"] = '';				
				//$line_arr["site"] = "";
				array_push($responseArray['rpt_detail'], $line_arr);
				
				$line_arr = array();
				$line_arr["testName"] = 'Impression:';
				$line_arr["value"] = $impressionStr;
				$line_arr["flag"] = '';
				$line_arr["Reference"] = '';				
				//$line_arr["site"] = "";
				array_push($responseArray['rpt_detail'], $line_arr);								
				
			}
			
		}
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	$responseArray['department'] = $department;
	
	$tempArr = array('key' => 'department:', 'value' => $department);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	//print "<pre>";print_r($responseArray);print "</pre>";exit;
	return $responseArray;
}

function isDate($value) {
	if (!$value) {
		return false;
	} else {
		$date = date_parse($value); 
		//date_parse - Returns associative array with detailed info about given date/time
		/*Array
		(
			[year] => 2006
			[month] => 12
			[day] => 12
			[hour] => 10
			[minute] => 0
			[second] => 0
			[fraction] => 0.5
			[warning_count] => 0
			[warnings] => Array()
			[error_count] => 0
			[errors] => Array()
			[is_localtime] => 
		)*/
		if($date['error_count'] == 0 && $date['warning_count'] == 0){
			return checkdate($date['month'], $date['day'], $date['year']);
		} else {
			return false;
		}
	}
}

function baptistLabReportHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='', $responseArray = array())
{
	
	$headerContents = $reportContents;
	
	if(stripos($headerContents,'BHIN') !== false){
		$posEnd = stripos($headerContents,'BHIN');
		$headerContents = substr($headerContents,0,$posEnd);
		
		
	
	}
	else if(stripos($headerContents,'DIAGNOSIS') !== false){
		$posEnd = stripos($headerContents,'DIAGNOSIS');
		$headerContents = substr($headerContents,0,$posEnd);
		
	}
	
	$rpt_lines = explode("\n",$headerContents);
	
	
	foreach($rpt_lines as $key => $line)
	{
		
		
		if(stripos($line,'NAME:') !== false && stripos($line,'UNIT:') !== false && stripos($line,'PRINT:') !== false){
			$field_name = 'NAME:';
			
			$elements = explode("\t",$line);
			$field_val = isset($elements[0]) ? trim($elements[0]) : '';	
			$field_val = trim(str_ireplace('NAME:','',$field_val));
			$field_val = trim(str_ireplace('NAME :','',$field_val));
			
			//////////////////////////////////////////								
			$pat_name = explode(',',$field_val); 				
			if(!empty($pat_name) && count($pat_name) > 0){
				$firstName = isset($pat_name[1]) ? trim($pat_name[1]) : ''; 							
				if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
					$firstNameArr = explode(' ',$firstName);
					$firstName = $firstNameArr[0];
				}
				$tempArr = array('key' => 'first_name', 'value' => $firstName);
				//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
				array_push($responseArray['rpt_header'], $tempArr);
				
				$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
				array_push($responseArray['rpt_header'], $tempArr);
			}
			else{
				$tempArr = array('key' => 'first_name', 'value' => '');
				array_push($responseArray['rpt_header'], $tempArr);
				$tempArr = array('key' => 'last_name', 'value' => '');
				array_push($responseArray['rpt_header'], $tempArr);
			}
			
		}
		else if(stripos($line,'MPI:') !== false && stripos($line,'AGE:') !== false && stripos($line,'DOB:') !== false){
			$field_name = 'dob';
			$posDOB = stripos($line,'DOB:');
			$field_val = substr($line,$posDOB); 
			$field_val = trim(str_ireplace('DOB:','',$field_val));
			$field_val = trim(str_ireplace('DOB :','',$field_val));
			$field_val = substr($field_val,0,10);
			$tempArr = array('key' => $field_name, 'value' => $field_val);
			array_push($responseArray['rpt_header'], $tempArr);
		}
		
		
	} //end foreach
			
	
			
	return $responseArray;
}

function premierSurgeryCenterHandler($fileName='',$reportContents='', $configurationName = '',$email_addr=''){
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;
	//$responseArray['tsv_name'] = $tsv_name;
	//$responseArray['email_addr'] = $email_addr;
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	
	
	if(!empty($tempArr) && count($tempArr) > 2){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		if(isset($tempArr[2]) && is_numeric($tempArr[2])){
			$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		}
		if(isset($tempArr[3]) && is_numeric($tempArr[3])){
			$fax_data_id = isset($tempArr[3]) ? $tempArr[3] : 0;
		}
	}
	
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$faxCategory = 'Hospital';
	$faxType = $faxTypeMain = 'Procedure Note';//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$reportType = $procDateTime = $procStr = $findingsStr = $impressionStr ='';
	if(!empty($reportContents)){
		$posStart = stripos($reportContents,'PREMIER SURGERY CENTER');
		if($posStart !== false){
			$reportContents = substr($reportContents,$posStart);// To end of contents				
		}
		//GASTROENTEROLOGY , Patient Information, PREMIER SURGERY CENTER, PROCEDURE NOTE, PROCEDURES:
		$headerContents = $reportContents;
		
		
		$posStart = stripos($reportContents,'PROCEDURE NOTE');
		if($posStart !== false){
			if(stripos($reportContents,'PROCEDURES:') !== false){
				$posEnd = stripos($reportContents,'PROCEDURES:');
				$headerContents = substr($reportContents,$posStart,($posEnd - $posStart));
				
			}
			
			
			
		}
		
		$rpt_lines = explode("\n",$headerContents);
		
		foreach($rpt_lines as $key => &$line){
			//echo $key.'=>'.$line.'<br>';
			$elements = explode("\t",$line);
			
			
			if(stripos($line,'PATIENT NAME:') !== false && stripos($line,'DOB:') !== false){
				$elements = explode("\t",$line);
				$field_val = isset($elements[0]) ? trim($elements[0]) : '';	//PATIENT NAME: Britt Matthews
				
				$field_val = trim(str_ireplace('PATIENT NAME','',$field_val));				
				$field_val = trim(str_ireplace(':','',$field_val));
				
				//////////////////////////////////////////								
				$pat_name = explode(' ',$field_val); //PATIENT NAME: Britt Matthews (Fname Lname)					
				if(!empty($pat_name) && count($pat_name) > 0){
					$firstName = isset($pat_name[0]) ? trim($pat_name[0]) : '';						
					$tempArr = array('key' => 'first_name', 'value' => $firstName);
					//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
					array_push($responseArray['rpt_header'], $tempArr);
					
					$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
					array_push($responseArray['rpt_header'], $tempArr);
				}
				else{
					$tempArr = array('key' => 'first_name', 'value' => '');
					array_push($responseArray['rpt_header'], $tempArr);
					$tempArr = array('key' => 'last_name', 'value' => '');
					array_push($responseArray['rpt_header'], $tempArr);
				}
				/////////////////////////////////////////////////
				$field_name = 'dob';
				$posDOB = stripos($line,'DOB');
				$dobStr = substr($line,$posDOB); //To end of line					
				$field_val = $dobStr;
				$field_val = trim(str_ireplace(')','',$field_val));
				$field_val = trim(str_ireplace('(','',$field_val));
				$field_val = trim(str_ireplace('DOB','',$field_val));
				$field_val = trim(str_ireplace(':','',$field_val));
				//$field_val = substr($field_val,0,10);//DOB: 09/01/1977
				$tempArr = array('key' => $field_name, 'value' => $field_val);
				array_push($responseArray['rpt_header'], $tempArr);
			}
			
		}
		
		
		$tempArr = array('key' => 'Procedure:', 'value' => $procStr);
		array_push($responseArray['rpt_header'], $tempArr);		
		
		/*$tempArr = array('key' => 'Findings:', 'value' => $findingsStr);
		array_push($responseArray['rpt_header'], $tempArr);
						
		$tempArr = array('key' => 'Impression:', 'value' => $impressionStr);
		array_push($responseArray['rpt_header'], $tempArr);*/
	}
		
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	//print "<pre>";print_r($responseArray);print "</pre>";//exit;		
	return $responseArray;
}

//sherwoodUrgentCareHandler
function sherwoodUrgentCareHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{	
	
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;
	//$responseArray['tsv_name'] = $tsv_name;
	//$responseArray['email_addr'] = $email_addr;
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$faxCategory = 'Results';
	$faxType = $faxTypeMain = 'Radiology Report';//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$reportType = $procDateTime = $procStr = $findingsStr = $impressionStr ='';
	if(!empty($reportContents)){
		
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents));//x20 is space in hex
		if(stripos($reportContents,'Sherwood Urgent Care')!== false){
			$faxCategory = 'Urgent Care';
			$faxType = 'Sherwood Urgent Care';
			$faxTypeMain = 'Urgent Care';
			
			//HIGHLY CONFIDENTIAL.
			$posStart = 0;
			if(stripos($reportContents,'HIGHLY CONFIDENTIAL') !==false){
				$posStart = stripos($reportContents,'HIGHLY CONFIDENTIAL');
				$reportContents = substr($reportContents,$posStart);
			}
			if(stripos($reportContents,'Urgent Team') !==false){
				$posStart = stripos($reportContents,'Urgent Team');
				$reportContents = substr($reportContents,$posStart);
			}
			$posStart = 0;			
			$posEnd = stripos($reportContents,'Sherwood Urgent Care');
			$reportContents = substr($reportContents,$posStart,($posEnd - $posStart));
			
			$rpt_lines = explode("\n",$reportContents);
			
			foreach($rpt_lines as $key => $line)
			{
				echo $key.'=>'.$line.'<br>';
				$elements = explode("\t",$line);
				print "<pre>";
				print_r($elements);
				print "</pre>";
				
				if((stripos($line,'Patient:') !== false || stripos($line,'Patlent:') !== false) && stripos($line,'DOB') !== false){
					$field_name = 'NAME:';
					
					$elements = explode("\t",$line);
					$field_val = isset($elements[0]) ? trim($elements[0]) : '';	//Patlent: Luke Lucketta (DOB:3/26/2011)
					$posDOB = stripos($field_val,'DOB') - 1;
					$dobStr = substr($field_val,$posDOB); //To end of line
					$field_val = substr($field_val,0,$posDOB);
					$field_val = trim(str_ireplace('Patlent','',$field_val));
					$field_val = trim(str_ireplace('Patient','',$field_val));
					$field_val = trim(str_ireplace(':','',$field_val));
					
					//////////////////////////////////////////								
					$pat_name = explode(' ',$field_val); 				
					if(!empty($pat_name) && count($pat_name) > 0){
						$firstName = isset($pat_name[0]) ? trim($pat_name[0]) : '';						
						$tempArr = array('key' => 'first_name', 'value' => $firstName);
						//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
						
						$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
					else{
						$tempArr = array('key' => 'first_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
						$tempArr = array('key' => 'last_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
					/////////////////////////////////////////////////
					$field_name = 'dob';					
					$field_val = $dobStr;
					$field_val = trim(str_ireplace(')','',$field_val));
					$field_val = trim(str_ireplace('(','',$field_val));
					$field_val = trim(str_ireplace('DOB','',$field_val));
					$field_val = trim(str_ireplace(':','',$field_val));
					//$field_val = substr($field_val,0,10);//DOB: 09/01/1977
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);
				}							
				
			} //end foreach
			
		}
		
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	//print "<pre>";print_r($responseArray);print "</pre>";exit;		
	return $responseArray;
}

//ahhcMainHandler
function ahhcMainHandler($fileName='',$reportContents='', $configurationName = '',$email_addr=''){
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	//$responseArray['text_contents'] = $reportContents;
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Hospital';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			
			if(stripos($reportContents,'ProgressNotes')!== false){
			
				
				
				$faxType = 'Progress Note';
				$faxTypeMain = 'Cardiac';
				
				//if(stripos($reportContents,'Patient Name')!== false && stripos($reportContents,'DOB')!== false && stripos($reportContents,'Account No')!== false)
				if(stripos($reportContents,'Patient Name')!== false){
					$posStart = stripos($reportContents,'Patient Name');
					$reportContents = substr($reportContents,$posStart);//To end of contents
				}	
						
				$rpt_lines = explode("\n",$reportContents);
				foreach($rpt_lines as $key => $line)
				{					
					$elements = explode("\t",$line);				
					//Getting from header
					if(stripos($line,'Patient Name')!== false && stripos($line,'DOB')!== false && stripos($line,'Account No')!== false){
						//Lname, Fname
						////Patient Name: Randall, Roxanne, DOB: 09/05/1944, Account No: 1852983
						$posDOB = stripos($line,'DOB');
						$posAcct = stripos($line,'Account No');
						$field_val = substr($line,$posAcct);//To end of line contents
						
						$field_name = 'dob';
						$field_val = substr($line,$posDOB,($posAcct - $posDOB));
						$field_val = trim(str_ireplace('DOB','',$field_val));
						$field_val = trim(str_ireplace(';','',$field_val));
						$field_val = trim(str_ireplace(':','',$field_val));
						$field_val = trim(str_ireplace(',','',$field_val));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
						//$field_val = isset($elements[1]) ? trim($elements[1]) : ''; //Bohanan, Shavonne (ID: 1000181161), DOB: 11/25/1979
						////////////////////////////////////////////
						
						//////////////////////////////////////////////////////////////////////
						$field_val = substr($line,0,$posDOB-1);
						$field_val = trim(str_ireplace('Patient Name','',$field_val));
						$field_val = trim(str_ireplace(';','',$field_val));
						$field_val = trim(str_ireplace(':','',$field_val));
												
						$pat_name = explode(',',$field_val);//Randall, Roxanne,
						if(!empty($pat_name) && count($pat_name) > 0){
							$lastName = isset($pat_name[0]) ? trim($pat_name[0]) : '';
							$firstName = isset($pat_name[1]) ? trim($pat_name[1]) : ''; 							
							if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
								$firstNameArr = explode(' ',$firstName);
								$firstName = $firstNameArr[0];
							}
							$tempArr = array('key' => 'first_name', 'value' => $firstName);
							//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$tempArr = array('key' => 'last_name', 'value' => $lastName);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						
						break;
					}
					
					
					
				}//end foreach $rpt_lines			
			}
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	
	
	return $responseArray;
}

//Radiology Associates
function rapaReportHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	
	
	$titles = array('DATE:','OATE:','COMPARISON:','COMPARISON :','HISTORY:','HISTORY :','TECHNIQUE:','TECHNIQUE :','FINDINGS:','FINDINGS :','IMPRESSION:','IMPRESSION :','CONCLUSION:','CONCLUSION :','INDICATION:','INDICATION :','RECOMMENDATION:','RECOMMENDATION :','Transcribed by','Signed by','Electronically Signed By');
	$titles2 = array('DATE','OATE','COMPARISON','HISTORY','TECHNIQUE','FINDINGS','IMPRESSION','CONCLUSION','INDICATION','RECOMMENDATION');
						
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;
	//$responseArray['tsv_name'] = $tsv_name;
	//$responseArray['email_addr'] = $email_addr;
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$faxCategory = 'Results';
	$faxType = $faxTypeMain = 'Radiology Report';//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	
	$reportType = $reportHeader = $reportBody = $procStr = $compStr = $findingsStr= $impressionStr = $dateStr = $historyStr = $techStr = $recommendStr = $conclusionStr = $indicationStr = '';
	
	$procFlag = $dateFlag = $historyFlag = $techFlag = $indFlag =$compFlag= $recommendFlag = $findingsFlag = $impFlag = $concFlag = false;
	
	if(!empty($reportContents))
	{
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents));
		$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents));//x20 is space in hex
		//echo "<br>-------------------------------------------------------------------------------------------<br>";				
		$rpt_lines = explode("\n",$reportContents);
		
		
		{	
			$patientStr = $dobStr = $acctStr = $entryDate = '';
			///////////////////////////////////////////////////////////////
			$reportHeader = 1;
			$searchStr = "Patient:";
			//$key = array_search($searchStr, $rpt_lines);
			$key = array_search_partial($rpt_lines,$searchStr);
			//echo "<br>Key: ".$key."<br>";
			if (array_key_exists($key,$rpt_lines)){
				$patientStr = $rpt_lines[$key];
				$patientPos = stripos($patientStr,'Patient:');
				$patientStr = substr($patientStr,$patientPos);//to the end of report	
				$patientStr = trim(str_ireplace('Patient:','',$patientStr));
			}
			
			$searchStr = "Patient :";
			//$key = array_search($searchStr, $rpt_lines);
			$key = array_search_partial($rpt_lines,$searchStr);
			//echo "<br>Key: ".$key."<br>";
			if (array_key_exists($key,$rpt_lines)){
				$patientStr = $rpt_lines[$key];
				$patientPos = stripos($patientStr,'Patient :');
				$patientStr = substr($patientStr,$patientPos);//to the end of report	
				$patientStr = trim(str_ireplace('Patient :','',$patientStr));
			}
			//if(!empty($patientStr))
			{				
				$field_name = 'Patient:';
				//$tempArr = array('key' => $field_name, 'value' => $field_val);
				//array_push($responseArray['rpt_header'], $tempArr);
				$pat_name = explode(' ',$patientStr);
				if(!empty($pat_name) && count($pat_name) > 0){
					
					if(count($pat_name) > 1){
						$fname = trim($pat_name[0]);										
						$lname = trim($pat_name[1]);
					}
					else{ //when only one part
						$lname = trim($pat_name[0]);
						$fname = trim($pat_name[0]);
					}
					$tempArr = array('key' => 'first_name', 'value' => $fname);
					array_push($responseArray['rpt_header'], $tempArr);
					$tempArr = array('key' => 'last_name', 'value' => $lname);
					array_push($responseArray['rpt_header'], $tempArr);
				}
				else{
					$tempArr = array('key' => 'first_name', 'value' => '');
					array_push($responseArray['rpt_header'], $tempArr);
					$tempArr = array('key' => 'last_name', 'value' => '');
					array_push($responseArray['rpt_header'], $tempArr);
				}
				////////////////////////
				
			}
			//////////////////////////////////////////////////////////////////
			$searchStr = "DOB:";
			//$key = array_search($searchStr, $rpt_lines);
			$key = array_search_partial($rpt_lines,$searchStr);
			if (array_key_exists($key,$rpt_lines)){
				$dobStr = $rpt_lines[$key];
				$dobPos = stripos($dobStr,'DOB:');
				$dobStr = substr($dobStr,$dobPos);//to the end of report	
				$dobStr = trim(str_ireplace('DOB:','',$dobStr));
			}
			
			$searchStr = "DOB :";
			//$key = array_search($searchStr, $rpt_lines);
			$key = array_search_partial($rpt_lines,$searchStr);
			if (array_key_exists($key,$rpt_lines)){
				$dobStr = $rpt_lines[$key];
				$dobPos = stripos($dobStr,'DOB :');
				$dobStr = substr($dobStr,$dobPos);//to the end of report	
				$dobStr = trim(str_ireplace('DOB :','',$dobStr));
				$dobStr = str_replace(' ','',$dobStr);
			}
			
			//$field_name = 'DOB:';
			$field_name = 'dob';			
			$tempArr = array('key' => $field_name, 'value' => $dobStr);
			array_push($responseArray['rpt_header'], $tempArr);
			
			//////////////////////////////////////////////////////////////////
			
			//////////////////////////////////////////////////////////////////
			$responseArray['fax_category'] = $faxCategory;
			$responseArray['fax_type'] = $faxTypeMain;
			
			$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
			array_push($responseArray['rpt_header'], $tempArr);
		}
	} //end if reportContents
	
	return $responseArray;
}

function walgreensPharmacyHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	
	$tempArr = array('testName' => 'pharmacy_name', 'value' => 'Walgreens Pharmacy','flag'=>'','Reference'=>'');						
	array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			if(in_array('Prescription Refill Request',$rpt_lines)){
				$faxType = 'Prescription Refill Request';
				$faxTypeMain = 'Refill Request';
				
				///////////////////////////////////////////				
				foreach($rpt_lines as $key => $line){
					if(stripos($line,'Patient:') !== false && stripos($line,'Birthdate:') !== false){
						$field_name = 'Patient:';//(FirstName Lastname)
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';	//KIMBERLY N ROBINSON						
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? $pat_name[0] : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? $pat_name[1] : '');
							if(isset($pat_name[2])){
								$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[2]) ? $pat_name[2] : '');
							}
							array_push($responseArray['rpt_header'], $tempArr);
							
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						if(isset($elements[2]) && stripos($elements[2],'Birthdate:') !== false){
							$field_name = 'dob';//Birthdate:							
							$field_val = isset($elements[2]) ? $elements[2] : '';
							$field_val = trim(str_ireplace('Birthdate:','',$field_val));						
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
						}
							
					}
					else if(stripos($line,'Prescription Information:') !== false || stripos($line,'Prescription Information :') !== false){
						$field_name = 'Prescription Information:';
					}
					else if(stripos($line,'Drug:') !== false && stripos($line,'Prescribed Qty:') !== false){
						$field_name = 'drug_name';//Drug:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
					}
				}//end foreach $rpt_lines
				
			}
			else if(in_array('Controlled Substance Second Prescription Refill Request',$rpt_lines)){
				$faxType = 'Controlled Substance Second Prescription Refill Request';
				$faxTypeMain = 'Controlled Refill Request';
				///////////////Header///////////////////////
				
				///////////////////////////////////////////
				if(stripos($reportContents,'Prescriber Information:') !== false){
					$posStart = stripos($reportContents,'Prescriber Information:');
					//$reportContents = substr($reportContents,$posStart,($posEnd-$posStart));
					$reportContents = substr($reportContents,$posStart);// To end of contents
					$rpt_lines = explode("\n",$reportContents);
				}
				foreach($rpt_lines as $key => $line){
					if(stripos($line,'Patient:') !== false && stripos($line,'Birthdate:') !== false){
						$field_name = 'Patient:';//(FirstName Lastname)
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';	//KIMBERLY N ROBINSON						
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? $pat_name[0] : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? $pat_name[1] : '');
							if(isset($pat_name[2])){
								$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[2]) ? $pat_name[2] : '');
							}
							array_push($responseArray['rpt_header'], $tempArr);
							
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						if(isset($elements[2]) && stripos($elements[2],'Birthdate:') !== false){
							$field_name = 'dob';//Birthdate:							
							$field_val = isset($elements[2]) ? $elements[2] : '';
							$field_val = trim(str_ireplace('Birthdate:','',$field_val));						
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
						}
							
					}
					else if(stripos($line,'Drug:') !== false && stripos($line,'Prescribed Qty:') !== false){
						$field_name = 'drug_name';//Drug:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
							
					}
					
					
				}//end foreach $rpt_lines
				
			}
			else if(in_array('90 Day Prescription Request',$rpt_lines)){
				$faxType = '90 Day Prescription Request';
				$faxTypeMain = '90 DAYS SUPPLY';
				///////////////Header///////////////////////
				
				///////////////////////////////////////////
				foreach($rpt_lines as $key => $line){
					if(stripos($line,'Patient:') !== false && stripos($line,'Birth Date:') !== false){
						$field_name = 'Patient:';//(FirstName Lastname)
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';	//KIMBERLY N ROBINSON						
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? $pat_name[0] : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? $pat_name[1] : '');
							if(isset($pat_name[2])){
								$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[2]) ? $pat_name[2] : '');
							}
							array_push($responseArray['rpt_header'], $tempArr);
							
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						if(isset($elements[2]) && stripos($elements[2],'Birth Date:') !== false){
							$field_name = 'dob';//Birth Date:							
							$field_val = isset($elements[2]) ? $elements[2] : '';
							$field_val = trim(str_ireplace('Birth Date:','',$field_val));						
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
						}
							
					}
					
					else if(stripos($line,'Drug:') !== false){
						$field_name = 'drug_name';//Drug:						
						$field_val = trim(str_ireplace('Drug:','',$line));							
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);							
					}
					
					
				}//end foreach $rpt_lines
				
			}
			else if(in_array('Prior Authorization Needed',$rpt_lines)){
				$faxType = 'Prior Authorization Needed';
				$faxTypeMain = 'PA Request';
				///////////////Header///////////////////////
				
				///////////////////////////////////////////
				foreach($rpt_lines as $key => $line){
					if(stripos($line,'Patient:') !== false && stripos($line,'Birth Date:') !== false){
						$field_name = 'Patient:';//(FirstName Lastname)
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';	//KIMBERLY N ROBINSON						
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? $pat_name[0] : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? $pat_name[1] : '');
							if(isset($pat_name[2])){
								$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[2]) ? $pat_name[2] : '');
							}
							array_push($responseArray['rpt_header'], $tempArr);
							
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						if(isset($elements[2]) && stripos($elements[2],'Birth Date:') !== false){
							$field_name = 'dob';//Birth Date:							
							$field_val = isset($elements[2]) ? $elements[2] : '';
							$field_val = trim(str_ireplace('Birth Date:','',$field_val));						
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
						}
							
					}
					else if(stripos($line,'Drug:') !== false && stripos($line,'Qty:') !== false){
						$field_name = 'drug_name';//Drug:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
							
					}
				}//end foreach $rpt_lines
			}
			
			
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;
				
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	return $responseArray;
}

function cvsPharmacyHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	$tempArr = array('testName' => 'pharmacy_name', 'value' => 'CVS Pharmacy','flag'=>'','Reference'=>'');						
	array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	
	
	if(!empty($tempArr) && count($tempArr) > 2){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		if(isset($tempArr[2]) && is_numeric($tempArr[2])){
			$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		}
		if(isset($tempArr[3]) && is_numeric($tempArr[3])){
			$fax_data_id = isset($tempArr[3]) ? $tempArr[3] : 0;
		}
	}
	
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		/*print "<pre>";
		print_r($rpt_lines);
		print "</pre>";*/
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			
			//if(in_array('REQUEST FOR A REFILL OR NEW PRESCRIPTION',$rpt_lines))
			if(stripos($reportContents, 'REQUEST FOR A REFILL OR NEW PRESCRIPTION') !== false)
			{
				$faxType = 'REQUEST FOR A REFILL OR NEW PRESCRIPTION';
				$faxTypeMain = 'Refill Request';				
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'Name:') !== false){
						$field_name = 'Patient:';//Name:  (Lastname, FirstName)
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(',',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$firstName = isset($pat_name[1]) ? trim($pat_name[1]) : ''; 							
							if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
								$firstNameArr = explode(' ',$firstName);
								$firstName = $firstNameArr[0];
							}
							$tempArr = array('key' => 'first_name', 'value' => $firstName);
							//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						
					}
					else if(stripos($line,'DOB:') !== false){
						$field_name = 'dob';
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';// 09-15-1949						
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
					}
					
					else if(stripos($line,'Medication:') !== false){
						$field_name = 'drug_name';//Medication: //Drug:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					
					
				}//end foreach $rpt_lines
			}
			//else if(in_array('PRESCRIPTION RENEWAL REQUEST',$rpt_lines))
			else if(stripos($reportContents, 'PRESCRIPTION RENEWAL REQUEST') !== false)
			{
				$faxType = 'PRESCRIPTION RENEWAL REQUEST';
				$faxTypeMain = 'Refill Request';				
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'Patient name:') !== false){
						//$field_name = 'FOR PATIENT:';
						$field_name = 'Patient:';//Name:  (Lastname, FirstName)
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Patient name:','',$field_val));
						
						if(stripos($field_val,',') !== false){
							$pat_name = explode(',',$field_val);
						}
						else if(stripos($field_val,'.') !== false){
							$pat_name = explode('.',$field_val);
						}
						else{
							$pat_name = explode(' ',$field_val);
						}
												
						
						if(!empty($pat_name) && count($pat_name) > 0){
							$firstName = isset($pat_name[1]) ? trim($pat_name[1]) : ''; 							
							if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
								$firstNameArr = explode(' ',$firstName);
								$firstName = $firstNameArr[0];
							}
							$tempArr = array('key' => 'first_name', 'value' => $firstName);
							//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					} 
					else if(stripos($line,'Date of birth:') !== false){
						$field_name = 'dob';
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Date of birth:','',$field_val));						
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}
					else if(stripos($line,'Medication:') !== false){						
						$field_val = trim(str_ireplace('Medication:','',$line));
						$field_name = 'drug_name';//Medication: //Drug:												
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					
					
				}//end foreach $rpt_lines
			}
			//else if(in_array('PATIENT REQUESTS NEW RX',$rpt_lines))
			else if(stripos($reportContents, 'PATIENT REQUESTS NEW RX') !== false)
			{
				$faxType = 'PATIENT REQUESTS NEW RX';
				$faxTypeMain = 'New Rx Request';				
				foreach($rpt_lines as $key => $line){
					
				if(stripos($line,'PATIENT:') !== false && stripos($line,'REQUESTED PRESCRIPTION') !== false){
						//$field_name = 'FOR PATIENT:';
						$field_name = 'Patient:';
						$nextKey = $key+1;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}
						$elements = explode("\t",$field_val);
						$field_val = isset($elements[1]) ? $elements[1]: '';				
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						if(stripos($field_val,',') !== false){
							$pat_name = explode(',',$field_val);
						}
						else if(stripos($field_val,'.') !== false){
							$pat_name = explode('.',$field_val);
						}
						else{
							$pat_name = explode(' ',$field_val);
						}
						
						if(!empty($pat_name) && count($pat_name) > 0){
							$firstName = isset($pat_name[1]) ? trim($pat_name[1]) : ''; 							
							if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
								$firstNameArr = explode(' ',$firstName);
								$firstName = $firstNameArr[0];
							}
							$tempArr = array('key' => 'first_name', 'value' => $firstName);
							//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}					
					else if(stripos($line,'DOB:') !== false && stripos($line,'Drug:') !== false){
						$field_name = 'dob';
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						//////////////////////////
						$nextKey = $key+1;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}
						$field_name = 'drug_name';//Medication: //Drug:												
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					
					
				}//end foreach $rpt_lines
			}
			
			else if(stripos($reportContents, 'REQUEST FOR NEW PRESCRIPTION FOR CONTROLLED SUBSTANCE') !== false)
			{
				$faxType = 'REQUEST FOR NEW PRESCRIPTION FOR CONTROLLED SUBSTANCE';
				$faxTypeMain = 'Controlled Substance Refill Request';				
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'Name:') !== false && stripos($line,'From:') !== false){
						$field_name = 'prescriber_name';//PRESCRIBER:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
							
					} 
					else if(stripos($line,'Name:') !== false && stripos($line,'Last Fill Date:') !== false){
						//$field_name = 'FOR PATIENT:';
						$field_name = 'Patient:';
						
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';					
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						if(stripos($field_val,',') !== false){
							$pat_name = explode(',',$field_val);
						}
						else if(stripos($field_val,'.') !== false){
							$pat_name = explode('.',$field_val);
						}
						else{
							$pat_name = explode(' ',$field_val);
						}
						
						if(!empty($pat_name) && count($pat_name) > 0){
							$firstName = isset($pat_name[1]) ? trim($pat_name[1]) : ''; 							
							if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
								$firstNameArr = explode(' ',$firstName);
								$firstName = $firstNameArr[0];
							}
							$tempArr = array('key' => 'first_name', 'value' => $firstName);
							//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						////////////////////////////////
						
					}					
					else if(stripos($line,'DOB:') !== false && stripos($line,'Medication:') !== false){
						$field_name = 'dob';
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';// 09-15-1949						
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						//////////////////////////
						
						$field_name = 'drug_name';//Medication: //Drug:												
						$field_val = isset($elements[2]) ? $elements[2]: '';
						$field_val = trim(str_ireplace('Medication:','',$field_val));
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					
					
				}//end foreach $rpt_lines
			}
			
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	return $responseArray;
}

function getMonthNumber($monthStr) {
	//e.g, $month='Jan' or 'January' or 'JAN' or 'JANUARY' or 'january' or 'jan'
	$m = ucfirst(strtolower(trim($monthStr)));
	switch ($m) {
		case "January":        
		case "Jan":
			$m = "01";
			break;
		case "February":
		case "Feb":
			$m = "02";
			break;
		case "March":
		case "Mar":
			$m = "03";
			break;
		case "April":
		case "Apr":
			$m = "04";
			break;
		case "May":
			$m = "05";
			break;
		case "June":
		case "Jun":
			$m = "06";
			break;
		case "July":        
		case "Jul":
			$m = "07";
			break;
		case "August":
		case "Aug":
			$m = "08";
			break;
		case "September":
		case "Sep":
			$m = "09";
			break;
		case "October":
		case "Oct":
			$m = "10";
			break;
		case "November":
		case "Nov":
			$m = "11";
			break;
		case "December":
		case "Dec":
			$m = "12";
			break;
		default:
			$m = false;
			break;
	}
	return $m;
}

function walmartPharmacyHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	
	$tempArr = array('testName' => 'pharmacy_name', 'value' => 'Walmart Pharmacy','flag'=>'','Reference'=>'');						
	array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			
		
			
			//if(in_array('Refill Authorization Request',$rpt_lines))
			if(stripos($reportContents,'Refill Authorization Request') !== false)
			{
				$faxType = 'Refill Authorization Request';
				$faxTypeMain = 'Refill Request';
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'Patient') !== false && stripos($line,'Phone') !== false){
						$field_name = 'Patient:';
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Patient:','',$field_val));						
						
						
						$pat_name = explode(',',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							
							$lname = isset($pat_name[0]) ? trim($pat_name[0]) : '';
							$lname = trim(str_ireplace('Patient.','',$lname));
							$lname = trim(str_ireplace('Patient:','',$lname));
							$lname = trim(str_ireplace('Patient,','',$lname));
							$lname = trim(str_ireplace('Patient','',$lname));
							
							$tempArr = array('key' => 'last_name', 'value' => $lname);
							array_push($responseArray['rpt_header'], $tempArr);
							
							$fname = '';
							if(count($pat_name) > 1){ 
								$fnameArr = explode(' ', trim($pat_name[1]));								
								$fname = $fnameArr[0];
							}
							
							$tempArr = array('key' => 'first_name', 'value' => $fname);
							array_push($responseArray['rpt_header'], $tempArr);
							
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						
					}
					else if(stripos($line,'DOB') !== false){
						$field_name = 'dob';
						$elements = explode("\t",$line);
						if(count($elements) > 3){
							$field_val = isset($elements[3]) ? $elements[3]: '';
							$field_val = trim(str_ireplace(':','',$field_val));
						}
						else{
							$field_val = isset($elements[2]) ? trim($elements[2]) : '';
							$field_val = trim(str_ireplace(':','',$field_val));
						}
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
					}
					
					else if(stripos($line,'Pres Drug:') !== false){
						$field_name = 'drug_name';//Medication: //Drug:						
						$field_val = trim(str_ireplace('Pres Drug:','',$line));												
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					
				}//end foreach $rpt_lines
			}
			
			else if(stripos($reportContents, 'Prior Authorization Request') !== false)
			{
				$faxType = 'Prior Authorization Request';
				$faxTypeMain = 'PA Request';
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'Patient') !== false && stripos($line,'Phone') !== false){
						$field_name = 'Patient:';
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Patient:','',$field_val));						
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(',',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$firstName = isset($pat_name[1]) ? trim($pat_name[1]) : ''; 							
							if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
								$firstNameArr = explode(' ',$firstName);
								$firstName = $firstNameArr[0];
							}
							$tempArr = array('key' => 'first_name', 'value' => $firstName);
							//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'DOB') !== false){
						$field_name = 'dob';
						$elements = explode("\t",$line);
						if(count($elements) >= 3){
							$field_val = isset($elements[3]) ? $elements[3]: '';						
						}
						else{
							$field_val = isset($elements[2]) ? trim($elements[2]) : '';						
						}
						$field_val = trim(str_ireplace(':','',$field_val));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
					}	
					else if(stripos($line,'Pres Drug') !== false){
						$field_name = 'drug_name';//Medication: //Drug:						
						$elements = explode("\t",$line);
						$field_val = trim(str_ireplace('Pres Drug','',$line));
						$field_val = trim(str_ireplace(':','',$field_val));												
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					
				}//end foreach $rpt_lines
			}
			
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	return $responseArray;
}
 
function wellingtonPharmacyHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	
	$tempArr = array('testName' => 'pharmacy_name', 'value' => 'THE PHARMACY AT WELLINGTON','flag'=>'','Reference'=>'');						
	array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		$rpt_lines = explode("\n",$reportContents);
		
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			
			
			if(stripos($reportContents,'Request Refill Authorization From:') !== false)
			{
				$faxType = 'Request Refill Authorization From';
				$faxTypeMain = 'Refill Request';
				foreach($rpt_lines as $key => $line){
					
					if((stripos($line,'Date:') !== false || stripos($line,'Daie:') !== false) && stripos($line,'Medication:') !== false){
						$field_name = 'request_date';//Date:   Requested Date:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: ''; //mm/dd/yyyy	
						//$field_val = trim(str_ireplace('Date:','',$field_val));	
						if(count(explode("/",$field_val)) > 2){
							$tempArr = array('key' => $field_name, 'value' => $field_val);
						}
						else{
							$field_val = trim(str_ireplace('/','',$field_val));
							$field_val = substr($field_val,0,2).'/'.substr($field_val,2,2).'/'.substr($field_val,4);
							$tempArr = array('key' => $field_name, 'value' => $field_val);
						}
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);	
						
						$field_name = 'drug_name';//Medication: //Drug:						
						$field_val = isset($elements[2]) ? $elements[2]: '';
						$field_val = trim(str_ireplace('Medication:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
											
					}
					else if(stripos($line,'DOB:') !== false && stripos($line,'Phone:') !== false && stripos($line,'Phone:') > stripos($line,'DOB:')){
						
						$posPh = stripos($line,'Phone:');
						$phNumber = substr($line,$posPh);//to the end of line
						$dobStr = '';
						if($posPh > 0){
							$dobStr = substr($line,0,$posPh-1);
						}
						$field_name = 'dob';
						$field_val = '';
						if(!empty($dobStr)){
							$field_val = trim(str_ireplace('DOB:','',$dobStr));	
						}												
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						///////////////////////////////////////						
						$prevKey = $key - 2;
						$field_val = isset($rpt_lines[$prevKey]) ? $rpt_lines[$prevKey] : '';
						$elements = explode("\t",$field_val); 
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$lname = '';
							if(count($pat_name) > 1){
								$lname = isset($pat_name[2]) ? $pat_name[2]: $pat_name[1];
							}
							$tempArr = array('key' => 'last_name', 'value' => $lname);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					
					
				}//end foreach $rpt_lines
				
			}
			else if(stripos($reportContents,'New Controlled Rx Authorization Form:') !== false)
			{
				$faxType = 'New Controlled Rx Authorization Form';
				$faxTypeMain = 'Controlled Refill Request';
				
				$posSentBy = stripos($reportContents,'Sent By:');
				$tempContents = substr($reportContents,$posSentBy);
				
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'Patient:') !== false && stripos($line,'Patient Name:') !== false){
						
						$elements = explode("\t",$line); //FAITH E MCGILL
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Patient:','',$field_val));
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$lname = '';
							if(count($pat_name) > 1){
								$lname = isset($pat_name[2]) ? $pat_name[2]: $pat_name[1];
							}
							$tempArr = array('key' => 'last_name', 'value' => $lname);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'DOB:') !== false){
						//DOB: 5/15/1978
						$field_name = 'dob';
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';																		
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}
					else if(stripos($line,'Medication') !== false && strlen(trim($line)) > 12){//'Medication' exist two times						
						$field_name = 'drug_name';//Medication: //Drug:						
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Medication','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
																	
					}	
					
					
				}//end foreach $rpt_lines
				
				
			}
				
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	return $responseArray;
}

function burrowsPharmacyHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	echo "<h2>Burrow</h2><br>";
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	$tempArr = array('testName' => 'pharmacy_name', 'value' => 'THE PHARMACY AT WELLINGTON','flag'=>'','Reference'=>'');						
	array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		print "<pre>";
		print_r($rpt_lines);
		print "</pre>";
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			
			/*foreach($rpt_lines as $key => $line){
				
				echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
				$elements = explode("\t",$line);
				
				print "<pre>";
				print_r($elements);
				print "</pre>";
			}*/
			
			//if(in_array('Request Refill Authorization From',$rpt_lines))
			if(stripos($reportContents,'Request Refill Authorization From:') !== false)
			{
				$faxType = 'Request Refill Authorization From';
				$faxTypeMain = 'Refill Request';
				foreach($rpt_lines as $key => $line){
					
					if((stripos($line,'Date:') !== false || stripos($line,'Daie:') !== false) && stripos($line,'Medication:') !== false){
						$field_name = 'request_date';//Date:   Requested Date:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: ''; //mm/dd/yyyy	
						//$field_val = trim(str_ireplace('Date:','',$field_val));	
						if(count(explode("/",$field_val)) > 2){
							$tempArr = array('key' => $field_name, 'value' => $field_val);
						}
						else{
							$field_val = trim(str_ireplace('/','',$field_val));
							$field_val = substr($field_val,0,2).'/'.substr($field_val,2,2).'/'.substr($field_val,4);
							$tempArr = array('key' => $field_name, 'value' => $field_val);
						}
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);	
						
						$field_name = 'drug_name';//Medication: //Drug:						
						$field_val = isset($elements[2]) ? $elements[2]: '';
						$field_val = trim(str_ireplace('Medication:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
											
					}
					else if(stripos($line,'Qty Written:') !== false){
						$field_name = 'qty_prescribed';//Qty. Prescribed:  //Prescribed Qty:
						$elements = explode("\t",$line);
						$field_val = '';
						foreach($elements as $item){
							if(stripos($item,'Qty Written:') !== false){
								$field_val = trim(str_ireplace('Qty Written:','',$item));
							}
						}
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}					
					else if(stripos($line,'Rx:') !== false && (stripos($line,'Date Wile:') !== false || stripos($line,'Date Written:') !== false) && stripos($line,'Last Filled:') !== false){
						$elements = explode("\t",$line);
						foreach($elements as $item){
							if(stripos($item,'Rx:') !== false){
								$field_name = 'rx_number';//Rx Number:
								$field_val = trim(str_ireplace('Rx:','',$item));						
								$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
								array_push($responseArray['rpt_detail'], $tempArr);
							}
							else if(stripos($item,'Date Wile:') !== false || stripos($item,'Date Written:') !== false){
								$field_name = 'date_written';//Date Written:
								$field_val = trim(str_ireplace('Date Wile:','',$item));	
								$field_val = trim(str_ireplace('Date Written:','',$field_val));					
								$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
								array_push($responseArray['rpt_detail'], $tempArr);	
							}
							
						}
					}
					else if(stripos($line,'Last Filled:') !== false && stripos($line,'Directions:') !== false){
						$elements = explode("\t",$line);
						$field_name = 'date_last_filled';//Date Last Filled:   //Last Filled:
						$field_val = '';
						if(isset($elements[1])){																					
							$field_val = trim($elements[1]);													
						}
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
					}
					//Dispensed 6\ttime(s) for a total Qty of 180.000
					else if(stripos($line,'Dispensed') !== false && stripos($line,'total Qty') !== false){
						$elements = explode("\t",$line);
						$field_name = 'sig';//SIG:
						$field_val = isset($elements[2]) ? $elements[2]: '';						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
					}
					else if(stripos($line,'Refills Originally Authorized:') !== false){
						$field_name = 'refills';//Prescribed Refills:						
						$field_val = trim(str_ireplace('Refills Originally Authorized:','',$line));												
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					/*else if(stripos($line,'Plus') !== false && stripos($line,'Refills') !== false && stripos($line,'Date:') !== false){
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';//Fname Lname
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'Change Directions:') !== false){
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';//Fname Lname
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}*/
					else if(stripos($line,'DOB:') !== false && stripos($line,'Phone:') !== false && stripos($line,'Phone:') > stripos($line,'DOB:')){
						//DOB: 10/07/1955 Phone: (501) 943-2165
						$posPh = stripos($line,'Phone:');
						$phNumber = substr($line,$posPh);//to the end of line
						$dobStr = '';
						if($posPh > 0){
							$dobStr = substr($line,0,$posPh-1);
						}
						$field_name = 'dob';
						$field_val = '';
						if(!empty($dobStr)){
							$field_val = trim(str_ireplace('DOB:','',$dobStr));	
						}												
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						///////////////////////////////////////						
						$prevKey = $key - 2;
						$field_val = isset($rpt_lines[$prevKey]) ? $rpt_lines[$prevKey] : '';
						$elements = explode("\t",$field_val); //KIMBERLY THROGMORTON
						$field_val = isset($elements[0]) ? $elements[0]: '';//Fname Lname
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$lname = '';
							if(count($pat_name) > 1){
								$lname = isset($pat_name[2]) ? $pat_name[2]: $pat_name[1];
							}
							$tempArr = array('key' => 'last_name', 'value' => $lname);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					/*
					else if(stripos($line,'PRESCRIBER:') !== false || stripos($line,'PRESCRIBER :') !== false){
						$field_name = 'PRESCRIBER:';
					}
					else if(stripos($line,'Name:') !== false && stripos($line,'From:') !== false){
						$field_name = 'prescriber_name';//PRESCRIBER:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
							
					} //Store # 17611
					else if(stripos($line,'FOR PATIENT:') !== false || stripos($line,'FOR PATIENT :') !== false){
						$field_name = 'FOR PATIENT:';
					}														
					else if(stripos($line,'Pharmacy Comments:') !== false){
						$field_name = 'pharmacy_comments';//Pharmacy Comments:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);							
					}*/
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
				
			}
			else if(stripos($reportContents,'New Controlled Rx Authorization Form:') !== false)
			{
				$faxType = 'New Controlled Rx Authorization Form';
				$faxTypeMain = 'Controlled Refill Request';
				
				$posSentBy = stripos($reportContents,'Sent By:');
				$tempContents = substr($reportContents,$posSentBy);// Taken from Sent By to end of report
				
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'Patient:') !== false && stripos($line,'Patient Name:') !== false){
						
						$elements = explode("\t",$line); //FAITH E MCGILL
						$field_val = isset($elements[0]) ? $elements[0]: '';//Fname Mid Lname
						$field_val = trim(str_ireplace('Patient:','',$field_val));
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$lname = '';
							if(count($pat_name) > 1){
								$lname = isset($pat_name[2]) ? $pat_name[2]: $pat_name[1];
							}
							$tempArr = array('key' => 'last_name', 'value' => $lname);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'DOB:') !== false){
						//DOB: 5/15/1978
						$field_name = 'dob';
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';																		
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}
					else if(stripos($line,'Rx:') !== false){
						$field_name = 'rx_number';//Rx Number:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					else if(stripos($line,'Medication') !== false && strlen(trim($line)) > 12){//'Medication' exist two times						
						$field_name = 'drug_name';//Medication: //Drug:						
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Medication','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						///////////////////////////////////////////////////////
						$field_name = 'qty_prescribed';//Qty. Prescribed:  //Prescribed Qty:
						$nextKey = $key+1;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
							$elements = explode("\t",$field_val);
							$field_val = isset($elements[0]) ? $elements[0]: '';
							$field_val = trim(str_ireplace('Qty Written:','',$field_val));
						}
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
																	
					}	
					else if(stripos($line,'Refills Originally Authorized:') !== false){
						$field_name = 'refills';//Prescribed Refills:
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';						
						$field_val = trim(str_ireplace('Refills Originally Authorized:','',$field_val));												
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}	
					/*else if(stripos($line,'Directions:') !== false && stripos($line,'Directions:') > stripos($line,'Refills Originally Authorized:')){
						$field_name = 'sig';//SIG:
						$field_val = trim(str_ireplace('Directions:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
					}*/	
					else if(stripos($line,'Last Filled:') !== false && stripos($line,'Date Written:') !== false){
						$elements = explode("\t",$line);
						$field_name = 'date_last_filled';//Date Last Filled:   //Last Filled:
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						////////////////////////////
						$field_name = 'date_written';//Date Last Filled:   //Last Filled:
						$field_val = isset($elements[2]) ? $elements[2]: '';	
						$field_val = trim(str_ireplace('Date Written:','',$field_val));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
					}		
					/*else if(stripos($line,'Date:') !== false && stripos($line,'Date:') > stripos($line,'Sent By:')){
						$field_name = 'request_date';//Date:   Requested Date:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: ''; //Date:	01/22/2020 8:09 AM	
						//$field_val = trim(str_ireplace('Date:','',$field_val));					
						if(count(explode("/",$field_val)) > 2){
							$tempArr = array('key' => $field_name, 'value' => $field_val);
						}
						else{
							$field_val = trim(str_ireplace('/','',$field_val));
							$field_val = substr($field_val,0,2).'/'.substr($field_val,2,2).'/'.substr($field_val,4);
							$tempArr = array('key' => $field_name, 'value' => $field_val);
						}
						array_push($responseArray['rpt_header'], $tempArr);		
											
					}*/
										
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
				/////////////////////////////////////////
				$posRefill = stripos($reportContents,'Refills Originally Authorized:');
				$reportContents = substr($reportContents,$posRefill);
				$rpt_lines = explode("\n",$reportContents);
				foreach($rpt_lines as $key => $line){
					if(stripos($line,'Directions:') !== false ){
						$field_name = 'sig';//SIG:
						$field_val = trim(str_ireplace('Directions:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
						break;
					}
				}
				////////////////////////////////////////////
				$rpt_lines = explode("\n",$tempContents);
				$line = isset($rpt_lines[1]) ? trim($rpt_lines[1]) : '';
				if(stripos($line,'Date:') !== false){
					$field_name = 'request_date';//Date:   Requested Date:
					$elements = explode("\t",$line);
					$field_val = isset($elements[1]) ? $elements[1]: ''; //Date:	01/22/2020 8:09 AM	
					//$field_val = trim(str_ireplace('Date:','',$field_val));					
					if(count(explode("/",$field_val)) > 2){
						$tempArr = array('key' => $field_name, 'value' => $field_val);
					}
					else{
						$field_val = trim(str_ireplace('/','',$field_val));
						$field_val = substr($field_val,0,2).'/'.substr($field_val,2,2).'/'.substr($field_val,4);
						$tempArr = array('key' => $field_name, 'value' => $field_val);
					}
					array_push($responseArray['rpt_header'], $tempArr);		
										
				}
				
			}
			else if(in_array('Prior Authorization Request',$rpt_lines)){
				$faxType = 'Prior Authorization Request';
				$faxTypeMain = 'PA Request';
			}			
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	//print "<pre>";print_r($responseArray);print "</pre>";exit;
	return $responseArray;
}

function eastEndPharmacyHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	//echo "<h2>East End</h2><br>";
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	$tempArr = array('testName' => 'pharmacy_name', 'value' => 'East End','flag'=>'','Reference'=>'');						
	array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		print "<pre>";
		print_r($rpt_lines);
		print "</pre>";
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			
			/*foreach($rpt_lines as $key => $line){
				
				echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
				$elements = explode("\t",$line);
				
				print "<pre>";
				print_r($elements);
				print "</pre>";
			}*/
			
			//if(in_array('Request Refill Authorization From',$rpt_lines))
			if(stripos($reportContents,'Request Refill Authorization From:') !== false)
			{
				$faxType = 'Request Refill Authorization From';
				$faxTypeMain = 'Refill Request';
				foreach($rpt_lines as $key => $line){
					
					if((stripos($line,'Date:') !== false || stripos($line,'Daie:') !== false) && stripos($line,'Medication:') !== false){
						$field_name = 'request_date';//Date:   Requested Date:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: ''; //mm/dd/yyyy	
						//$field_val = trim(str_ireplace('Date:','',$field_val));	
						if(count(explode("/",$field_val)) > 2){
							$tempArr = array('key' => $field_name, 'value' => $field_val);
						}
						else{
							$field_val = trim(str_ireplace('/','',$field_val));
							$field_val = substr($field_val,0,2).'/'.substr($field_val,2,2).'/'.substr($field_val,4);
							$tempArr = array('key' => $field_name, 'value' => $field_val);
						}
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);	
						
						$field_name = 'drug_name';//Medication: //Drug:						
						$field_val = isset($elements[2]) ? $elements[2]: '';
						$field_val = trim(str_ireplace('Medication:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
											
					}
					else if(stripos($line,'Qty Written:') !== false){
						$field_name = 'qty_prescribed';//Qty. Prescribed:  //Prescribed Qty:
						$elements = explode("\t",$line);
						$field_val = '';
						foreach($elements as $item){
							if(stripos($item,'Qty Written:') !== false){
								$field_val = trim(str_ireplace('Qty Written:','',$item));
							}
						}
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}					
					else if(stripos($line,'Rx:') !== false && (stripos($line,'Date Wile:') !== false || stripos($line,'Date Written:') !== false) && stripos($line,'Last Filled:') !== false){
						$elements = explode("\t",$line);
						foreach($elements as $item){
							if(stripos($item,'Rx:') !== false){
								$field_name = 'rx_number';//Rx Number:
								$field_val = trim(str_ireplace('Rx:','',$item));						
								$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
								array_push($responseArray['rpt_detail'], $tempArr);
							}
							else if(stripos($item,'Date Wile:') !== false || stripos($item,'Date Written:') !== false){
								$field_name = 'date_written';//Date Written:
								$field_val = trim(str_ireplace('Date Wile:','',$item));	
								$field_val = trim(str_ireplace('Date Written:','',$field_val));					
								$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
								array_push($responseArray['rpt_detail'], $tempArr);	
							}
							
						}
					}
					else if(stripos($line,'Last Filled:') !== false && stripos($line,'Directions:') !== false){
						$elements = explode("\t",$line);
						$field_name = 'date_last_filled';//Date Last Filled:   //Last Filled:
						$field_val = '';
						if(isset($elements[1])){																					
							$field_val = trim($elements[1]);													
						}
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
					}
					//Dispensed 6\ttime(s) for a total Qty of 180.000
					else if(stripos($line,'Dispensed') !== false && stripos($line,'total Qty') !== false){
						$elements = explode("\t",$line);
						$field_name = 'sig';//SIG:
						$field_val = isset($elements[2]) ? $elements[2]: '';						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
					}
					else if(stripos($line,'Refills Originally Authorized:') !== false){
						$field_name = 'refills';//Prescribed Refills:						
						$field_val = trim(str_ireplace('Refills Originally Authorized:','',$line));												
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					/*else if(stripos($line,'Plus') !== false && stripos($line,'Refills') !== false && stripos($line,'Date:') !== false){
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';//Fname Lname
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'Change Directions:') !== false){
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';//Fname Lname
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}*/
					else if(stripos($line,'DOB:') !== false && stripos($line,'Phone:') !== false && stripos($line,'Phone:') > stripos($line,'DOB:')){
						//DOB: 10/07/1955 Phone: (501) 943-2165
						$posPh = stripos($line,'Phone:');
						$phNumber = substr($line,$posPh);//to the end of line
						$dobStr = '';
						if($posPh > 0){
							$dobStr = substr($line,0,$posPh-1);
						}
						$field_name = 'dob';
						$field_val = '';
						if(!empty($dobStr)){
							$field_val = trim(str_ireplace('DOB:','',$dobStr));	
						}												
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						///////////////////////////////////////						
						$prevKey = $key - 2;
						$field_val = isset($rpt_lines[$prevKey]) ? $rpt_lines[$prevKey] : '';
						$elements = explode("\t",$field_val); //KIMBERLY THROGMORTON
						$field_val = isset($elements[0]) ? $elements[0]: '';//Fname Lname
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$lname = '';
							if(count($pat_name) > 1){
								$lname = isset($pat_name[2]) ? $pat_name[2]: $pat_name[1];
							}
							$tempArr = array('key' => 'last_name', 'value' => $lname);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					/*
					else if(stripos($line,'PRESCRIBER:') !== false || stripos($line,'PRESCRIBER :') !== false){
						$field_name = 'PRESCRIBER:';
					}
					else if(stripos($line,'Name:') !== false && stripos($line,'From:') !== false){
						$field_name = 'prescriber_name';//PRESCRIBER:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
							
					} //Store # 17611
					else if(stripos($line,'FOR PATIENT:') !== false || stripos($line,'FOR PATIENT :') !== false){
						$field_name = 'FOR PATIENT:';
					}														
					else if(stripos($line,'Pharmacy Comments:') !== false){
						$field_name = 'pharmacy_comments';//Pharmacy Comments:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);							
					}*/
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
				
			}
			else if(stripos($reportContents,'New Controlled Rx Authorization Form:') !== false)
			{
				$faxType = 'New Controlled Rx Authorization Form';
				$faxTypeMain = 'Controlled Refill Request';
				
				$posSentBy = stripos($reportContents,'Sent By:');
				$tempContents = substr($reportContents,$posSentBy);// Taken from Sent By to end of report
				
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'Patient:') !== false && stripos($line,'Patient Name:') !== false){
						
						$elements = explode("\t",$line); //FAITH E MCGILL
						$field_val = isset($elements[0]) ? $elements[0]: '';//Fname Mid Lname
						$field_val = trim(str_ireplace('Patient:','',$field_val));
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$lname = '';
							if(count($pat_name) > 1){
								$lname = isset($pat_name[2]) ? $pat_name[2]: $pat_name[1];
							}
							$tempArr = array('key' => 'last_name', 'value' => $lname);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'DOB:') !== false){
						//DOB: 5/15/1978
						$field_name = 'dob';
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';																		
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}
					else if(stripos($line,'Rx:') !== false){
						$field_name = 'rx_number';//Rx Number:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					else if(stripos($line,'Medication') !== false && strlen(trim($line)) > 12){//'Medication' exist two times						
						$field_name = 'drug_name';//Medication: //Drug:						
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Medication','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						///////////////////////////////////////////////////////
						$field_name = 'qty_prescribed';//Qty. Prescribed:  //Prescribed Qty:
						$nextKey = $key+1;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
							$elements = explode("\t",$field_val);
							$field_val = isset($elements[0]) ? $elements[0]: '';
							$field_val = trim(str_ireplace('Qty Written:','',$field_val));
						}
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
																	
					}	
					else if(stripos($line,'Refills Originally Authorized:') !== false){
						$field_name = 'refills';//Prescribed Refills:
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';						
						$field_val = trim(str_ireplace('Refills Originally Authorized:','',$field_val));												
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}	
					/*else if(stripos($line,'Directions:') !== false && stripos($line,'Directions:') > stripos($line,'Refills Originally Authorized:')){
						$field_name = 'sig';//SIG:
						$field_val = trim(str_ireplace('Directions:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
					}*/	
					else if(stripos($line,'Last Filled:') !== false && stripos($line,'Date Written:') !== false){
						$elements = explode("\t",$line);
						$field_name = 'date_last_filled';//Date Last Filled:   //Last Filled:
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						////////////////////////////
						$field_name = 'date_written';//Date Last Filled:   //Last Filled:
						$field_val = isset($elements[2]) ? $elements[2]: '';	
						$field_val = trim(str_ireplace('Date Written:','',$field_val));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
					}		
					/*else if(stripos($line,'Date:') !== false && stripos($line,'Date:') > stripos($line,'Sent By:')){
						$field_name = 'request_date';//Date:   Requested Date:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: ''; //Date:	01/22/2020 8:09 AM	
						//$field_val = trim(str_ireplace('Date:','',$field_val));					
						if(count(explode("/",$field_val)) > 2){
							$tempArr = array('key' => $field_name, 'value' => $field_val);
						}
						else{
							$field_val = trim(str_ireplace('/','',$field_val));
							$field_val = substr($field_val,0,2).'/'.substr($field_val,2,2).'/'.substr($field_val,4);
							$tempArr = array('key' => $field_name, 'value' => $field_val);
						}
						array_push($responseArray['rpt_header'], $tempArr);		
											
					}*/
										
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
				/////////////////////////////////////////
				$posRefill = stripos($reportContents,'Refills Originally Authorized:');
				$reportContents = substr($reportContents,$posRefill);
				$rpt_lines = explode("\n",$reportContents);
				foreach($rpt_lines as $key => $line){
					if(stripos($line,'Directions:') !== false ){
						$field_name = 'sig';//SIG:
						$field_val = trim(str_ireplace('Directions:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
						break;
					}
				}
				////////////////////////////////////////////
				$rpt_lines = explode("\n",$tempContents);
				$line = isset($rpt_lines[1]) ? trim($rpt_lines[1]) : '';
				if(stripos($line,'Date:') !== false){
					$field_name = 'request_date';//Date:   Requested Date:
					$elements = explode("\t",$line);
					$field_val = isset($elements[1]) ? $elements[1]: ''; //Date:	01/22/2020 8:09 AM	
					//$field_val = trim(str_ireplace('Date:','',$field_val));					
					if(count(explode("/",$field_val)) > 2){
						$tempArr = array('key' => $field_name, 'value' => $field_val);
					}
					else{
						$field_val = trim(str_ireplace('/','',$field_val));
						$field_val = substr($field_val,0,2).'/'.substr($field_val,2,2).'/'.substr($field_val,4);
						$tempArr = array('key' => $field_name, 'value' => $field_val);
					}
					array_push($responseArray['rpt_header'], $tempArr);		
										
				}
				
			}
			else if(in_array('Prior Authorization Request',$rpt_lines)){
				$faxType = 'Prior Authorization Request';
				$faxTypeMain = 'PA Request';
			}			
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	//print "<pre>";print_r($responseArray);print "</pre>";exit;
	return $responseArray;
}

function dailyDoseDrugStoreHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	//echo "<h2>Daily Dose DrugStore</h2><br>";
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	
	//Following item added in below ode
	//$tempArr = array('testName' => 'pharmacy_name', 'value' => 'Daily Dose DrugStore','flag'=>'','Reference'=>'');						
	//array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		print "<pre>";
		print_r($rpt_lines);
		print "</pre>";
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			
			/*foreach($rpt_lines as $key => $line){
				
				echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
				$elements = explode("\t",$line);
				
				print "<pre>";
				print_r($elements);
				print "</pre>";
			}*/
			
			//if(in_array('Request Refill Authorization From',$rpt_lines))
			if(stripos($reportContents,'Request Refill Authorization From:') !== false)
			{
				$faxType = 'Request Refill Authorization From';
				$faxTypeMain = 'Refill Request';
				foreach($rpt_lines as $key => $line){
					
					if((stripos($line,'Patient:') !== false)){
						$field_name = 'pharmacy_name';
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? trim($elements[0]): '';
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						///////////////////////
						$field_name = 'pharmacy_address';
						$nextKey = $key+1;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}												
						$elements = explode("\t",$field_val);						
						$field_val = isset($elements[0]) ? trim($elements[0]): '';
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						//////////////////////////////////
						$field_name = 'pharmacy_city';
						$nextKey = $key+2;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}												
						$elements = explode("\t",$field_val);
						$field_val = isset($elements[0]) ? trim($elements[0]): '';
						
						$elements = explode(",",$field_val);
						$field_val = isset($elements[0]) ? trim($elements[0]): '';
						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						$field_val = isset($elements[1]) ? trim($elements[1]): '';
						$elements = explode(" ",$field_val);
						$pharmacy_state = isset($elements[0]) ? trim($elements[0]): '';
						$field_name = 'pharmacy_state';
						$tempArr = array('testName' => $field_name, 'value' => $pharmacy_state,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						$pharmacy_zip = isset($elements[1]) ? trim($elements[1]): '';
						$field_name = 'pharmacy_zip';
						$tempArr = array('testName' => $field_name, 'value' => $pharmacy_zip,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					else if((stripos($line,'Date:') !== false || stripos($line,'Daie:') !== false) && stripos($line,'Medication:') !== false){
						$field_name = 'request_date';//Date:   Requested Date:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: ''; //mm/dd/yyyy	
						//$field_val = trim(str_ireplace('Date:','',$field_val));	
						if(count(explode("/",$field_val)) > 2){
							$tempArr = array('key' => $field_name, 'value' => $field_val);
						}
						else{
							$field_val = trim(str_ireplace('/','',$field_val));
							$field_val = substr($field_val,0,2).'/'.substr($field_val,2,2).'/'.substr($field_val,4);
							$tempArr = array('key' => $field_name, 'value' => $field_val);
						}
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);	
						
						$field_name = 'drug_name';//Medication: //Drug:						
						$field_val = isset($elements[2]) ? $elements[2]: '';
						$field_val = trim(str_ireplace('Medication:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
											
					}
					else if(stripos($line,'Qty Written:') !== false){
						$field_name = 'qty_prescribed';//Qty. Prescribed:  //Prescribed Qty:
						$elements = explode("\t",$line);
						$field_val = '';
						foreach($elements as $item){
							if(stripos($item,'Qty Written:') !== false){
								$field_val = trim(str_ireplace('Qty Written:','',$item));
							}
						}
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}					
					else if(stripos($line,'Rx:') !== false && (stripos($line,'Date Wile:') !== false || stripos($line,'Date Written:') !== false) && stripos($line,'Last Filled:') !== false){
						$elements = explode("\t",$line);
						foreach($elements as $item){
							if(stripos($item,'Rx:') !== false){
								$field_name = 'rx_number';//Rx Number:
								$field_val = trim(str_ireplace('Rx:','',$item));						
								$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
								array_push($responseArray['rpt_detail'], $tempArr);
							}
							else if(stripos($item,'Date Wile:') !== false || stripos($item,'Date Written:') !== false){
								$field_name = 'date_written';//Date Written:
								$field_val = trim(str_ireplace('Date Wile:','',$item));	
								$field_val = trim(str_ireplace('Date Written:','',$field_val));					
								$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
								array_push($responseArray['rpt_detail'], $tempArr);	
							}
							
						}
					}
					else if(stripos($line,'Last Filled:') !== false && stripos($line,'Directions:') !== false){
						$elements = explode("\t",$line);
						$field_name = 'date_last_filled';//Date Last Filled:   //Last Filled:
						$field_val = '';
						if(isset($elements[1])){																					
							$field_val = trim($elements[1]);													
						}
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
					}
					//Dispensed 6\ttime(s) for a total Qty of 180.000
					else if(stripos($line,'Dispensed') !== false && stripos($line,'total Qty') !== false){
						$elements = explode("\t",$line);
						$field_name = 'sig';//SIG:
						$field_val = isset($elements[2]) ? $elements[2]: '';						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
					}
					else if(stripos($line,'Refills Originally Authorized:') !== false){
						$field_name = 'refills';//Prescribed Refills:						
						$field_val = trim(str_ireplace('Refills Originally Authorized:','',$line));												
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					/*else if(stripos($line,'Plus') !== false && stripos($line,'Refills') !== false && stripos($line,'Date:') !== false){
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';//Fname Lname
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'Change Directions:') !== false){
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';//Fname Lname
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}*/
					else if(stripos($line,'DOB:') !== false && stripos($line,'Phone:') !== false && stripos($line,'Phone:') > stripos($line,'DOB:')){
						//DOB: 10/07/1955 Phone: (501) 943-2165
						$posPh = stripos($line,'Phone:');
						$phNumber = substr($line,$posPh);//to the end of line
						$dobStr = '';
						if($posPh > 0){
							$dobStr = substr($line,0,$posPh-1);
						}
						$field_name = 'dob';
						$field_val = '';
						if(!empty($dobStr)){
							$field_val = trim(str_ireplace('DOB:','',$dobStr));	
						}												
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						///////////////////////////////////////						
						$prevKey = $key - 2;
						$field_val = isset($rpt_lines[$prevKey]) ? $rpt_lines[$prevKey] : '';
						$elements = explode("\t",$field_val); //KIMBERLY THROGMORTON, HANNAH ELIZABETH BARAJAS
						$field_val = isset($elements[0]) ? $elements[0]: '';//Fname Lname
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$lname = '';
							if(count($pat_name) > 1){
								$lname = isset($pat_name[2]) ? trim($pat_name[2]): trim($pat_name[1]);
							}
							$tempArr = array('key' => 'last_name', 'value' => $lname);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					/*
					else if(stripos($line,'PRESCRIBER:') !== false || stripos($line,'PRESCRIBER :') !== false){
						$field_name = 'PRESCRIBER:';
					}
					else if(stripos($line,'Name:') !== false && stripos($line,'From:') !== false){
						$field_name = 'prescriber_name';//PRESCRIBER:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
							
					} //Store # 17611
					else if(stripos($line,'FOR PATIENT:') !== false || stripos($line,'FOR PATIENT :') !== false){
						$field_name = 'FOR PATIENT:';
					}														
					else if(stripos($line,'Pharmacy Comments:') !== false){
						$field_name = 'pharmacy_comments';//Pharmacy Comments:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);							
					}*/
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
				
			}						
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	//print "<pre>";print_r($responseArray);print "</pre>";exit;
	return $responseArray;
}

function risonPharmacyHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	//echo "<h2>Daily Dose DrugStore</h2><br>";
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	
	//$tempArr = array('testName' => 'pharmacy_name', 'value' => 'Daily Dose DrugStore','flag'=>'','Reference'=>'');						
	//array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		print "<pre>";
		print_r($rpt_lines);
		print "</pre>";
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			
			/*foreach($rpt_lines as $key => $line){
				
				echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
				$elements = explode("\t",$line);
				
				print "<pre>";
				print_r($elements);
				print "</pre>";
			}*/
			
			//if(in_array('Request Refill Authorization From',$rpt_lines))
			if(stripos($reportContents,'Request Refill Authorization From:') !== false)
			{
				$faxType = 'Request Refill Authorization From';
				$faxTypeMain = 'Refill Request';
				foreach($rpt_lines as $key => $line){
					
					if((stripos($line,'Patient:') !== false)){
						$field_name = 'pharmacy_name';
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? trim($elements[0]): '';
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						///////////////////////
						$field_name = 'pharmacy_address';
						$nextKey = $key+1;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}												
						$elements = explode("\t",$field_val);						
						$field_val = isset($elements[0]) ? trim($elements[0]): '';
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						//////////////////////////////////
						$field_name = 'pharmacy_city';
						$nextKey = $key+2;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}												
						$elements = explode("\t",$field_val);
						$field_val = isset($elements[0]) ? trim($elements[0]): '';
						
						$elements = explode(",",$field_val);
						$field_val = isset($elements[0]) ? trim($elements[0]): '';
						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						$field_val = isset($elements[1]) ? trim($elements[1]): '';
						$elements = explode(" ",$field_val);
						$pharmacy_state = isset($elements[0]) ? trim($elements[0]): '';
						$field_name = 'pharmacy_state';
						$tempArr = array('testName' => $field_name, 'value' => $pharmacy_state,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						$pharmacy_zip = isset($elements[1]) ? trim($elements[1]): '';
						$field_name = 'pharmacy_zip';
						$tempArr = array('testName' => $field_name, 'value' => $pharmacy_zip,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					else if((stripos($line,'Date:') !== false || stripos($line,'Daie:') !== false) && stripos($line,'Medication:') !== false){
						$field_name = 'request_date';//Date:   Requested Date:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: ''; //mm/dd/yyyy	
						//$field_val = trim(str_ireplace('Date:','',$field_val));	
						if(count(explode("/",$field_val)) > 2){
							$tempArr = array('key' => $field_name, 'value' => $field_val);
						}
						else{
							$field_val = trim(str_ireplace('/','',$field_val));
							$field_val = substr($field_val,0,2).'/'.substr($field_val,2,2).'/'.substr($field_val,4);
							$tempArr = array('key' => $field_name, 'value' => $field_val);
						}
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);	
						
						$field_name = 'drug_name';//Medication: //Drug:						
						$field_val = isset($elements[2]) ? $elements[2]: '';
						$field_val = trim(str_ireplace('Medication:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
											
					}
					else if(stripos($line,'Qty Written:') !== false){
						$field_name = 'qty_prescribed';//Qty. Prescribed:  //Prescribed Qty:
						$elements = explode("\t",$line);
						$field_val = '';
						foreach($elements as $item){
							if(stripos($item,'Qty Written:') !== false){
								$field_val = trim(str_ireplace('Qty Written:','',$item));
							}
						}
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}					
					else if(stripos($line,'Rx:') !== false && (stripos($line,'Date Wile:') !== false || stripos($line,'Date Written:') !== false) && stripos($line,'Last Filled:') !== false){
						$elements = explode("\t",$line);
						foreach($elements as $item){
							if(stripos($item,'Rx:') !== false){
								$field_name = 'rx_number';//Rx Number:
								$field_val = trim(str_ireplace('Rx:','',$item));						
								$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
								array_push($responseArray['rpt_detail'], $tempArr);
							}
							else if(stripos($item,'Date Wile:') !== false || stripos($item,'Date Written:') !== false){
								$field_name = 'date_written';//Date Written:
								$field_val = trim(str_ireplace('Date Wile:','',$item));	
								$field_val = trim(str_ireplace('Date Written:','',$field_val));					
								$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
								array_push($responseArray['rpt_detail'], $tempArr);	
							}
							
						}
					}
					else if(stripos($line,'Last Filled:') !== false && stripos($line,'Directions:') !== false){
						$elements = explode("\t",$line);
						$field_name = 'date_last_filled';//Date Last Filled:   //Last Filled:
						$field_val = '';
						if(isset($elements[1])){																					
							$field_val = trim($elements[1]);													
						}
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
					}
					//Dispensed 6\ttime(s) for a total Qty of 180.000
					else if(stripos($line,'Dispensed') !== false && stripos($line,'total Qty') !== false){
						$elements = explode("\t",$line);
						$field_name = 'sig';//SIG:
						$field_val = isset($elements[2]) ? $elements[2]: '';						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
					}
					else if(stripos($line,'Refills Originally Authorized:') !== false){
						$field_name = 'refills';//Prescribed Refills:						
						$field_val = trim(str_ireplace('Refills Originally Authorized:','',$line));												
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					/*else if(stripos($line,'Plus') !== false && stripos($line,'Refills') !== false && stripos($line,'Date:') !== false){
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';//Fname Lname
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'Change Directions:') !== false){
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';//Fname Lname
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}*/
					else if(stripos($line,'DOB:') !== false && stripos($line,'Phone:') !== false && stripos($line,'Phone:') > stripos($line,'DOB:')){
						//DOB: 10/07/1955 Phone: (501) 943-2165
						$posPh = stripos($line,'Phone:');
						$phNumber = substr($line,$posPh);//to the end of line
						$dobStr = '';
						if($posPh > 0){
							$dobStr = substr($line,0,$posPh-1);
						}
						$field_name = 'dob';
						$field_val = '';
						if(!empty($dobStr)){
							$field_val = trim(str_ireplace('DOB:','',$dobStr));	
						}												
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						///////////////////////////////////////						
						$prevKey = $key - 2;
						$field_val = isset($rpt_lines[$prevKey]) ? $rpt_lines[$prevKey] : '';
						$elements = explode("\t",$field_val); //KIMBERLY THROGMORTON, HANNAH ELIZABETH BARAJAS
						$field_val = isset($elements[0]) ? $elements[0]: '';//Fname Lname
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$lname = '';
							if(count($pat_name) > 1){
								$lname = isset($pat_name[2]) ? trim($pat_name[2]): trim($pat_name[1]);
							}
							$tempArr = array('key' => 'last_name', 'value' => $lname);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					/*
					else if(stripos($line,'PRESCRIBER:') !== false || stripos($line,'PRESCRIBER :') !== false){
						$field_name = 'PRESCRIBER:';
					}
					else if(stripos($line,'Name:') !== false && stripos($line,'From:') !== false){
						$field_name = 'prescriber_name';//PRESCRIBER:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
							
					} //Store # 17611
					else if(stripos($line,'FOR PATIENT:') !== false || stripos($line,'FOR PATIENT :') !== false){
						$field_name = 'FOR PATIENT:';
					}														
					else if(stripos($line,'Pharmacy Comments:') !== false){
						$field_name = 'pharmacy_comments';//Pharmacy Comments:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);							
					}*/
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
				
			}						
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	//print "<pre>";print_r($responseArray);print "</pre>";exit;
	return $responseArray;
}

//CORNERSTONE PHARMACY
function cornerstonePharmacyHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	$tempArr = array('testName' => 'pharmacy_name', 'value' => 'Cornerstone Pharmacy','flag'=>'','Reference'=>'');						
	array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		/*print "<pre>";
		print_r($rpt_lines);
		print "</pre>";*/
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			
			/*foreach($rpt_lines as $key => $line){
				
				echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
				$elements = explode("\t",$line);
				
				print "<pre>";
				print_r($elements);
				print "</pre>";
			}*/
			
			//if(in_array('Request Refill Authorization From',$rpt_lines))
			if(stripos($reportContents,'Request Refill Authorization From:') !== false)
			{
				$faxType = 'Request Refill Authorization From';
				$faxTypeMain = 'Refill Request';
				foreach($rpt_lines as $key => $line){
					if(stripos($line,'Date:') !== false && stripos($line,'Request Refill Authorization From:') !== false){
						$elements = explode("\t",$line);
						$field_val = '';
						if(isset($elements[1]) && stripos($elements[1],'Date:') !==false){
							$field_val = isset($elements[2]) ? $elements[2] : '';
						}
						
						$field_name = 'request_date';//Date:   Requested Date:											
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);	
						
						$field_val = '';
						if(isset($elements[3]) && stripos($elements[3],'Rx #:') !==false){
							$field_val = isset($elements[3]) ? $elements[3] : '';
							$field_val = trim(str_ireplace('Rx #:','',$field_val));
						}
						$field_name = 'rx_number';//Rx Number:												
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
											
					}
					else if((stripos($line,'Date:') !== false || stripos($line,'Daie:') !== false) && stripos($line,'Medication:') !== false){						
						$field_name = 'drug_name';//Medication: //Drug:
						//$elements = explode("\t",$line);												
						//$field_val = isset($elements[1]) ? $elements[1]: '';
						$posMed = stripos($line,'Medication:');
						$field_val = substr($line,$posMed);//To end of line
						$field_val = trim(str_ireplace('Medication:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
											
					}
					else if(stripos($line,'Qty Written:') !== false){
						$field_name = 'qty_prescribed';//Qty. Prescribed:  //Prescribed Qty:
						$elements = explode("\t",$line);
						$field_val = '';
						foreach($elements as $item){
							if(stripos($item,'Qty Written:') !== false){
								$field_val = trim(str_ireplace('Qty Written:','',$item));
							}
						}
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}					
					//else if(stripos($line,'Rx:') !== false && (stripos($line,'Date Wile:') !== false || stripos($line,'Date Written:') !== false) && stripos($line,'Last Filled:') !== false)
					else if(stripos($line,'Rx:') !== false && (stripos($line,'Last Filled:') !== false || stripos($line,'Last Filed:') !== false))
					{
						$elements = explode("\t",$line);
						/*$field_name = 'rx_number';//Rx Number:
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Rx:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);*/
						
						$field_val = isset($elements[1]) ? $elements[1]: '';
						$field_name = 'date_written';//Date Written:
						$field_val = trim(str_ireplace('Date Wile:','',$field_val));
						$field_val = trim(str_ireplace('Date Wien:','',$field_val));	
						$field_val = trim(str_ireplace('Date Written:','',$field_val));
						$field_val = trim(str_ireplace('Date dion','',$field_val));	
						$field_val = trim(str_ireplace(':','',$field_val));	
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);							
						
					}
					else if(stripos($line,'Last Filled:') !== false && stripos($line,'Directions:') !== false){
						$elements = explode("\t",$line);
						$field_name = 'date_last_filled';//Date Last Filled:   //Last Filled:
						$field_val = '';
						if(isset($elements[1])){																					
							$field_val = trim($elements[1]);													
						}
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
					}
					//Dispensed 6\ttime(s) for a total Qty of 180.000
					else if(stripos($line,'Dispensed') !== false && stripos($line,'total Qty') !== false){
						$elements = explode("\t",$line);
						$field_name = 'sig';//SIG:
						$field_val = isset($elements[2]) ? $elements[2]: '';						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
					}
					else if(stripos($line,'Refills Originally Authorized:') !== false){
						$field_name = 'refills';//Prescribed Refills:						
						$field_val = trim(str_ireplace('Refills Originally Authorized:','',$line));												
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					/*else if(stripos($line,'Plus') !== false && (stripos($line,'Refills') !== false || stripos($line,'Reils') !== false) && stripos($line,'Date:') !== false){
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';//Fname Lname
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'DOB:') !== false && stripos($line,'Phone:') !== false){
						//DOB: 10/07/1955 Phone: (501) 943-2165
						$posPh = stripos($line,'Phone:');
						$phNumber = substr($line,$posPh);//to the end of line
						$dobStr = '';
						if($posPh > 0){
							$dobStr = substr($line,0,$posPh-1);
						}
						$field_name = 'dob';
						$field_val = '';
						if(!empty($dobStr)){
							$field_val = trim(str_ireplace('DOB:','',$dobStr));	
						}												
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
					}*/
					else if(stripos($line,'DOB:') !== false && stripos($line,'Phone:') !== false && stripos($line,'Phone:') > stripos($line,'DOB:')){
						$posPh = stripos($line,'Phone:');
						$phNumber = substr($line,$posPh);//to the end of line
						$dobStr = '';
						if($posPh > 0){
							$dobStr = substr($line,0,$posPh-1);
						}
						$field_name = 'dob';
						$field_val = '';
						if(!empty($dobStr)){
							$field_val = trim(str_ireplace('DOB:','',$dobStr));	
						}												
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						///////////////////////////////////////						
						$prevKey = $key - 2;
						$field_val = isset($rpt_lines[$prevKey]) ? $rpt_lines[$prevKey] : '';
						$elements = explode("\t",$field_val); //KIMBERLY THROGMORTON
						$field_val = isset($elements[0]) ? $elements[0]: '';//Fname Lname
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$lname = '';
							if(count($pat_name) > 1){
								$lname = isset($pat_name[2]) ? $pat_name[2]: $pat_name[1];
							}
							$tempArr = array('key' => 'last_name', 'value' => $lname);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					/*
					else if(stripos($line,'PRESCRIBER:') !== false || stripos($line,'PRESCRIBER :') !== false){
						$field_name = 'PRESCRIBER:';
					}
					else if(stripos($line,'Name:') !== false && stripos($line,'From:') !== false){
						$field_name = 'prescriber_name';//PRESCRIBER:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
							
					} //Store # 17611
					else if(stripos($line,'FOR PATIENT:') !== false || stripos($line,'FOR PATIENT :') !== false){
						$field_name = 'FOR PATIENT:';
					}														
					else if(stripos($line,'Pharmacy Comments:') !== false){
						$field_name = 'pharmacy_comments';//Pharmacy Comments:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);							
					}*/
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
				
			}
			else if(stripos($reportContents,'New Controlled Rx Authorization Form:') !== false)
			{
				$faxType = 'New Controlled Rx Authorization Form';
				$faxTypeMain = 'Controlled Refill Request';
				foreach($rpt_lines as $key => $line){
					if(stripos($line,'Sent By:') !== false){
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0] : '';
						$field_val = trim(str_ireplace('Sent By:','',$field_val));
						///////////////////////////////////////////////////////////////////						
						$field_name = 'request_date';//Date:   Requested Date:	
						$nextKey = $key + 1;
						$field_val = isset($rpt_lines[$nextKey]) ? $rpt_lines[$nextKey] : '';
						$elements = explode("\t",$field_val);
						$field_val = isset($elements[1]) ? trim($elements[1]) : '';										
						$elements = explode(" ",$field_val);
						$field_val = $elements[0];
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);	
											
					}
					else if(stripos($line,'Patient:') !== false){
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? trim($elements[0]): '';//Fname Lname (KIMBERLY HENDERSON)
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$lname = '';
							if(count($pat_name) > 1){
								$lname = isset($pat_name[2]) ? $pat_name[2]: $pat_name[1];
							}
							$tempArr = array('key' => 'last_name', 'value' => $lname);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'DOB:') !== false){
						$field_name = 'dob';
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1] : '';																							
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
						
					}
					else if(stripos($line,'Rx:') !== false){
						$field_name = 'rx_number';
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1] : '';																							
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
						
					}					
					else if(stripos($line,'Refills Originally Authorized:') !== false){
						$field_name = 'refills';//Prescribed Refills:						
						$field_val = trim(str_ireplace('Refills Originally Authorized:','',$line));												
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
						////////////////////////////////////////////////////////////
						$field_name = 'qty_prescribed';//Qty. Prescribed:  //Prescribed Qty:
						$prevKey = $key - 1;
						$field_val = isset($rpt_lines[$prevKey]) ? $rpt_lines[$prevKey] : '';
						$elements = explode("\t",$field_val); 						
						$field_val = isset($elements[0]) ? $elements[0] : '';
						$field_val = trim(str_ireplace('Qty Written:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						//////////////////////////////////////////////////////////////////
						$field_name = 'drug_name';//Medication: //Drug:
						$prevKey = $key - 2;
						$field_val = isset($rpt_lines[$prevKey]) ? $rpt_lines[$prevKey] : '';
						//$elements = explode("\t",$field_val);
						$field_val = trim(str_ireplace('Medication','',$field_val));		
						$field_val = trim(str_ireplace('Directions:','',$field_val));				
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						////////////////////////////////////
						$field_name = 'sig';//SIG:
						$nextKey = $key + 1;
						$field_val = isset($rpt_lines[$nextKey]) ? $rpt_lines[$nextKey] : '';
						$elements = explode("\t",$field_val);
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Directions:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
											
					}					
					else if(stripos($line,'Last Filled:') !== false && stripos($line,'Date Written:') !== false){
						$elements = explode("\t",$line);
						$field_name = 'date_last_filled';//Date Last Filled:   //Last Filled:
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
						////////////////////////////////////////////////
						$field_name = 'date_written';//Date Written:
						$field_val = isset($elements[2]) ? $elements[2]: '';
						$field_val = trim(str_ireplace('Date Wile:','',$field_val));
						$field_val = trim(str_ireplace('Date Wien:','',$field_val));	
						$field_val = trim(str_ireplace('Date Written:','',$field_val));	
						$field_val = trim(str_ireplace('Date dion','',$field_val));	
						$field_val = trim(str_ireplace(':','',$field_val));			
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
				
			}
			//else if(in_array('Prior Authorization Request',$rpt_lines))
			else if(stripos($reportContents,'Prior Authorization Request') !== false)
			{
				$faxType = 'Prior Authorization Request';
				$faxTypeMain = 'PA Request';
			}			
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	return $responseArray;
}

//krogerPharmacyHandler
function krogerPharmacyHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	$tempArr = array('testName' => 'pharmacy_name', 'value' => 'Kroger Pharmacy','flag'=>'','Reference'=>'');						
	array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		/*print "<pre>";
		print_r($rpt_lines);
		print "</pre>";*/
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			//Prescription Refill Request for:
			//if(in_array('REQUEST FOR A REFILL OR NEW PRESCRIPTION',$rpt_lines))
			if(stripos($reportContents,'Prescription Refill Request for:')!== false)
			{
				$faxType = 'Prescription Refill Request for:';
				$faxTypeMain = 'Refill Request';				
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'Prescription Refill Request for:') !== false){
						$field_name = 'prescriber_name';//PRESCRIBER:
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';						
						$field_val = trim(str_ireplace('Prescription Refill Request for:','',$field_val));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
							
					}
					else if(stripos($line,'Phone:') !== false && stripos($line,'NPI:') !== false){
						$elements = explode("\t",$line);
						$field_name = 'PRESCRIBER:';
					}
					//else if(stripos($line,'This request is from:') !== false && stripos($line,'Fax Date:') !== false)
					else if(stripos($line,'Fax Date:') !== false)
					{
						$field_name = 'request_date';//Date:   Requested Date:  Fax Date: 08/04/2019
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';	
						$field_val = trim(str_ireplace('Fax Date:','',$field_val));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}	//Store # 17611
					//Rx Number: 6540200	Last Fill Date: 04/18/2019	Original Fill Date: 04/18/2019
					else if(stripos($line,'Rx Number:') !== false && stripos($line,'Last Fill Date:') !== false){
						$field_name = 'rx_number';//Rx Number:
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Rx Number:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						if(isset($elements[1]) && stripos($elements[1],'Last Fill Date:') !== false){
							$field_name = 'date_last_filled';//Date Last Filled:   //Last Filled:							
							$field_val = isset($elements[1]) ? $elements[1] : '';
							$field_val = trim(str_ireplace('Last Fill Date:','',$field_val));						
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);
						}
							
					}
					else if(stripos($line,'Patient:') !== false && stripos($line,'Date of Birth:') !== false){
						$field_name = 'Patient:';//Name:  (FirstName Lastname) 
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';//LORENZO COLLINS						
						$field_val = trim(str_ireplace('Patient:','',$field_val));
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						///////////////////////////////
						$field_name = 'dob';						
						$field_val = isset($elements[1]) ? $elements[1]: '';// 01/19/1962	
						$field_val = trim(str_ireplace('Date of Birth:','',$field_val));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}															
					/*else if(stripos($line,'Drug Prescribed:') !== false){
						$field_name = 'drug_name';//Medication: //Drug:						
						$field_val = trim(str_ireplace('Drug Prescribed:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}*/
					//else if(stripos($line,'Drug Dispensed:') !== false && stripos($line,'NDC:') !== false)
					else if(stripos($line,'Drug Dispensed:') !== false)
					{
						$field_name = 'drug_name';//Medication: //Drug:
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';	
						$field_val = trim(str_ireplace('Drug Dispensed:','',$field_val));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						$field_name = 'ndc';//Medication: //Drug:
						$field_val = isset($elements[1]) ? $elements[1]: '';	
						$field_val = trim(str_ireplace('NDC:','',$field_val));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}//Original Quantity Ordered: 90	Dispensed Quantity: 90  
					else if(stripos($line,'Original Quantity Ordered:') !== false && stripos($line,'Dispensed Quantity:') !== false){
						$field_name = 'qty_prescribed';//Qty. Prescribed:  //Prescribed Qty:
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Original Quantity Ordered:','',$field_val));							
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
						
						$field_name = 'qty_dispensed';//Qty. Prescribed:  //Prescribed Qty:						
						$field_val = isset($elements[1]) ? $elements[1]: '';
						$field_val = trim(str_ireplace('Dispensed Quantity:','',$field_val));							
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					else if(stripos($line,'SIG:') !== false){
						$field_name = 'sig';//SIG:						
						$field_val = trim(str_ireplace('SIG:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);							
					}
					else if(stripos($line,'Original Refills Authorized:') !== false){
						$field_name = 'refills';//Prescribed Refills:						
						$field_val = trim(str_ireplace('Original Refills Authorized:','',$line));
						$field_val = trim(str_ireplace('refills','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}				
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}
			else if(stripos($reportContents,'RX Refill	Authorization Request')!== false  || stripos($reportContents,'RX Refill Authorization Request')!== false)
			{
				//RX Refill	Authorization Request
				$faxType = 'RX Refill Authorization Request';
				$faxTypeMain = 'Refill Request';
				if(stripos($reportContents,"PLEASE COMPLETE AND FAX BACK TO THE PHARMACY:") !== false){
					$posEnd = stripos($reportContents,"PLEASE COMPLETE AND FAX BACK TO THE PHARMACY:");
					$reportContents = substr($reportContents,0,$posEnd);
					$rpt_lines = explode("\n",$reportContents);
				}
				$pharmacyKeys = array('pharmacy_name','pharmacy_address','pharmacy_city','pharmacy_phone','pharmacy_fax','pharmacy_npi','store_number');	
				
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'Date of Request:') !== false)
					{
						$field_name = 'request_date';//Date:   Requested Date:  Fax Date: 08/04/2019							
						$field_val = trim(str_ireplace('Date of Request:','',$line));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}
					else if(stripos($line,'Prescriber:') !== false && stripos($line,'Pharmacy:') !== false){
						$field_name = 'prescriber_name';//PRESCRIBER:
						$nextKey = $key+1;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}
						$elements = explode("\t",$field_val);
						$field_val = isset($elements[0]) ? $elements[0]: '';						
						//$field_val = trim(str_ireplace('Prescription Refill Request for:','',$field_val));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
							
					}
					else if(stripos($line,'Phone:') !== false){
						$elements = explode("\t",$line);						
						$field_name = 'pharmacy_phone';						
						$field_val = isset($elements[1]) ? $elements[1]: '';
						$field_val = trim(str_ireplace('Phone:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					else if(stripos($line,'FAX:') !== false){
						$elements = explode("\t",$line);						
						$field_name = 'pharmacy_fax';						
						$field_val = isset($elements[1]) ? $elements[1]: '';
						$field_val = trim(str_ireplace('FAX:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					else if(stripos($line,'Patient:') !== false && stripos($line,'Original Rx:') !== false){
						$field_name = 'Patient:';//Name:  (Lastname,FirstName ) 
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';//TURNEY, ELIZABETH						
						$field_val = trim(str_ireplace('Patient:','',$field_val));
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(',',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						///////////////////////////////
						$field_name = 'rx_number';//Rx Number:						
						$field_val = isset($elements[1]) ? $elements[1]: '';
						$field_val = trim(str_ireplace('Original Rx:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}															
					else if(stripos($line,'DOB:') !== false){
						$field_name = 'dob';						
						$field_val = trim(str_ireplace('DOB:','',$line));
						$field_val = trim(substr($field_val,0,10));						
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
					}
					//else if(stripos($line,'Drug Dispensed:') !== false && stripos($line,'NDC:') !== false)
					else if(stripos($line,'Prescribed Product:') !== false)
					{
						$field_name = 'drug_name';//Medication: //Drug:
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';	
						$field_val = trim(str_ireplace('Prescribed Product:','',$field_val));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}//Original Quantity Ordered: 90	Dispensed Quantity: 90  
					else if(stripos($line,'Prescribed Quantity:') !== false && stripos($line,'Last Fill Date:') !== false){
						$field_name = 'qty_prescribed';//Qty. Prescribed:  //Prescribed Qty:
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Prescribed Quantity:','',$field_val));							
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
						
						if(isset($elements[1]) && stripos($elements[1],'Last Fill Date:') !== false){
							$field_name = 'date_last_filled';//Date Last Filled:   //Last Filled:							
							$field_val = isset($elements[1]) ? $elements[1] : '';
							$field_val = trim(str_ireplace('Last Fill Date:','',$field_val));						
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);
						}											
					}
					else if(stripos($line,'SIG:') !== false){
						$field_name = 'sig';//SIG:						
						$field_val = trim(str_ireplace('SIG:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);							
					}
									
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
				if(stripos($reportContents,'Directions:') !== false){
					$postStart = stripos($reportContents,'Directions:');
					$postEnd = stripos($reportContents,'Dispensed Product:');
					$field_val = substr($reportContents,$postStart,($postEnd - $postStart));
					$field_val = trim(str_ireplace('Directions:','',$field_val));
					$field_name = 'sig';//SIG:																		
					$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
					array_push($responseArray['rpt_detail'], $tempArr);
				}
			}
			else if(stripos($reportContents,'Controlled Substance Refill Request Notification')!== false)
			{
				$faxType = 'Controlled Substance Refill Request Notification';
				$faxTypeMain = 'Controlled Substance Refill Request';				
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'Prescriber') !== false && stripos($line,'Pharmacy') !== false){
						$field_name = 'prescriber_name';//PRESCRIBER:
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';						
						$field_val = trim(str_ireplace('Prescriber','',$field_val));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
							
					}					
					/*else if(stripos($line,'Fax Date:') !== false)
					{
						$field_name = 'request_date';//Date:   Requested Date:  Fax Date: 08/04/2019
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';	
						$field_val = trim(str_ireplace('Fax Date:','',$field_val));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}*/					
					else if(stripos($line,'Rx Number:') !== false){
						$field_name = 'rx_number';//Rx Number:
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Rx Number:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						if(isset($elements[1]) && stripos($elements[1],'Drug Prescribed:') !== false){
							$field_name = 'drug_name';							
							$field_val = isset($elements[1]) ? $elements[1] : '';
							$field_val = trim(str_ireplace('Drug Prescribed:','',$field_val));						
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);							
						}
							
					}
					else if(stripos($line,'Date Last Disp:') !== false){
						$field_name = 'date_last_filled';//Date Last Filled:   //Last Filled:
						$pos = stripos($line,'Drug Dispensed:');						
						$field_val = substr($line,0,($pos-1));//to the end of line
						$field_val = trim(str_ireplace('Date Last Disp:','',$field_val));
						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);							
					}
					else if(stripos($line,'Name:') !== false){
						$field_name = 'Patient:';//Name:  (FirstName Lastname) 												
						$field_val = trim(str_ireplace('Name:','',$line));
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'DOB:') !== false && stripos($line,'Phone:') !== false){						
						$field_name = 'dob';						
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';								
						$field_val = trim(str_ireplace('DOB:','',$field_val));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}																				
					else if(stripos($line,'Written Quantity:') !== false){
						$field_name = 'qty_prescribed';//Qty. Prescribed:  //Prescribed Qty:
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Written Quantity:','',$field_val));							
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						//array_push($responseArray['rpt_detail'], $tempArr);						
										
					}
					else if(stripos($line,'Disp Quantity:') !== false){
						$field_name = 'qty_dispensed';//Qty. Prescribed:  //Prescribed Qty:
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Disp Quantity:','',$field_val));							
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
						
						$field_name = 'sig';//Qty. Prescribed:  //Prescribed Qty:						
						$field_val = isset($elements[1]) ? $elements[1]: '';
						$field_val = trim(str_ireplace('Directions:','',$field_val));							
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}					
								
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}
			else if((stripos($reportContents,'Controlled Substance Refill Request')!== false || stripos($reportContents,'Controlled	Substance Refill	Request')!== false) && (stripos($reportContents,'Message ID:')!== false || stripos($reportContents,'eRx ID:')!== false || stripos($reportContents,'eRx Network')!== false))
			{
				//RX Refill	Request
				$faxType = 'Controlled Substance Refill Request';
				$faxTypeMain = 'Controlled Substance Refill Request';				
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'Date/Time:') !== false)
					{
						$field_name = 'request_date';//Date/Time: 01/21/2020 17:37							
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? trim($elements[0]): '';
						//$field_val = trim(str_ireplace('Date/Time:','',$line));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}
					else if(stripos($line,'Prescriber') !== false && stripos($line,'Pharmacy') !== false){
						$field_name = 'prescriber_name';//PRESCRIBER:
						$nextKey = $key+1;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}
						$elements = explode("\t",$field_val);
						$field_val = isset($elements[0]) ? $elements[0]: '';						
						//$field_val = trim(str_ireplace('Prescription Refill Request for:','',$field_val));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
							
					}					
					else if(stripos($line,'DOB:') !== false && (stripos($reportContents,'DOB:') < stripos($reportContents,'Gender:'))){
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';//ELIZABETH TURNEY (Fname Lname)
						//$field_val = trim(str_ireplace('Patient:','',$field_val));
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						///////////////////////////////////////
						
						$field_name = 'dob';						
						//$field_val = trim(str_ireplace('DOB:','',$line));
						//$field_val = trim(substr($field_val,0,10));	
						$field_val = isset($elements[2]) ? $elements[2]: '';					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
					}
					else if(stripos($line,'Rx Number:') !== false && stripos($line,'Qty Prescribed:') !== false && stripos($line,'Last Dispensed:') !== false){
						$elements = explode("\t",$line);						
						///////////////////////////////
						$field_name = 'rx_number';//Rx Number:						
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Rx Number:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						$field_name = 'qty_prescribed';
						$field_val = isset($elements[2]) ? $elements[2]: '';//90 Ninety
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						$field_name = 'date_last_filled';//Date Last Filled:   //Last Filled:							
						$field_val = isset($elements[3]) ? $elements[3] : '';
						$field_val = trim(str_ireplace('Last Dispensed:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}															
					
					//else if(stripos($line,'Drug Dispensed:') !== false && stripos($line,'NDC:') !== false)
					else if(stripos($line,'Prescribed:') !== false && stripos($line,'Qty Dispensed:') !== false && stripos($line,'Date Written:') !== false)
					{
						$field_name = 'date_written';						
						$elements = explode("\t",$line);
						$field_val = isset($elements[4]) ? $elements[4]: '';	
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						////////////////////////////////////////////
						$field_name = 'drug_name';//Medication: //Drug:
						$nextKey = $key+1;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}//Original Quantity Ordered: 90	Dispensed Quantity: 90  					
					else if(stripos($line,'SIG:') !== false){
						$field_name = 'sig';//SIG:						
						$field_val = trim(str_ireplace('SIG:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);							
					}
									
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}
			else if((stripos($reportContents,'Refill Request')!== false || stripos($reportContents,'Refill	Request')!== false) && (stripos($reportContents,'Message ID:')!== false || stripos($reportContents,'eRx ID:')!== false || stripos($reportContents,'eRx Network')!== false))
			{
				//RX Refill	Request
				$faxType = 'Refill Request';
				$faxTypeMain = 'Refill Request';
				
				$posComments = stripos($reportContents,'Comments:');
				$posPrescriber = stripos($reportContents,'Prescriber');
				$posPatient = stripos($reportContents,'Patient');
				$posPrescription = stripos($reportContents,'Prescription');
				
				$prescriberContents 	= substr($reportContents,$posPrescriber, ($posPatient - $posPrescriber));
				$patientContents 		= substr($reportContents,$posPatient, ($posPrescription - $posPatient));
				$prescriptionContents 	= substr($reportContents,$posPrescription, ($posComments - $posPrescription));
				
				if(stripos($reportContents,'Date/Time:') !== false)
				{
					$field_name = 'request_date';//Date/Time: 06/30/2020	07:00
					
					$posStart = stripos($reportContents,'Date/Time:');
					$partialContents = substr($reportContents,$posStart);//to end of contents
					$rpt_lines = explode("\n",$partialContents);
					$line = $rpt_lines[0];//Take first line , ignore rest of lines				
					
					$elements = explode("\t",$line);
					$field_val = isset($elements[0]) ? trim($elements[0]): '';
					
					if(isset($elements[0]) && trim($elements[0]) == 'Date/Time:'){
						$field_val = isset($elements[1]) ? trim($elements[1]): '';
					}
												
					$field_val = trim(str_ireplace('Date/Time:','',$field_val));					
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);						
				}
				
				if(!empty($prescriberContents)){
					$rpt_lines = explode("\n",$prescriberContents);
					//The array_shift() function, which is used to remove the first element 
					//from an array, returns the removed element. It also returns NULL, if the array is empty.
					//Note: If the keys are numeric, all elements will get new keys, starting from 0 and increases by 1
					$remove = array_shift($rpt_lines); 
					//unset($rpt_lines[0]) ;//It removes the first element without affecting the key values..
					$pharmacyKeys = array('pharmacy_name','pharmacy_address','pharmacy_city','pharmacy_phone','pharmacy_fax','pharmacy_npi','store_number');
					foreach($rpt_lines as $key => $line){
						$elements = explode("\t",$line);							
						if($key == 0){
							$field_name = 'prescriber_name';//PRESCRIBER:
							$field_val = isset($elements[0]) ? $elements[0]: '';						
							//$field_val = trim(str_ireplace('Prescription Refill Request for:','',$field_val));
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						////////////////////////////////////////
						$field_name = $pharmacyKeys[$key];												
						$field_val = isset($elements[1]) ? trim($elements[1]) : '';
						if(count($elements) > 3){
							$field_val = isset($elements[3]) ? trim($elements[3]) : '';
						}
						else if(count($elements) > 2){
							$field_val = isset($elements[2]) ? trim($elements[2]) : '';	
						}
						
						$field_val = trim(str_ireplace('Phone:','',$field_val));
						$field_val = trim(str_ireplace('Fax:','',$field_val));
						$field_val = trim(str_ireplace('NPI:','',$field_val));
						$field_val = trim(str_ireplace('NCPDP:','',$field_val));
												
						if($field_name == 'pharmacy_city'){
							//'pharmacy_city','pharmacy_state','pharmacy_zip',
							explode(" ",$field_val);
						}
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
					}
				}
				
				if(!empty($patientContents)){
					$rpt_lines = explode("\n",$patientContents);
					$remove = array_shift($rpt_lines);//Note: If the keys are numeric, all elements will get new keys, starting from 0
					//unset($rpt_lines[0]) ;//It removes the first element without affecting the key values..
					//foreach($rpt_lines as $key => $line){}
					$line = $rpt_lines[0];
					$elements = explode("\t",$line);
					$field_val = isset($elements[0]) ? trim($elements[0]): '';//(Fname Lname)
					$pat_name = explode(' ',$field_val);
					if(!empty($pat_name) && count($pat_name) > 0){
						$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
						$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
					else{
						$tempArr = array('key' => 'first_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
						$tempArr = array('key' => 'last_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
					//////////////////////////////////////
					$field_name = 'dob';
					$field_val = isset($elements[1]) ? trim($elements[1]) : '';
					if(count($elements)>2){
						$field_val = isset($elements[2]) ? trim($elements[2]) : '';
					}
											
					//$field_val = trim(str_ireplace('DOB:','',$line));
					//$field_val = trim(substr($field_val,0,10));											
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);
				}
				
				if(!empty($prescriptionContents)){
					$rpt_lines = explode("\n",$prescriptionContents);
					$remove = array_shift($rpt_lines);
					//unset($rpt_lines[0]) ;//It removes the first element without affecting the key values..
					foreach($rpt_lines as $key => $line){
						if(stripos($line,'Rx Number:') !== false && stripos($line,'Qty Prescribed:') !== false && stripos($line,'Last Dispensed:') !== false){
							$elements = explode("\t",$line);						
							///////////////////////////////
							$field_name = 'rx_number';//Rx Number:						
							$field_val = isset($elements[0]) ? $elements[0]: '';
							$field_val = trim(str_ireplace('Rx Number:','',$field_val));						
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);
							
							$field_name = 'qty_prescribed';
							$field_val = isset($elements[1]) ? trim($elements[1]) : '';
							if($field_val == 'Qty Prescribed:'){
								$field_val = isset($elements[2]) ? trim($elements[2]) : '';
							}
							$field_val = trim(str_ireplace('Qty Prescribed:','',$field_val));
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);
							
							$field_name = 'date_last_filled';//Date Last Filled:   //Last Filled:							
							$field_val = isset($elements[3]) ? $elements[3] : '';
							if(isset($elements[4])){
								$field_val = $elements[4];
							}
							$field_val = trim(str_ireplace('Last Dispensed:','',$field_val));						
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);						
						}
						else if(stripos($line,'Qty Dispensed:') !== false && stripos($line,'Date Written:') !== false)
						{
							$field_name = 'date_written';						
							$elements = explode("\t",$line);
							$field_val = isset($elements[3]) ? trim($elements[3]) : '';
							if(isset($elements[4])){
								$field_val = isset($elements[4]) ? trim($elements[4]) : '';
							}	
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);
							////////////////////////////////////////////
							$field_name = 'drug_name';//Medication: //Drug:
							$nextKey = $key+1;
							$field_val = '';
							if (array_key_exists($nextKey,$rpt_lines)){
								$field_val = trim($rpt_lines[$nextKey]);
							}
							$field_val = trim(str_ireplace('Prescribed:','',$field_val));					
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);						
						}
						else if(stripos($line,'SIG:') !== false){
							$field_name = 'sig';//SIG:
							$posSig = stripos($prescriptionContents,'SIG:');
							$field_val = substr($prescriptionContents,$posSig);						
							$field_val = trim(str_ireplace('SIG:','',$field_val));
							$field_val = trim(str_ireplace('Comments:','',$field_val));						
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);							
						}
						
					}//end foreach
				}
				
				/*
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'Date/Time:') !== false)
					{
						$field_name = 'request_date';//Date:   Requested Date:  Fax Date: 08/04/2019							
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Date/Time:','',$field_val));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}
					else if(stripos($line,'Prescriber') !== false && stripos($line,'Pharmacy') !== false){
						$field_name = 'prescriber_name';//PRESCRIBER:
						$nextKey = $key+1;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}
						$elements = explode("\t",$field_val);
						$field_val = isset($elements[0]) ? $elements[0]: '';						
						//$field_val = trim(str_ireplace('Prescription Refill Request for:','',$field_val));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
							
					}					
					else if(stripos($line,'DOB:') !== false){
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';//ELIZABETH TURNEY (Fname Lname)
						//$field_val = trim(str_ireplace('Patient:','',$field_val));
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						///////////////////////////////////////
						
						$field_name = 'dob';						
						//$field_val = trim(str_ireplace('DOB:','',$line));
						//$field_val = trim(substr($field_val,0,10));	
						$field_val = isset($elements[2]) ? $elements[2]: '';					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
					}
					else if(stripos($line,'Rx Number:') !== false && stripos($line,'Qty Prescribed:') !== false && stripos($line,'Last Dispensed:') !== false){
						$elements = explode("\t",$line);						
						///////////////////////////////
						$field_name = 'rx_number';//Rx Number:						
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Rx Number:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						$field_name = 'qty_prescribed';
						$field_val = isset($elements[2]) ? $elements[2]: '';
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						$field_name = 'date_last_filled';//Date Last Filled:   //Last Filled:							
						$field_val = isset($elements[3]) ? $elements[3] : '';
						$field_val = trim(str_ireplace('Last Dispensed:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}															
					
					//else if(stripos($line,'Drug Dispensed:') !== false && stripos($line,'NDC:') !== false)
					else if(stripos($line,'Prescribed:') !== false && stripos($line,'Qty Dispensed:') !== false && stripos($line,'Date Written:') !== false)
					{
						$field_name = 'date_written';						
						$elements = explode("\t",$line);
						$field_val = isset($elements[4]) ? $elements[4]: '';	
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						////////////////////////////////////////////
						$field_name = 'drug_name';//Medication: //Drug:
						$nextKey = $key+1;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}//Original Quantity Ordered: 90	Dispensed Quantity: 90  					
					else if(stripos($line,'SIG:') !== false){
						$field_name = 'sig';//SIG:						
						$field_val = trim(str_ireplace('SIG:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);							
					}
									
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
				*/
			}			
			else if(stripos($reportContents,'PRIOR AUTH REQUIRED/INVALID')!== false || stripos($reportContents,'THIRD PARTY REJECTION')!== false || stripos($reportContents,'REJECT CODES')!== false)
			{
				$faxType = 'PRIOR AUTH REQUIRED/INVALID';
				$faxTypeMain = 'PA Request';				
				foreach($rpt_lines as $key => $line){
					
					/*if(stripos($line,'Fax Date:') !== false)
					{
						$field_name = 'request_date';//Date:   Requested Date:  Fax Date: 08/04/2019
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';	
						$field_val = trim(str_ireplace('Fax Date:','',$field_val));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}	//Store # 17611					
					else */
					if(stripos($line,'Rx Number:') !== false){
						$field_name = 'rx_number';//Rx Number:
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Rx Number:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
						
					}
					else if(stripos($line,'PRESCRIBER:') !== false && stripos($line,'PATIENT:') !== false){
						$field_name = 'Patient:';//Name:  (FirstName Lastname) 	MELANIE JOYNER					
						//$field_val = trim(str_ireplace('Name:','',$item));
						$nextKey = $key+1;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}
												
						$elements = explode("\t",$field_val);
						/////////////////////////////////////////////
						$field_name = 'prescriber_name';//PRESCRIBER:						
						$field_val = isset($elements[0]) ? $elements[0]: '';												
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						/////////////////////////////////////////////////
						$field_val = isset($elements[1]) ? $elements[1]: '';//LORENZO COLLINS 						
						//$field_val = trim(str_ireplace('Patient:','',$field_val));
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}						

					}
					else if(stripos($line,'DOB:') !== false){						
						$field_name = 'dob';
						$field_val = '';
						$dobPos = stripos($line,'DOB:');
						if($dobPos !== false){
							$field_val = substr($line,$dobPos);//to the end of line
						}							
						$field_val = trim(str_ireplace('DOB:','',$field_val));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
					}															
					/*else if(stripos($line,'Drug Prescribed:') !== false){
						$field_name = 'drug_name';//Medication: //Drug:						
						$field_val = trim(str_ireplace('Drug Prescribed:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}*/
					else if(stripos($line,'Dispensing:') !== false)
					{
						$field_name = 'drug_name';//Medication: //Drug:
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';	
						$field_val = trim(str_ireplace('Dispensing:','',$field_val));					
						
						//$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						//array_push($responseArray['rpt_detail'], $tempArr);
					}
					else if(stripos($line,'Written:') !== false)
					{
						$field_name = 'drug_name';//Medication: //Drug:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';	
						//$field_val = trim(str_ireplace('Drug Dispensed:','',$field_val));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						$field_name = 'ndc';//Medication: //Drug:
						$field_val = isset($elements[1]) ? $elements[1]: '';	
						$field_val = trim(str_ireplace('NDC:','',$field_val));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					else if(stripos($line,'NDC #:') !== false)
					{
						$field_name = 'ndc';//Medication: //Drug:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';	
						//$field_val = trim(str_ireplace('Drug Dispensed:','',$field_val));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
					}			
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}			
			else if(stripos($reportContents,'Controlled RX Refill Authorization Request')!== false || stripos($reportContents,'Controlled RX Refill Authorization Reguest')!== false)
			{
				$faxType = 'Controlled RX Refill Authorization Request';
				$faxTypeMain = 'Controlled Refill Request';				
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'Date of Request:') !== false)
					{
						$field_name = 'request_date';//Date:   Requested Date:  Fax Date: 08/04/2019							
						$field_val = trim(str_ireplace('Date of Request:','',$line));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}
					else if(stripos($line,'Provider:') !== false && stripos($line,'Pharmacy:') !== false){
						$field_name = 'prescriber_name';//PRESCRIBER:
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';						
						$field_val = trim(str_ireplace('Provider:','',$field_val));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
							
					}										
					else if(stripos($line,'Your patient') !== false){
						$field_name = 'Patient:';//Name:  (FirstName Lastname) 
						$elements = explode(",",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';//MELISSA REYES (DOB 09/29/1981)
						$dobPos = stripos($field_val,'(');
						$dobStr = substr($field_val,$dobPos);
						$field_val = trim(substr($field_val,0,($dobPos-1)));						
						//$field_val = trim(str_ireplace('Patient:','',$field_val));
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						///////////////////////////////
						$field_name = 'dob';
						$field_val = trim(str_ireplace('(','',$dobStr));
						$field_val = trim(str_ireplace(')','',$field_val));
						$field_val = trim(str_ireplace('DOB','',$field_val));	
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}															
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}
			else if(in_array('90 DAYS SUPPLY: REQUEST FOR AUTHORIZATION',$rpt_lines)){
				$faxType = '90 DAYS SUPPLY: REQUEST FOR AUTHORIZATION';
				$faxTypeMain = '90 DAYS SUPPLY';
			}			
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	return $responseArray;
}

function optumRxHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	$tempArr = array('testName' => 'pharmacy_name', 'value' => 'KROGER PHARMACY','flag'=>'','Reference'=>'');						
	array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		/*print "<pre>";
		print_r($rpt_lines);
		print "</pre>";*/
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			//Prescription Refill Request for:
			//if(in_array('REQUEST FOR A REFILL OR NEW PRESCRIPTION',$rpt_lines))
			if(stripos($reportContents,'Prior Authorization Request')!== false)//
			{
				$faxType = 'Prior Authorization Request';
				$faxTypeMain = 'PA Request';				
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'Date:') !== false)
					{
						$field_name = 'request_date';//Date:   Requested Date:  Fax Date: 08/04/2019
						//$elements = explode("\t",$line);
						//$field_val = isset($elements[1]) ? $elements[1]: '';	
						$field_val = trim(str_ireplace('Date:','',$line));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}	//Store # 17611										
					else if(stripos($line,'Patient Name:') !== false && stripos($line,'Patient DOB:') !== false){
						$field_name = 'Patient:';//Name:  (FirstName Lastname) 	MELANIE JOYNER				
											
						//$elements = explode("\t",$line);						
						//$field_val = isset($elements[0]) ? $elements[0]: '';//JESSY SANTOS 	(FirstName Lastname)					
						
						$posDob = stripos($line,'Patient DOB:');
						$field_val = substr($line,0,$posDob);
						$field_val = trim(str_ireplace('Patient Name:','',$field_val));
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						//////////////////////////////
						$field_name = 'dob';	
						//$field_val = isset($elements[1]) ? $elements[1]: '';
						$field_val = substr($line,$posDob);// To end of line
						$field_val = trim(str_ireplace('Patient DOB:','',$field_val));
						$field_val = trim(str_ireplace(')','',$field_val));
						$field_val = trim(str_ireplace('(','',$field_val));
						$field_val = trim(str_ireplace('~~','',$field_val));
						$field_val = trim(str_ireplace('~','',$field_val));						
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
					}
																			
					else if(stripos($line,'Medication Name:') !== false){
						$field_name = 'drug_name';//Medication: //Drug:						
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Medication Name:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}					
					/*else if(stripos($line,'GPI/NDC:') !== false)
					{
						$field_name = 'drug_name';//Medication: //Drug:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';	
						//$field_val = trim(str_ireplace('Drug Dispensed:','',$field_val));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						$field_name = 'ndc';//Medication: //Drug:
						$field_val = isset($elements[1]) ? $elements[1]: '';	
						$field_val = trim(str_ireplace('NDC:','',$field_val));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					else if(stripos($line,'NDC #:') !== false)
					{
						$field_name = 'ndc';//Medication: //Drug:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';	
						//$field_val = trim(str_ireplace('Drug Dispensed:','',$field_val));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
					}*/			
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}			
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	return $responseArray;
}

//CoverMyMedsPAHandler
function CoverMyMedsPAHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	$tempArr = array('testName' => 'pharmacy_name', 'value' => 'Cover My Meds PA','flag'=>'','Reference'=>'');						
	array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	/*if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}*/
	if(!empty($tempArr) && count($tempArr) > 2){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		if(isset($tempArr[2]) && is_numeric($tempArr[2])){
			$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		}
		if(isset($tempArr[3]) && is_numeric($tempArr[3])){
			$fax_data_id = isset($tempArr[3]) ? $tempArr[3] : 0;
		}
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		print "<pre>";
		print_r($rpt_lines);
		print "</pre>";
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			
			$faxType = 'Prior Authorization';
			$faxTypeMain = 'PA Request';
			if(stripos($reportContents,'change the prescription')!== false || stripos($reportContents,'covered alternative')!== false){
				//This is same as "A covered alternative may be available" case below in elseif
				$faxType = 'Drug Change Request';
				$faxTypeMain = 'Drug Change Request';
				$posStart = 0;
				if(stripos($reportContents,'Prescriber:')!== false){
					$posStart = stripos($reportContents,'Prescriber:');
				}
				
				if(stripos($reportContents,'Dear Staff')!== false){
					$posEnd = stripos($reportContents,'Dear Staff');
					$headerContents = substr($reportContents,$posStart,($posEnd - $posStart));
				}
				else{
					$headerContents = substr($reportContents,$posStart); // to end of contents
				}
				
				
				$headerContents = trim(str_ireplace("\n\r"," ",$headerContents));
				$headerContents = trim(str_ireplace("\r\n"," ",$headerContents));
				$headerContents = trim(str_ireplace("\n"," ",$headerContents));
				$headerContents = trim(str_ireplace("\r"," ",$headerContents));
				$headerContents = trim(str_ireplace("  "," ",$headerContents));
				$headerContents = trim(str_ireplace("Prescriber:","",$headerContents));
				//echo nl2br($headerContents).'<br>-----------------------------<br>';
				$posEnd = stripos($headerContents,"is not covered by");
				$field_val = substr($headerContents,0,$posEnd);//drug name will left
				//echo '<br>-----------------------------<br>'.nl2br($field_val).'<br>-----------------------------<br>';
				$field_name = 'drug_name';//Medication: //Drug:
																	
				//$field_val = trim(str_ireplace('Drug Dispensed:','',$field_val));					
				$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
				array_push($responseArray['rpt_detail'], $tempArr);
				
				///////////////////////////////////////////////////////////
				$posStart = stripos($reportContents,'Patient Last Name:');
				$bodyContents = substr($reportContents,$posStart);// To end of contnts
				
				$rpt_lines = explode("\n",$bodyContents);
				foreach($rpt_lines as $key => $line){
					if(stripos($line,'Patient Last Name:') !== false)
					{
						$patPos = stripos($line,'Patient Last Name:'); 
						$field_val = substr($line,$patPos);//to the end of line
						
						$field_val = trim(str_ireplace('Patient Last Name:','',$field_val));
						$tempArr = array('key' => 'last_name', 'value' => isset($field_val) ? trim($field_val) : '');
						array_push($responseArray['rpt_header'], $tempArr);
						
						/*$first_name = '';
						if(!empty($pat_name)){
							$first_name = trim(str_ireplace($field_val,'',$pat_name));
						}
						$tempArr = array('key' => 'first_name', 'value' => $first_name);
						array_push($responseArray['rpt_header'], $tempArr);*/
							
					}
					else if(stripos($line,'DOB:') !== false){						
						$field_name = 'dob';												
						$dobPos = stripos($line,'DOB:'); 
						$field_val = substr($line,$dobPos);//to the end of line
						$field_val = trim(str_ireplace('DOB:','',$field_val));	// DOB: 09/11/1975					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}
				}
				
			}
			else if(stripos($reportContents,'Important:')!== false && stripos($reportContents,'REJECTED')!== false)
			{
				$posImp = stripos($reportContents,'Important:');
				$posRej = stripos($reportContents,'REJECTED');
				$field_val = substr($reportContents,$posImp, ($posRej - $posImp));
				$field_val = trim(str_ireplace('Important:','',$field_val));
				$field_val = trim(str_ireplace('REJECTED','',$field_val));
				$field_val = trim(str_ireplace('has been','',$field_val));
				
				$field_name = 'drug_name';//Medication: //Drug:								
				$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
				array_push($responseArray['rpt_detail'], $tempArr);
				/////////////////////////////////////////////////////////////
				$posStart = stripos($reportContents,'To get assistance');
				$posEnd = stripos($reportContents,'Sincerely');
				
				$subContents = substr($reportContents,$posStart,($posEnd - $posStart));
				
				$rpt_lines = explode("\n",$subContents);
				
				foreach($rpt_lines as $key => $line)
				{
				
					if(stripos($line,'Patient Last Name:') !== false)
					{
						$patPos = stripos($line,'Patient Last Name:'); 
						$field_val = substr($line,$patPos);//to the end of line
						
						$field_val = trim(str_ireplace('Patient Last Name:','',$field_val));
						$tempArr = array('key' => 'last_name', 'value' => isset($field_val) ? trim($field_val) : '');
						array_push($responseArray['rpt_header'], $tempArr);					
											
					}	
					else if(stripos($line,'DOB:') !== false){						
						$field_name = 'dob';												
						$dobPos = stripos($line,'DOB:'); 
						$field_val = substr($line,$dobPos);//to the end of line
						$field_val = trim(str_ireplace('DOB:','',$field_val));	// DOB: 09/11/1975					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}	
				
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
				
				}//end foreach $rpt_lines
			}
			else if(stripos($reportContents,'Followup:') !== false && stripos($reportContents,'is waiting for') !== false){
				$posStart = stripos($reportContents,'Followup:');
				$reportContents = substr($reportContents,$posStart);// To end of contnts
				$reportContents = trim(str_ireplace('COMPLETE THIS FORM ONLINE','',$reportContents));
				
				$posEnd = stripos($reportContents,'Login to go.covermymeds.com');
				$headerContents = substr($reportContents,0,$posEnd);
				$headerContents = trim(str_ireplace('Followup:','',$headerContents));
				$posDOB = stripos($headerContents,'DOB')-1;
				$pat_name = substr($headerContents,0,$posDOB);
				
				$headerContents = substr($headerContents,$posDOB); //To end of header contents, Now we have "(DOB:xxxxxxx)" at begining
				
				$posStart = stripos($headerContents,'medication');
				$headerContents = substr($headerContents,$posStart);
				$field_val = trim(str_ireplace('medication','',$headerContents));
				$field_val = trim(str_ireplace("\n"," ",$field_val));
				$field_name = 'drug_name';//Medication: //Drug:					
				//$field_val = trim(str_ireplace('Drug Dispensed:','',$field_val));					
				$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
				array_push($responseArray['rpt_detail'], $tempArr);
				
				///////////////////////////////////////////////////////////
				$posStart = stripos($reportContents,'Patient Last Name:');
				$bodyContents = substr($reportContents,$posStart);// To end of contnts
				
				$rpt_lines = explode("\n",$bodyContents);
				foreach($rpt_lines as $key => $line){
					if(stripos($line,'Patient Last Name:') !== false)
					{
						$patPos = stripos($line,'Patient Last Name:'); 
						$field_val = substr($line,$patPos);//to the end of line
						
						$field_val = trim(str_ireplace('Patient Last Name:','',$field_val));
						$tempArr = array('key' => 'last_name', 'value' => isset($field_val) ? trim($field_val) : '');
						array_push($responseArray['rpt_header'], $tempArr);
						
						/*$first_name = '';
						if(!empty($pat_name)){
							$first_name = trim(str_ireplace($field_val,'',$pat_name));
						}
						$tempArr = array('key' => 'first_name', 'value' => $first_name);
						array_push($responseArray['rpt_header'], $tempArr);*/
							
					}
					else if(stripos($line,'DOB:') !== false){						
						$field_name = 'dob';												
						$dobPos = stripos($line,'DOB:'); 
						$field_val = substr($line,$dobPos);//to the end of line
						$field_val = trim(str_ireplace('DOB:','',$field_val));	// DOB: 09/11/1975					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}
				}
				
			}
			//else if(stripos($reportContents,'A Prior Authorization has been started') !== false && stripos($reportContents,'prescription by the Pharmacy') !== false)
			else if(stripos($reportContents,'A Prior Authorization has been started') !== false){
			
				$posStart = stripos($reportContents,'A Prior Authorization has been started');
				$reportContents = substr($reportContents,$posStart);// To end of contents
				$reportContents = trim(str_ireplace('COMPLETE THIS FORM ONLINE','',$reportContents));
				//echo nl2br($reportContents).'<br>-----------------------------<br>';
				//$posEnd = stripos($reportContents,'Login to go.covermymeds.com');
				$headerContents = $reportContents;
				$headerContents = trim(str_ireplace("\n\r"," ",$headerContents));
				$headerContents = trim(str_ireplace("\r\n"," ",$headerContents));
				$headerContents = trim(str_ireplace("\n"," ",$headerContents));
				$headerContents = trim(str_ireplace("\r"," ",$headerContents));
				$headerContents = trim(str_ireplace("  "," ",$headerContents));
				//echo nl2br($headerContents).'<br>-----------------------------<br>';
				$posEnd = stripos($headerContents,"prescription by the Pharmacy");
				$headerContents = substr($headerContents,0,$posEnd);
				//echo nl2br($headerContents).'<br>-----------------------------<br>';
				$headerContents = trim(str_ireplace("A Prior Authorization has been started\nfor",'',$headerContents));
				$headerContents = trim(str_ireplace("A Prior Authorization has been started",'',$headerContents));
				if(stripos($headerContents,'for') !== false){
					
				}
				//echo nl2br($headerContents).'<br>-----------------------------<br>';
				$posDOB = stripos($headerContents,'DOB')-1;
				$pat_name = substr($headerContents,0,$posDOB);
				
				$headerContents = substr($headerContents,$posDOB); //To end of header contents, Now we have "(DOB:xxxxxxx)" at begining
				//echo nl2br($headerContents).'<br>-----------------------------<br>';
				$field_name = 'drug_name';//Medication: //Drug:
				if(stripos($headerContents,')') !== false){
					$posStart = stripos($headerContents,')') + 3;//(DOB:|2/31/1973)'s
					$field_val = substr($headerContents,$posStart);
					$field_val = trim(str_ireplace("\n\r"," ",$field_val));
					$field_val = trim(str_ireplace("\r\n"," ",$field_val));
					$field_val = trim(str_ireplace("\n"," ",$field_val));
					$field_val = trim(str_ireplace("\r"," ",$field_val));
				}
				else{
					//$field_val = trim(str_ireplace('DOB','',$headerContents));
					$field_val = trim(str_ireplace("\n"," ",$field_val));
				}
													
				//$field_val = trim(str_ireplace('Drug Dispensed:','',$field_val));					
				$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
				array_push($responseArray['rpt_detail'], $tempArr);
				
				///////////////////////////////////////////////////////////
				$posStart = stripos($reportContents,'Patient Last Name:');
				$bodyContents = substr($reportContents,$posStart);// To end of contnts
				
				$rpt_lines = explode("\n",$bodyContents);
				foreach($rpt_lines as $key => $line){
					if(stripos($line,'Patient Last Name:') !== false)
					{
						$patPos = stripos($line,'Patient Last Name:'); 
						$field_val = substr($line,$patPos);//to the end of line
						
						$field_val = trim(str_ireplace('Patient Last Name:','',$field_val));
						$tempArr = array('key' => 'last_name', 'value' => isset($field_val) ? trim($field_val) : '');
						array_push($responseArray['rpt_header'], $tempArr);
						
						/*$first_name = '';
						if(!empty($pat_name)){
							$first_name = trim(str_ireplace($field_val,'',$pat_name));
						}
						$tempArr = array('key' => 'first_name', 'value' => $first_name);
						array_push($responseArray['rpt_header'], $tempArr);*/
							
					}
					else if(stripos($line,'DOB:') !== false){						
						$field_name = 'dob';												
						$dobPos = stripos($line,'DOB:'); 
						$field_val = substr($line,$dobPos);//to the end of line
						$field_val = trim(str_ireplace('DOB:','',$field_val));	// DOB: 09/11/1975					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}
				}
				
			}
			else if(stripos($reportContents,'A covered alternative may be available') !== false){
			
				//$posStart = stripos($reportContents,'A Prior Authorization has been started');
				//$reportContents = substr($reportContents,$posStart);// To end of contents
				//$reportContents = trim(str_ireplace('COMPLETE THIS FORM ONLINE','',$reportContents));
				//echo nl2br($reportContents).'<br>-----------------------------<br>';
				
				$posEnd = stripos($reportContents,'Dear Staff');
				$headerContents = substr($reportContents,0,$posEnd);;
				$headerContents = trim(str_ireplace("\n\r"," ",$headerContents));
				$headerContents = trim(str_ireplace("\r\n"," ",$headerContents));
				$headerContents = trim(str_ireplace("\n"," ",$headerContents));
				$headerContents = trim(str_ireplace("\r"," ",$headerContents));
				$headerContents = trim(str_ireplace("  "," ",$headerContents));
				//echo nl2br($headerContents).'<br>-----------------------------<br>';
				$posEnd = stripos($headerContents,"is not covered by");
				$field_val = substr($headerContents,0,$posEnd);//drug name will left
				//echo nl2br($headerContents).'<br>-----------------------------<br>';
				if(stripos($field_val,'email') !== false){
					$posStart = stripos($field_val,'email');
					$field_val = substr($field_val,$posStart);// To end of contents
					$field_val = trim(str_ireplace("email","",$field_val));
					
					$pattern = "/[^@\s]*@[^@\s]*\.[^@\s]*/";// works fine but also matches any puctuation after the email address itself
					//$pattern = "/[^@\s]*@[^@\s\.]*\.[^@\s\.,!?]*/g";
					//$match = eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $text, $regs);
					$replacement = "";
					$field_val = trim(preg_replace($pattern, $replacement, $field_val));
				}
				else if(stripos($field_val,'fax') !== false){
					$posStart = stripos($field_val,'fax');
					$field_val = substr($field_val,$posStart);// To end of contents
					$field_val = trim(str_ireplace("fax","",$field_val));
					$fax_no = substr($field_val,0,14);//(501) 565-1164
					$fax_no = preg_replace("/[^0-9]/","",$fax_no);//Removes everything but numbers!
					if(is_numeric($fax_no)){
						$field_val = substr($field_val,15);
					}
				}
				else if(stripos($field_val,'tel') !== false){
					$posStart = stripos($field_val,'tel');
					$field_val = substr($field_val,$posStart);// To end of contents
					$field_val = trim(str_ireplace("tel","",$field_val));
					$tel = substr($field_val,0,14);
					$tel = preg_replace("/[^0-9]/","",$tel);
					if(is_numeric($fax_no)){
						$field_val = substr($field_val,15);
					}
				}
				else if(stripos($field_val,'Pharmacy Address') !== false){
					$posStart = stripos($field_val,'Pharmacy Address');
					$field_val = substr($field_val,$posStart);// To end of contents
					$field_val = trim(str_ireplace("Pharmacy Address","",$field_val));
				}
				
				if(stripos($field_val,"com\r\n")!==false){
					$field_val = trim(str_ireplace("com\r\n","",$field_val));
				}
				
				//echo nl2br($headerContents).'<br>-----------------------------<br>';
				$field_name = 'drug_name';//Medication: //Drug:
																	
				//$field_val = trim(str_ireplace('Drug Dispensed:','',$field_val));					
				$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
				array_push($responseArray['rpt_detail'], $tempArr);
				
				///////////////////////////////////////////////////////////
				$posStart = stripos($reportContents,'Patient Last Name:');
				$bodyContents = substr($reportContents,$posStart);// To end of contnts
				
				$rpt_lines = explode("\n",$bodyContents);
				foreach($rpt_lines as $key => $line){
					if(stripos($line,'Patient Last Name:') !== false)
					{
						$patPos = stripos($line,'Patient Last Name:'); 
						$field_val = substr($line,$patPos);//to the end of line
						
						$field_val = trim(str_ireplace('Patient Last Name:','',$field_val));
						$tempArr = array('key' => 'last_name', 'value' => isset($field_val) ? trim($field_val) : '');
						array_push($responseArray['rpt_header'], $tempArr);
						
						/*$first_name = '';
						if(!empty($pat_name)){
							$first_name = trim(str_ireplace($field_val,'',$pat_name));
						}
						$tempArr = array('key' => 'first_name', 'value' => $first_name);
						array_push($responseArray['rpt_header'], $tempArr);*/
							
					}
					else if(stripos($line,'DOB:') !== false){						
						$field_name = 'dob';												
						$dobPos = stripos($line,'DOB:'); 
						$field_val = substr($line,$dobPos);//to the end of line
						$field_val = trim(str_ireplace('DOB:','',$field_val));	// DOB: 09/11/1975					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}
				}
				
			}
			else{
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'To submit the PA for') !== false || stripos($line,'To complete the PA for') !== false){
						$field_name = 'Patient:';//Name:  (FirstName Lastname) 												
						$patPos = stripos($line,'To submit the PA for'); 
						if($patPos!==false){
							$field_val = substr($line,$patPos);//to the end of line
							$field_val = trim(str_ireplace('To submit the PA for','',$field_val));
						}
						else{
							$patPos = stripos($line,'To complete the PA for');
							$field_val = substr($line,$patPos);//to the end of line
							$field_val = trim(str_ireplace('To complete the PA for','',$field_val));
						}
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							//$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							//array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							//$tempArr = array('key' => 'last_name', 'value' => '');
							//array_push($responseArray['rpt_header'], $tempArr);
						}	
					}	//key.covermymeds.com				
					else if(stripos($line,'Patient Last Name:') !== false)
					{
						$patPos = stripos($line,'Patient Last Name:'); 
						$field_val = substr($line,$patPos);//to the end of line
						
						$field_val = trim(str_ireplace('Patient Last Name:','',$field_val));
						$tempArr = array('key' => 'last_name', 'value' => isset($field_val) ? trim($field_val) : '');
						array_push($responseArray['rpt_header'], $tempArr);					
											
					}	
					else if(stripos($line,'DOB:') !== false){						
						$field_name = 'dob';												
						$dobPos = stripos($line,'DOB:'); 
						$field_val = substr($line,$dobPos);//to the end of line
						$field_val = trim(str_ireplace('DOB:','',$field_val));	// DOB: 09/11/1975					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}	
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	return $responseArray;
}

//UAMS Medical Center 
function uamsHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	//$tempArr = array('testName' => 'pharmacy_name', 'value' => 'UAMS','flag'=>'','Reference'=>'');						
	//array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	/*if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}*/
	if(!empty($tempArr) && count($tempArr) > 2){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		if(isset($tempArr[2]) && is_numeric($tempArr[2])){
			$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		}
		if(isset($tempArr[3]) && is_numeric($tempArr[3])){
			$fax_data_id = isset($tempArr[3]) ? $tempArr[3] : 0;
		}
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Hospital';//Medical Center/UAMS Hospital
	$department = '';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		/*print "<pre>";
		print_r($rpt_lines);
		print "</pre>";*/
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			//if(stripos($reportContents,'University of Arkansas for Medical Sciences')!== false)
			if(in_array('Subject: Request for Medical Records',$rpt_lines)){
				$faxType = 'Request for Medical Records';
				$faxTypeMain = 'Request for Medical Records';				
				foreach($rpt_lines as $key => $line){					
					
					if(stripos($line,'UAMS Hospital') !== false){
						$field_name = 'Patient:';//Name:  (Lastname, FirstName) Lindly, Jessica L
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';//FERNANDEZ, CAMELIA						
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(',',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$firstName = isset($pat_name[1]) ? trim($pat_name[1]) : ''; 							
							if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
								$firstNameArr = explode(' ',$firstName);
								$firstName = $firstNameArr[0];
							}
							$tempArr = array('key' => 'first_name', 'value' => $firstName);
							//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						
					}
					else if(stripos($line,'MRN:') !== false && stripos($line,'DOB:') !== false){
						$field_name = 'dob';
						$posMRN = stripos($line,'MRN:');//MRN: 001587951, DOB: 8/21/1977, Sex: F
						$lineContents = substr($line,$posMRN);//to the end of line
						$elements = explode(",",$lineContents);
						$field_val = isset($elements[1]) ? $elements[1]: '';// 09-15-1949
						$field_val = trim(str_ireplace('DOB:','',$field_val));							
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
					}
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}
			else if(stripos($reportContents,'Progress Notes by')!==false){
				$faxType = 'Progress Note';
				$faxTypeMain = 'Progress Note';	
				if(stripos($reportContents,'Neurology Clinic')!==false){
					//$responseArray['department'] = 'UAMS Neurology';
					$faxTypeMain = 'UAMS Neurology';
					$department = 'Neurology';
				}
				else if(stripos($reportContents,'Cardiology Clinic')!==false){					
					$faxTypeMain = 'UAMS Cardiology';					
					//$procStr ='Cardiac';
					$department = 'CARDIOLOGY';
				}
				
				foreach($rpt_lines as $key => $line){					
					
					if(stripos($line,'DOB:') !== false){						
						$field_name = 'dob';
						$posDOB = stripos($line,'DOB:');//MRN: 001587951, DOB: 8/21/1977, Sex: F
						$lineContents = substr($line,$posDOB);//to the end of line						
						$elements = explode("\t",$lineContents);
						$field_val = isset($elements[0]) ? $elements[0]: '';//FERNANDEZ, CAMELIA	
						$field_val = trim(str_ireplace('DOB:','',$field_val));							
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
						$field_val = substr($line,0,($posDOB-1));	//Silva, Andres C	(Lastname, FirstName)			
						$field_val = substr($field_val,0,(stripos($field_val,'(')-1));
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(',',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$firstName = isset($pat_name[1]) ? trim($pat_name[1]) : ''; 							
							if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
								$firstNameArr = explode(' ',$firstName);
								$firstName = $firstNameArr[0];
							}
							$tempArr = array('key' => 'first_name', 'value' => $firstName);
							//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						
					}
					else if(stripos($line,'Encounter Date') !== false){
						$field_name = 'encounter_date';
						$pos = stripos($line,'Encounter Date');//Encounter Date: 
						$lineContents = substr($line,$pos);//to the end of line
						
						if(stripos($lineContents,'Creation Time') !== false){
							$pos = stripos($lineContents,'Creation Time');
						}
						else{
							$pos = stripos($lineContents,'Creatiion Time');
						}
						
						$lineContents = substr($lineContents,0,$pos-1);//to the end of line
						$field_val = trim(str_ireplace('Encounter Date','',$lineContents));
						$field_val = trim(str_ireplace(':','',$field_val));
						
						$responseArray['fax_date_time'] = $field_val;
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}
			else if(stripos($reportContents,'Discharge Summary by')!==false){
				$faxType = 'Discharge Summary';
				$faxTypeMain = 'Discharge Summary';				
				foreach($rpt_lines as $key => $line){					
					
					if(stripos($line,'DOB:') !== false){						
						$field_name = 'dob';
						$posDOB = stripos($line,'DOB:');//MRN: 001587951, DOB: 8/21/1977, Sex: F
						$lineContents = substr($line,$posDOB);//to the end of line						
						$elements = explode("\t",$lineContents);
						$field_val = isset($elements[0]) ? $elements[0]: '';//FERNANDEZ, CAMELIA	
						$field_val = trim(str_ireplace('DOB:','',$field_val));							
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
						$field_val = substr($line,0,($posDOB-1));	//Silva, Andres C	(Lastname, FirstName)			
						$field_val = substr($field_val,0,(stripos($field_val,'(')-1));
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(',',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$firstName = isset($pat_name[1]) ? trim($pat_name[1]) : ''; 							
							if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
								$firstNameArr = explode(' ',$firstName);
								$firstName = $firstNameArr[0];
							}
							$tempArr = array('key' => 'first_name', 'value' => $firstName);
							//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						
					}
					else if(stripos($line,'Author:') !== false && stripos($line,'Service:') !== false && stripos($line,'Author Type:') !== false){
						$elements = explode("\t",$line);
						$posService = stripos($line,'Service:');
						$posAuthType = stripos($line,'Author Type:');
						$strService = substr($line,$posService,($posAuthType-$posService));
						$strService = trim(str_ireplace('Service:','',$strService));
						$tempArr = array('testName' => 'service', 'value' => $strService,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);//varchar
					}
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}
			else if(stripos($reportContents,'UAMS Appointment Center')!==false){
				$faxType = 'UAMS Appointment Center';
				$faxTypeMain = 'UAMS Appointment Center';
				
				if(stripos($reportContents,'UAMS CONFIDENTIALITY NOTICE') !== false){
					$posStart = stripos($reportContents,'UAMS CONFIDENTIALITY NOTICE');	
					$reportContents = substr($reportContents,$posStart); //To end of contents
				}
				
				if(stripos($reportContents,'UAMS Enterprise Fax') !== false){				
					$posStart = stripos($reportContents,'UAMS Enterprise Fax');	
					$reportContents = substr($reportContents,$posStart);
				}
				
				if(stripos($reportContents,"Patient's Name:") !== false){
					$posStart = stripos($reportContents,"Patient's Name:");	
					$reportContents = substr($reportContents,$posStart);
					$rpt_lines = explode("\n",$reportContents);
					$field_val = $rpt_lines[0];//Patient's Name:LINDLY, JESSICA L [001587951]
					$field_val = trim(str_ireplace("Patient's Name:",'',$field_val));
					
					$pat_name = explode(',',$field_val);//Smith, Alyssa Carol (Lastname, FirstName Middle)
					if(!empty($pat_name) && count($pat_name) > 0){
						$firstName = isset($pat_name[1]) ? trim($pat_name[1]) : ''; 							
						if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
							$firstNameArr = explode(' ',$firstName);
							$firstName = $firstNameArr[0];
						}
						$tempArr = array('key' => 'first_name', 'value' => $firstName);
						//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
						
						$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
					else{
						$tempArr = array('key' => 'first_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
						$tempArr = array('key' => 'last_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
				}
				
			}
			else if(stripos($reportContents,'UAMS Medical Center UAMS CT DEPARTMENT')!==false){
				$faxType = 'RADIOLOGICAL EXAMINATION';
				$faxTypeMain = 'UAMS CT';
				$titleKeys = array('EXAM DESCRIPTION:','EXAM DESCRIPTION :','HISTORY:','HISTORY :','COMPARISON:','COMPARISON :','TECHNIQUE:','TECHNIQUE :','FINDINGS:','FINDINGS :','IMPRESSION:','IMPRESSION :','CONCLUSION:','CONCLUSION :','INDICATION:','INDICATION :','RECOMMENDATION:','RECOMMENDATION :','Transcribed by','Signed by','Electronically Signed By');
				if(stripos($reportContents,'UAMS CONFIDENTIALITY NOTICE') !== false){
					$posStart = stripos($reportContents,'UAMS CONFIDENTIALITY NOTICE');	
					$reportContents = substr($reportContents,$posStart); //To end of contents
				}
				
				if(stripos($reportContents,'UAMS Enterprise Fax') !== false){				
					$posStart = stripos($reportContents,'UAMS Enterprise Fax');	
					$reportContents = substr($reportContents,$posStart);
				}
				$posStart = stripos($reportContents,'UAMS Medical Center UAMS CT DEPARTMENT');
				$reportContents = substr($reportContents,$posStart);
				////////////////////////////////////////////////////////
				$posStart = stripos($reportContents,'Imaging Result');
				$posEnd = stripos($reportContents,'EXAM DESCRIPTION');
				$headerContents = substr($reportContents,$posStart,($posEnd - $posStart));
				$rpt_lines = explode("\n",$headerContents);
				foreach($rpt_lines as $key => $line){
					$elements = explode("\t",$line);
					/*echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';				
					print "<pre>";
					print_r($elements);
					print "</pre>";*/
					
					if(stripos($line,'Name:') !== false && stripos($line,'DOB:') !== false){
						$field_name = 'Patient:';//Name:  (Lastname, FirstName) Barton, Steven E
						$field_val = '';
						$nextKey = $key+1;		
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = $rpt_lines[$nextKey];								
						}
						$elements = explode("\t",$field_val);					
						$field_val = isset($elements[0]) ? trim($elements[0]): '';					
						//$field_val = trim(str_ireplace('Patient:','',$field_val));					
						
						$pat_name = explode(',',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$firstName = isset($pat_name[1]) ? trim($pat_name[1]) : ''; 							
							if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
								$firstNameArr = explode(' ',$firstName);
								$firstName = $firstNameArr[0];
							}
							$tempArr = array('key' => 'first_name', 'value' => $firstName);
							//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						/////////////////////////////
						$field_name = 'dob';//9/28/1963
						$field_val = isset($elements[1]) ? trim($elements[1]): '';
						//$field_val = trim(str_ireplace('MR#:','',$field_val));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);				
					}					
					else if(stripos($line,'Procedures Performed:') !== false && stripos($line,'Exam Time:') !== false){					
						//Procedures Performed:\tExam Time:\tReason for Exam:\tDiagnosis:\
						$field_name = 'Procedure DATE/TIME:'; 
						$field_val = '';
						$nextKey = $key+1;		
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = $rpt_lines[$nextKey];								
						}
						$elements = explode("\t",$field_val);
						
						$field_val = isset($elements[1]) ? trim($elements[1]): '';// 07/20/2020 11:11 PM
						//$posCollect = stripos($line,'Collected:');
						//$field_val = substr($line,$posCollect);//
						//$field_val = trim(str_ireplace('Accession','',$field_val));
						//$field_val = trim(str_ireplace('#','',$field_val));
						//$field_val = trim(str_ireplace(':','',$field_val));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);				
					}					
									
				}// end foreach
				/////////////////////////////////////////////////////////
				$posStart = stripos($reportContents,'EXAM DESCRIPTION');
				//$posEnd = stripos($reportContents,'EXAM DESCRIPTION');
				//$bodyContents = substr($reportContents,$posStart,($posEnd - $posStart));
				$bodyContents = substr($reportContents,$posStart);//To end of contents				
				$rpt_lines = explode("\n",$bodyContents);
				
				$index = 0;
				$foundItems = array();
				//array_push($foundItems, 'PROCEDURE:');
				
				foreach($titleKeys as $item){			
					if(stripos($bodyContents,$item) !== false){
						$itemPos = stripos($bodyContents,$item);
						$foundItems[$index]['element'] = $item;
						$foundItems[$index]['pos'] = $itemPos;
						$index++;
					}
				}
				
				usort($foundItems, function ($item1, $item2) {
					return $item1['pos'] <=> $item2['pos'];
				});
				
				foreach($foundItems as $key => $row){
					$nextKey = $key + 1;
					//echo 'Next Key =>'.$nextKey.'<br>';
					$startPos = $row['pos'];
					
					$text = '';
					if (array_key_exists($nextKey,$foundItems)){
						//$nextLine = trim($rpt_lines[$nextKey]);
						$endPos = $foundItems[$nextKey]['pos'] - $startPos;
						//echo 'End Pos =>'.$endPos.'<br>';
						$text = substr($bodyContents,$startPos,$endPos);
					}
					else{
						$text = substr($bodyContents,$startPos);//end of contents
					}
					//'Gynecological History:','Family History:'
					/*if(stripos($text,'Page:') !== false){
						$pagePos = stripos($text,'Page:');					
					}*/
					
					//echo $text.'<br>--------------------------------------------------<br>';
					if($row['element']=='EXAM DESCRIPTION:' || $row['element']=='EXAM DESCRIPTION :'){
						//echo '<br><b>PROCEDURE:</b><br>';
						$text = trim(str_ireplace('EXAM DESCRIPTION:','',$text));
						$text = trim(str_ireplace('EXAM DESCRIPTION :','',$text));
						//echo $text.'<br>';
						$field_name = 'PROCEDURE:';
						$field_val = $text;
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
						//$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						//array_push($responseArray['rpt_detail'], $tempArr);
					}					
					else if($row['element']=='COMPARISON:' || $row['element']=='COMPARISON :'){
						//echo '<br><b>COMPARISON:</b><br>';
						$text = trim(str_ireplace('COMPARISON:','',$text));
						$text = trim(str_ireplace('COMPARISON :','',$text));
						//echo $text.'<br>';
						$field_name = 'COMPARISON:';
						$field_val = $text;
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					else if($row['element']=='HISTORY:' || $row['element']=='HISTORY :'){
						//echo '<br><b>History:</b><br>';
						$text = trim(str_ireplace('HISTORY:','',$text));
						$text = trim(str_ireplace('HISTORY :','',$text));
						//echo $text.'<br>';
						$field_name = 'HISTORY:';
						$field_val = $text;
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					else if($row['element']=='TECHNIQUE:' || $row['element']=='TECHNIQUE :'){
						//echo '<br><b>TECHNIQUE:</b><br>';
						$text = trim(str_ireplace('TECHNIQUE:','',$text));
						$text = trim(str_ireplace('TECHNIQUE :','',$text));
						//echo $text.'<br>';
						$field_name = 'TECHNIQUE:';
						$field_val = $text;
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					else if($row['element']=='FINDINGS:' || $row['element']=='FINDINGS :'){
						//echo '<br><b>FINDINGS</b><br>';
						$text = trim(str_ireplace('FINDINGS:','',$text));
						$text = trim(str_ireplace('FINDINGS :','',$text));
						//echo $text.'<br>';
						$field_name = 'FINDINGS:';
						$field_val = $text;
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					else if($row['element']=='IMPRESSION:' || $row['element']=='IMPRESSION :'){
						//echo '<br><b>IMPRESSION:</b><br>';
						$text = trim(str_ireplace('IMPRESSION:','',$text));
						$text = trim(str_ireplace('IMPRESSION :','',$text));
						//echo $text.'<br>';
						$field_name = 'IMPRESSION:';
						$field_val = $text;
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					else if($row['element']=='RECOMMENDATION:'){
						//echo '<br><b>RECOMMENDATION:</b><br>';
						$text = trim(str_ireplace('RECOMMENDATION:','',$text));
						//echo $text.'<br>';
						$field_name = 'RECOMMENDATION:';
						$field_val = $text;
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					else if($row['element']=='Transcribed by'){
						//echo '<br><b>Transcribed by</b><br>';
						/*$text = trim(str_ireplace('Current Medications:','',$text));
						$field_name = 'current_medications';
						$field_val = $text;
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						$medsArr = explode("\n",$text);
						foreach($medsArr as $row){
							$medElements = explode(';',$row);
							print "<pre>";print_r($medElements);print "</pre>";
						}*/
						continue;
					}
					else if($row['element']=='Signed by'){
						//echo '<br><b>Signed by:</b><br>';
						/*$text = trim(str_ireplace('Review Of System:','',$text));
						$field_name = 'ros_note';
						$field_val = $text;
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);*/
						continue;
					}
					else if($row['element']=='Electronically Signed By'){
						//echo '<br><b>Electronically Signed By:</b><br>';
						/*$text = trim(str_ireplace('Review Of System:','',$text));
						$field_name = 'ros_note';
						$field_val = $text;
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);*/
						continue;
					}
				}
				/////////////////////////////////////////////////////////////////////////
								
			}
			else if(stripos($reportContents,'UAMS Medical Center UAMS MRI Department')!==false){
				$faxType = 'RADIOLOGICAL EXAMINATION';
				$faxTypeMain = 'UAMS MRI';
				$titleKeys = array('EXAM DESCRIPTION:','EXAM DESCRIPTION :','HISTORY:','HISTORY :','COMPARISON:','COMPARISON :','TECHNIQUE:','TECHNIQUE :','FINDINGS:','FINDINGS :','IMPRESSION:','IMPRESSION :','CONCLUSION:','CONCLUSION :','INDICATION:','INDICATION :','RECOMMENDATION:','RECOMMENDATION :','Transcribed by','Signed by','Electronically Signed By');
				if(stripos($reportContents,'UAMS CONFIDENTIALITY NOTICE') !== false){
					$posStart = stripos($reportContents,'UAMS CONFIDENTIALITY NOTICE');	
					$reportContents = substr($reportContents,$posStart); //To end of contents
				}
				
				if(stripos($reportContents,'UAMS Enterprise Fax') !== false){				
					$posStart = stripos($reportContents,'UAMS Enterprise Fax');	
					$reportContents = substr($reportContents,$posStart);
				}
				$posStart = stripos($reportContents,'UAMS Medical Center UAMS MRI Department');
				$reportContents = substr($reportContents,$posStart);
				////////////////////////////////////////////////////////
				$posStart = stripos($reportContents,'Imaging Result');
				$posEnd = stripos($reportContents,'EXAM DESCRIPTION');
				$headerContents = substr($reportContents,$posStart,($posEnd - $posStart));
				$rpt_lines = explode("\n",$headerContents);
				foreach($rpt_lines as $key => $line){
					$elements = explode("\t",$line);
					/*echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';				
					print "<pre>";
					print_r($elements);
					print "</pre>";*/
					
					if(stripos($line,'Name:') !== false && stripos($line,'DOB:') !== false){
						$field_name = 'Patient:';//Name:  (Lastname, FirstName) Barton, Steven E
						$field_val = '';
						$nextKey = $key+1;		
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = $rpt_lines[$nextKey];								
						}
						$elements = explode("\t",$field_val);					
						$field_val = isset($elements[0]) ? trim($elements[0]): '';					
						//$field_val = trim(str_ireplace('Patient:','',$field_val));					
						
						$pat_name = explode(',',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$firstName = isset($pat_name[1]) ? trim($pat_name[1]) : ''; 							
							if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
								$firstNameArr = explode(' ',$firstName);
								$firstName = $firstNameArr[0];
							}
							$tempArr = array('key' => 'first_name', 'value' => $firstName);
							//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						/////////////////////////////
						$field_name = 'dob';//9/28/1963
						$field_val = isset($elements[1]) ? trim($elements[1]): '';
						//$field_val = trim(str_ireplace('MR#:','',$field_val));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);				
					}					
					else if(stripos($line,'Procedures Performed:') !== false && stripos($line,'Exam Time:') !== false){					
						//Procedures Performed:\tExam Time:\tReason for Exam:\tDiagnosis:\
						$field_name = 'Procedure DATE/TIME:'; 
						$field_val = '';
						$nextKey = $key+1;		
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = $rpt_lines[$nextKey];								
						}
						$elements = explode("\t",$field_val);
						
						$field_val = isset($elements[1]) ? trim($elements[1]): '';// 07/20/2020 11:11 PM
						//$posCollect = stripos($line,'Collected:');
						//$field_val = substr($line,$posCollect);//
						//$field_val = trim(str_ireplace('Accession','',$field_val));
						//$field_val = trim(str_ireplace('#','',$field_val));
						//$field_val = trim(str_ireplace(':','',$field_val));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);				
					}					
									
				}// end foreach
				/////////////////////////////////////////////////////////
				$posStart = stripos($reportContents,'EXAM DESCRIPTION');
				//$posEnd = stripos($reportContents,'EXAM DESCRIPTION');
				//$bodyContents = substr($reportContents,$posStart,($posEnd - $posStart));
				$bodyContents = substr($reportContents,$posStart);//To end of contents				
				$rpt_lines = explode("\n",$bodyContents);
				
				$index = 0;
				$foundItems = array();
				//array_push($foundItems, 'PROCEDURE:');
				
				foreach($titleKeys as $item){			
					if(stripos($bodyContents,$item) !== false){
						$itemPos = stripos($bodyContents,$item);
						$foundItems[$index]['element'] = $item;
						$foundItems[$index]['pos'] = $itemPos;
						$index++;
					}
				}
				
				usort($foundItems, function ($item1, $item2) {
					return $item1['pos'] <=> $item2['pos'];
				});
				
				foreach($foundItems as $key => $row){
					$nextKey = $key + 1;
					//echo 'Next Key =>'.$nextKey.'<br>';
					$startPos = $row['pos'];
					
					$text = '';
					if (array_key_exists($nextKey,$foundItems)){
						//$nextLine = trim($rpt_lines[$nextKey]);
						$endPos = $foundItems[$nextKey]['pos'] - $startPos;
						//echo 'End Pos =>'.$endPos.'<br>';
						$text = substr($bodyContents,$startPos,$endPos);
					}
					else{
						$text = substr($bodyContents,$startPos);//end of contents
					}
					//'Gynecological History:','Family History:'
					/*if(stripos($text,'Page:') !== false){
						$pagePos = stripos($text,'Page:');					
					}*/
					
					//echo $text.'<br>--------------------------------------------------<br>';
					if($row['element']=='EXAM DESCRIPTION:' || $row['element']=='EXAM DESCRIPTION :'){
						//echo '<br><b>PROCEDURE:</b><br>';
						$text = trim(str_ireplace('EXAM DESCRIPTION:','',$text));
						$text = trim(str_ireplace('EXAM DESCRIPTION :','',$text));
						//echo $text.'<br>';
						$field_name = 'PROCEDURE:';
						$field_val = $text;
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
						//$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						//array_push($responseArray['rpt_detail'], $tempArr);
					}					
					else if($row['element']=='COMPARISON:' || $row['element']=='COMPARISON :'){
						//echo '<br><b>COMPARISON:</b><br>';
						$text = trim(str_ireplace('COMPARISON:','',$text));
						$text = trim(str_ireplace('COMPARISON :','',$text));
						//echo $text.'<br>';
						$field_name = 'COMPARISON:';
						$field_val = $text;
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					else if($row['element']=='HISTORY:' || $row['element']=='HISTORY :'){
						//echo '<br><b>History:</b><br>';
						$text = trim(str_ireplace('HISTORY:','',$text));
						$text = trim(str_ireplace('HISTORY :','',$text));
						//echo $text.'<br>';
						$field_name = 'HISTORY:';
						$field_val = $text;
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					else if($row['element']=='TECHNIQUE:' || $row['element']=='TECHNIQUE :'){
						//echo '<br><b>TECHNIQUE:</b><br>';
						$text = trim(str_ireplace('TECHNIQUE:','',$text));
						$text = trim(str_ireplace('TECHNIQUE :','',$text));
						//echo $text.'<br>';
						$field_name = 'TECHNIQUE:';
						$field_val = $text;
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					else if($row['element']=='FINDINGS:' || $row['element']=='FINDINGS :'){
						//echo '<br><b>FINDINGS</b><br>';
						$text = trim(str_ireplace('FINDINGS:','',$text));
						$text = trim(str_ireplace('FINDINGS :','',$text));
						//echo $text.'<br>';
						$field_name = 'FINDINGS:';
						$field_val = $text;
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					else if($row['element']=='IMPRESSION:' || $row['element']=='IMPRESSION :'){
						//echo '<br><b>IMPRESSION:</b><br>';
						$text = trim(str_ireplace('IMPRESSION:','',$text));
						$text = trim(str_ireplace('IMPRESSION :','',$text));
						//echo $text.'<br>';
						$field_name = 'IMPRESSION:';
						$field_val = $text;
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					else if($row['element']=='RECOMMENDATION:'){
						//echo '<br><b>RECOMMENDATION:</b><br>';
						$text = trim(str_ireplace('RECOMMENDATION:','',$text));
						//echo $text.'<br>';
						$field_name = 'RECOMMENDATION:';
						$field_val = $text;
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					else if($row['element']=='Transcribed by'){
						//echo '<br><b>Transcribed by</b><br>';
						/*$text = trim(str_ireplace('Current Medications:','',$text));
						$field_name = 'current_medications';
						$field_val = $text;
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						$medsArr = explode("\n",$text);
						foreach($medsArr as $row){
							$medElements = explode(';',$row);
							print "<pre>";print_r($medElements);print "</pre>";
						}*/
						continue;
					}
					else if($row['element']=='Signed by'){
						//echo '<br><b>Signed by:</b><br>';
						/*$text = trim(str_ireplace('Review Of System:','',$text));
						$field_name = 'ros_note';
						$field_val = $text;
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);*/
						continue;
					}
					else if($row['element']=='Electronically Signed By'){
						//echo '<br><b>Electronically Signed By:</b><br>';
						/*$text = trim(str_ireplace('Review Of System:','',$text));
						$field_name = 'ros_note';
						$field_val = $text;
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);*/
						continue;
					}
				}
				/////////////////////////////////////////////////////////////////////////
								
			}
			else if(stripos($reportContents,'UAMS Medical Center UAMS Breast Center/Mammography Department')!==false){
				$faxType = 'RADIOLOGICAL EXAMINATION';
				$faxTypeMain = 'UAMS Mammography';
				$titleKeys = array('EXAM DESCRIPTION:','EXAM DESCRIPTION :','HISTORY:','HISTORY :','COMPARISON:','COMPARISON :','TECHNIQUE:','TECHNIQUE :','FINDINGS:','FINDINGS :','IMPRESSION:','IMPRESSION :','CONCLUSION:','CONCLUSION :','INDICATION:','INDICATION :','RECOMMENDATION:','RECOMMENDATION :','Transcribed by','Signed by','Electronically Signed By');
				if(stripos($reportContents,'UAMS CONFIDENTIALITY NOTICE') !== false){
					$posStart = stripos($reportContents,'UAMS CONFIDENTIALITY NOTICE');	
					$reportContents = substr($reportContents,$posStart); //To end of contents
				}
				
				if(stripos($reportContents,'UAMS Enterprise Fax') !== false){				
					$posStart = stripos($reportContents,'UAMS Enterprise Fax');	
					$reportContents = substr($reportContents,$posStart);
				}
				$posStart = stripos($reportContents,'UAMS Medical Center UAMS Breast Center/Mammography Department');
				$reportContents = substr($reportContents,$posStart);
				////////////////////////////////////////////////////////
				$posStart = stripos($reportContents,'Imaging Result');
				$posEnd = stripos($reportContents,'EXAM DESCRIPTION');
				$headerContents = substr($reportContents,$posStart,($posEnd - $posStart));
				$rpt_lines = explode("\n",$headerContents);
				foreach($rpt_lines as $key => $line){
					$elements = explode("\t",$line);
					/*echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';				
					print "<pre>";
					print_r($elements);
					print "</pre>";*/
					
					if(stripos($line,'Name:') !== false && stripos($line,'DOB:') !== false){
						$field_name = 'Patient:';//Name:  (Lastname, FirstName) Barton, Steven E
						$field_val = '';
						$nextKey = $key+1;		
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = $rpt_lines[$nextKey];								
						}
						$elements = explode("\t",$field_val);					
						$field_val = isset($elements[0]) ? trim($elements[0]): '';					
						//$field_val = trim(str_ireplace('Patient:','',$field_val));					
						
						$pat_name = explode(',',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$firstName = isset($pat_name[1]) ? trim($pat_name[1]) : ''; 							
							if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
								$firstNameArr = explode(' ',$firstName);
								$firstName = $firstNameArr[0];
							}
							$tempArr = array('key' => 'first_name', 'value' => $firstName);
							//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						/////////////////////////////
						$field_name = 'dob';//9/28/1963
						$field_val = isset($elements[1]) ? trim($elements[1]): '';
						//$field_val = trim(str_ireplace('MR#:','',$field_val));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);				
					}					
					else if(stripos($line,'Procedures Performed:') !== false && stripos($line,'Exam Time:') !== false){					
						//Procedures Performed:\tExam Time:\tReason for Exam:\tDiagnosis:\
						$field_name = 'Procedure DATE/TIME:'; 
						$field_val = '';
						$nextKey = $key+1;		
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = $rpt_lines[$nextKey];								
						}
						$elements = explode("\t",$field_val);
						
						$field_val = isset($elements[1]) ? trim($elements[1]): '';// 07/20/2020 11:11 PM
						//$posCollect = stripos($line,'Collected:');
						//$field_val = substr($line,$posCollect);//
						//$field_val = trim(str_ireplace('Accession','',$field_val));
						//$field_val = trim(str_ireplace('#','',$field_val));
						//$field_val = trim(str_ireplace(':','',$field_val));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);				
					}					
									
				}// end foreach
				/////////////////////////////////////////////////////////
				$posStart = stripos($reportContents,'EXAM DESCRIPTION');
				//$posEnd = stripos($reportContents,'EXAM DESCRIPTION');
				//$bodyContents = substr($reportContents,$posStart,($posEnd - $posStart));
				$bodyContents = substr($reportContents,$posStart);//To end of contents				
				$rpt_lines = explode("\n",$bodyContents);
				
				$index = 0;
				$foundItems = array();
				//array_push($foundItems, 'PROCEDURE:');
				
				foreach($titleKeys as $item){			
					if(stripos($bodyContents,$item) !== false){
						$itemPos = stripos($bodyContents,$item);
						$foundItems[$index]['element'] = $item;
						$foundItems[$index]['pos'] = $itemPos;
						$index++;
					}
				}
				
				usort($foundItems, function ($item1, $item2) {
					return $item1['pos'] <=> $item2['pos'];
				});
				
				foreach($foundItems as $key => $row){
					$nextKey = $key + 1;
					//echo 'Next Key =>'.$nextKey.'<br>';
					$startPos = $row['pos'];
					
					$text = '';
					if (array_key_exists($nextKey,$foundItems)){
						//$nextLine = trim($rpt_lines[$nextKey]);
						$endPos = $foundItems[$nextKey]['pos'] - $startPos;
						//echo 'End Pos =>'.$endPos.'<br>';
						$text = substr($bodyContents,$startPos,$endPos);
					}
					else{
						$text = substr($bodyContents,$startPos);//end of contents
					}
					//'Gynecological History:','Family History:'
					/*if(stripos($text,'Page:') !== false){
						$pagePos = stripos($text,'Page:');					
					}*/
					
					//echo $text.'<br>--------------------------------------------------<br>';
					if($row['element']=='EXAM DESCRIPTION:' || $row['element']=='EXAM DESCRIPTION :'){
						//echo '<br><b>PROCEDURE:</b><br>';
						$text = trim(str_ireplace('EXAM DESCRIPTION:','',$text));
						$text = trim(str_ireplace('EXAM DESCRIPTION :','',$text));
						//echo $text.'<br>';
						$field_name = 'PROCEDURE:';
						$field_val = $text;
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
						//$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						//array_push($responseArray['rpt_detail'], $tempArr);
					}					
					else if($row['element']=='COMPARISON:' || $row['element']=='COMPARISON :'){
						//echo '<br><b>COMPARISON:</b><br>';
						$text = trim(str_ireplace('COMPARISON:','',$text));
						$text = trim(str_ireplace('COMPARISON :','',$text));
						//echo $text.'<br>';
						$field_name = 'COMPARISON:';
						$field_val = $text;
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					else if($row['element']=='HISTORY:' || $row['element']=='HISTORY :'){
						//echo '<br><b>History:</b><br>';
						$text = trim(str_ireplace('HISTORY:','',$text));
						$text = trim(str_ireplace('HISTORY :','',$text));
						//echo $text.'<br>';
						$field_name = 'HISTORY:';
						$field_val = $text;
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					else if($row['element']=='TECHNIQUE:' || $row['element']=='TECHNIQUE :'){
						//echo '<br><b>TECHNIQUE:</b><br>';
						$text = trim(str_ireplace('TECHNIQUE:','',$text));
						$text = trim(str_ireplace('TECHNIQUE :','',$text));
						//echo $text.'<br>';
						$field_name = 'TECHNIQUE:';
						$field_val = $text;
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					else if($row['element']=='FINDINGS:' || $row['element']=='FINDINGS :'){
						//echo '<br><b>FINDINGS</b><br>';
						$text = trim(str_ireplace('FINDINGS:','',$text));
						$text = trim(str_ireplace('FINDINGS :','',$text));
						//echo $text.'<br>';
						$field_name = 'FINDINGS:';
						$field_val = $text;
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					else if($row['element']=='IMPRESSION:' || $row['element']=='IMPRESSION :'){
						//echo '<br><b>IMPRESSION:</b><br>';
						$text = trim(str_ireplace('IMPRESSION:','',$text));
						$text = trim(str_ireplace('IMPRESSION :','',$text));
						//echo $text.'<br>';
						$field_name = 'IMPRESSION:';
						$field_val = $text;
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					else if($row['element']=='RECOMMENDATION:'){
						//echo '<br><b>RECOMMENDATION:</b><br>';
						$text = trim(str_ireplace('RECOMMENDATION:','',$text));
						//echo $text.'<br>';
						$field_name = 'RECOMMENDATION:';
						$field_val = $text;
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					else if($row['element']=='Transcribed by'){
						//echo '<br><b>Transcribed by</b><br>';
						/*$text = trim(str_ireplace('Current Medications:','',$text));
						$field_name = 'current_medications';
						$field_val = $text;
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						$medsArr = explode("\n",$text);
						foreach($medsArr as $row){
							$medElements = explode(';',$row);
							print "<pre>";print_r($medElements);print "</pre>";
						}*/
						continue;
					}
					else if($row['element']=='Signed by'){
						//echo '<br><b>Signed by:</b><br>';
						/*$text = trim(str_ireplace('Review Of System:','',$text));
						$field_name = 'ros_note';
						$field_val = $text;
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);*/
						continue;
					}
					else if($row['element']=='Electronically Signed By'){
						//echo '<br><b>Electronically Signed By:</b><br>';
						/*$text = trim(str_ireplace('Review Of System:','',$text));
						$field_name = 'ros_note';
						$field_val = $text;
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);*/
						continue;
					}
				}
				/////////////////////////////////////////////////////////////////////////
								
			}
			else if(stripos($reportContents,'Summary of Care')!==false || stripos($reportContents,'ED Encounter Summary')!==false  || stripos($reportContents,'UAMS IP Summary')!==false){
				$faxType = 'Summary of Care Report';
				$faxTypeMain = 'Progress Note';	
				
				if(stripos($reportContents,'ED Encounter Summary')!==false){
					$faxType = 'ED Encounter Summary';
					//$faxTypeMain = 'Progress Note';
					$faxTypeMain = 'ED Encounter Summary';
				}
				else if(stripos($reportContents,'UAMS IP Summary')!==false){
					$faxType = 'UAMS IP Summary of Care Report';
					//$faxTypeMain = 'Progress Note';
					$faxTypeMain = 'UAMS IP Summary';
				}
				
				if(stripos($reportContents,'Fax Cover Sheet')!==false){
					$posStart = stripos($reportContents,'Fax Cover Sheet');
					$posEnd = stripos($reportContents,'NOTES:');
					$posEnd = stripos($reportContents,'Instructions:');
					
				}
				$posStart = stripos($reportContents,'Summary of Care');	
				$reportContents = substr($reportContents,$posStart); //To end of contents
				
				$posStart = stripos($reportContents,'Additional Information:');	
				$reportContents = substr($reportContents,$posStart); //To end of contents
					
				if(stripos($reportContents,'UAMS Enterprise') !==false || stripos($reportContents,'Fax Server') !==false){
					$posStart = stripos($reportContents,'UAMS Enterprise');	//On each Page Header
					if(stripos($reportContents,'Admission Information')!==false){
						$posEnd = stripos($reportContents,'Admission Information');//Exist only once
						$reportContents = substr($reportContents,$posStart,($posEnd - $posStart)); //To end of contents
					}
					else if(stripos($reportContents,'ED Arrival Information')!==false){
						$posEnd = stripos($reportContents,'ED Arrival Information');//Exist only once
						$reportContents = substr($reportContents,$posStart,($posEnd - $posStart)); //To end of contents
					}
					else{
						$reportContents = substr($reportContents,$posStart); //To end of contents
					}									
				}
				$rpt_lines = explode("\n",$reportContents);
				foreach($rpt_lines as $key => $line){					
					
					if(stripos($line,'MRN:') !== false){
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? trim($elements[0]) : '';	
						
						$pat_name = explode(',',$field_val);//Smith, Alyssa Carol (Lastname, FirstName Middle)
						if(!empty($pat_name) && count($pat_name) > 0){
							$firstName = isset($pat_name[1]) ? trim($pat_name[1]) : ''; 							
							if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
								$firstNameArr = explode(' ',$firstName);
								$firstName = $firstNameArr[0];
							}
							$tempArr = array('key' => 'first_name', 'value' => $firstName);
							//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						
					}
					else if(stripos($line,'Basic Information') !== false){
						$field_name = 'dob';
						$nextKey =$key+2;
						$nextLine = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$nextLine = trim($rpt_lines[$nextKey]);
						}
						$elements = explode("\t",$nextLine);
						$field_val = isset($elements[0]) ? $elements[0]: '';//6/10/1981
						
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}
			else if(stripos($reportContents,'HEAD AND NECK ONCOLOGY CLINIC')!==false){
				$faxType = 'Progress Note';
				$faxTypeMain = 'Progress Note';
					
				if(stripos($reportContents,'Date of Birth:') !== false)
				{						
					$field_name = 'dob';
					$posDOB = stripos($reportContents,'Date of Birth:');//MRN: 001587951, DOB: 8/21/1977, Sex: F
					$field_val = substr($reportContents,$posDOB);//to the end of line
					$elements = explode("\n",$field_val);	
					//$elements = explode("\t",$field_val);
					$field_val = isset($elements[0]) ? $elements[0]: '';//Date of Birth: 08/28/1977
					$field_val = trim(str_ireplace('Date of Birth:','',$field_val));							
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);
				}
				if(stripos($reportContents,'Patient:') !== false){
					$posPAT = stripos($reportContents,'Patient:');//Shelia A Weathers (FirstName Mid Lastname)
					$field_val = substr($reportContents,$posPAT);//to the end of line
					$elements = explode("\n",$field_val);
					$field_val = isset($elements[0]) ? $elements[0]: '';
					$field_val = trim(str_ireplace('Patient:','',$field_val));					
					//$tempArr = array('key' => $field_name, 'value' => $field_val);
					//array_push($responseArray['rpt_header'], $tempArr);
					
					$pat_name = explode(' ',$field_val);
					if(!empty($pat_name) && count($pat_name) > 0){
						$firstName = isset($pat_name[0]) ? trim($pat_name[0]) : ''; 							
						/*if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
							$firstNameArr = explode(' ',$firstName);
							$firstName = $firstNameArr[0];
						}*/
						$tempArr = array('key' => 'first_name', 'value' => $firstName);
						//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
						$lastName = '';
						if(isset($pat_name[2])){
							$lastName = trim($pat_name[2]); 
						}
						else if(isset($pat_name[1])){
							$lastName = trim($pat_name[1]); 
						}
						
						$tempArr = array('key' => 'last_name', 'value' => $lastName);
						array_push($responseArray['rpt_header'], $tempArr);
					}
					else{
						$tempArr = array('key' => 'first_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
						$tempArr = array('key' => 'last_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
					}	
				}
				
				/*echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
				$elements = explode("\t",$line);
				
				print "<pre>";
				print_r($elements);
				print "</pre>";*/
			}
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	$responseArray['department'] = $department;
	
	$tempArr = array('key' => 'department:', 'value' => $department);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	return $responseArray;
}

function surgicalClinicARHandler($fileName='',$reportContents='', $configurationName = '',$email_addr=''){
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Hospital';//Medical Center/UAMS Hospital
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		/*print "<pre>";
		print_r($rpt_lines);
		print "</pre>";*/
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			//if(stripos($reportContents,'University of Arkansas for Medical Sciences')!== false)
			
			$faxType = 'Progress Note';
			$faxTypeMain = 'Progress Note';	//office evaluation			
			foreach($rpt_lines as $key => $line)
			{					
				$elements = explode("\t",$line);
				if(isset($elements[0]) && $elements[0] =='THE SURGICAL CLINIC OF CENTRAL ARKANSAS' && $headerStart==1)	{
					$field_val = isset($elements[1]) ? trim($elements[1]) : ''; //Christina Pupo | 56yo F | 02/12/1963 | #492996 
					if(!empty($field_val) && count(explode('|',$field_val)) > 3){
						$pat_data = explode('|',$field_val);
						$field_val = isset($pat_data[0]) ? trim($pat_data[0]) : ''; //fName lName
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$firstName = isset($pat_name[0]) ? trim($pat_name[0]) : '';
							$lastName = isset($pat_name[1]) ? trim($pat_name[1]) : ''; 							
							if(!empty($lastName) && count(explode(' ',$lastName)) > 1){
								$lastNameArr = explode(' ',$lastName);
								$lastName = $lastNameArr[0];
							}
							$tempArr = array('key' => 'first_name', 'value' => $firstName);
							//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$tempArr = array('key' => 'last_name', 'value' => $lastName);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						
						$field_name = 'dob';
						$field_val = isset($pat_data[2]) ? trim($pat_data[2]) : ''; //02/12/1963 (mm/dd/YYYY)
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
					
						$headerStart = 0;
					}
				}
				/*
				if(stripos($line,'DOB:') !== false){						
					$field_name = 'dob';
					$posDOB = stripos($line,'DOB:');//MRN: 001587951, DOB: 8/21/1977, Sex: F
					$lineContents = substr($line,$posDOB);//to the end of line						
					$elements = explode("\t",$lineContents);
					$field_val = isset($elements[0]) ? $elements[0]: '';//FERNANDEZ, CAMELIA	
					$field_val = trim(str_ireplace('DOB:','',$field_val));							
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);
					
					$field_val = substr($line,0,($posDOB-1));	//Silva, Andres C	(Lastname, FirstName)			
					$field_val = substr($field_val,0,(stripos($field_val,'(')-1));
					//$tempArr = array('key' => $field_name, 'value' => $field_val);
					//array_push($responseArray['rpt_header'], $tempArr);
					
					$pat_name = explode(',',$field_val);
					if(!empty($pat_name) && count($pat_name) > 0){
						$firstName = isset($pat_name[1]) ? trim($pat_name[1]) : ''; 							
						if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
							$firstNameArr = explode(' ',$firstName);
							$firstName = $firstNameArr[0];
						}
						$tempArr = array('key' => 'first_name', 'value' => $firstName);
						//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
						
						$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
					else{
						$tempArr = array('key' => 'first_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
						$tempArr = array('key' => 'last_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
					
				}
				*/
				echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
				$elements = explode("\t",$line);
				
				print "<pre>";
				print_r($elements);
				print "</pre>";
				
			}//end foreach $rpt_lines			
								
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	return $responseArray;
}

function stVincentHeartClinicARHandler($fileName='',$reportContents='', $configurationName = '',$email_addr=''){
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Hospital';//Medical Center/UAMS Hospital
	$faxType = '';
	//'Date of visit:,DOB:,Age:, Medical record number:, PROBLEM LIST, DRUG ALLERGIES & SENSITIVITY LIST, MEDICATIONS, CHIEF COMPLAINTS, HISTORY OF PRESENT ILLNESS, PAST HISTORY,REVIEW OF SYSTEMS,PHYSICAL EXAMINATION, VITAL SIGNS,CONSTITUTIONAL,IMPRESSION/PLAN,PLAN:,TODAY'S ORDERS'
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		/*print "<pre>";
		print_r($rpt_lines);
		print "</pre>";*/
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			//if(stripos($reportContents,'University of Arkansas for Medical Sciences')!== false)
			
			$faxType = 'Progress Note';
			$faxTypeMain = 'Progress Note';	//office evaluation	
				
			$faxType = 'Progress Note';
			$faxTypeMain = 'Cardiac';
			
			foreach($rpt_lines as $key => $line)
			{					
				
				if(stripos($line,'Date of visit:') !== false)
				{
					$field_name = 'dos';//date of service => visit_date
					//$elements = explode("\t",$line);
					$field_val = trim(str_ireplace('Date of visit:','',$line));
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);
					////////////////////////////////////////////
					$prevKey = $key - 1;
					$field_val = isset($rpt_lines[$prevKey]) ? $rpt_lines[$prevKey] : '';//Farooq, Muhammad (Lname, Fname)					
					$pat_name = explode(',',$field_val);
					if(!empty($pat_name) && count($pat_name) > 0){
						$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
						
						$fname = isset($pat_name[1]) ? trim($pat_name[1]) : '';
						$pat_fname = explode(' ', $fname);
						$fname = isset($pat_fname[0]) ? trim($pat_fname[0]) : '';
						/*if(count($pat_fname) > 1){							
							$fname = isset($pat_fname[2]) ? $pat_fname[2]: $pat_fname[1];
						}*/
						$tempArr = array('key' => 'first_name', 'value' => $fname);
						array_push($responseArray['rpt_header'], $tempArr);
					}
					else{
						$tempArr = array('key' => 'first_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
						$tempArr = array('key' => 'last_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
				}
				else if(stripos($line,'DOB:') !== false && stripos($line,'Age:') !== false)
				{
					$field_name = 'dob';
					$elements = explode("\t",$line);
					$field_val = isset($elements[0]) ? $elements[0] : '';
					$field_val = trim(str_ireplace('DOB:','',$field_val));
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);
					//////////////////////////////////////////////////
					$field_name = 'age';
					$field_val = isset($elements[1]) ? $elements[1] : '';
					$field_val = trim(str_ireplace('Age:','',$field_val));
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);
				}
				else if(stripos($line,'Medical record number:') !== false)
				{
					$field_name = 'MRN:';
					//$elements = explode("\t",$line);
					$field_val = trim(str_ireplace('Medical record number:','',$line));
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);
					
					break;
				}
				
				
				echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
				$elements = explode("\t",$line);
				
				print "<pre>";
				print_r($elements);
				print "</pre>";
				
			}//end foreach $rpt_lines			
								
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	return $responseArray;
}

function orthoArkansasPAHandler($fileName='',$reportContents='', $configurationName = '',$email_addr=''){
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Hospital';//Medical Center/UAMS Hospital/Clinic
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		/*print "<pre>";
		print_r($rpt_lines);
		print "</pre>";*/
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			//if(stripos($reportContents,'University of Arkansas for Medical Sciences')!== false)
			
			$faxType = 'Progress Note';
			$faxTypeMain = 'Progress Note';	//office evaluation			
			foreach($rpt_lines as $key => $line)
			{					
				$elements = explode("\t",$line);				
				//Getting from footer
				if(stripos($line,'OrthoArkansas') !== false && stripos($line,'DOB:') !== false){
					//Lname, Fname
					$field_val = isset($elements[1]) ? trim($elements[1]) : ''; //Bohanan, Shavonne (ID: 1000181161), DOB: 11/25/1979
					////////////////////////////////////////////
					$field_name = 'dob';					
					$dobPos = stripos($field_val,'DOB:');
					$strDob = substr($field_val,$dobPos);//DOB: 11/25/1979    //to end of line
					$dob_val = trim(str_ireplace('DOB:','',$strDob));
					$tempArr = array('key' => $field_name, 'value' => $dob_val);
					array_push($responseArray['rpt_header'], $tempArr);
					//////////////////////////////////////////////////////////////////////
					$field_val = substr($field_val,0,$dobPos-1);					
					$pat_data = $field_val;
					$idPos = stripos($field_val,'(');
					if($idPos!==false){
						$pat_data = substr($field_val,0,$idPos-1);
						$field_val = substr($field_val,$idPos);
						$field_val = trim(str_ireplace('(','',$field_val));
						$field_val = trim(str_ireplace(')','',$field_val));
						$field_val = trim(str_ireplace(',','',$field_val));
						$field_val = trim(str_ireplace('ID:','',$field_val));
						
						$field_name = 'external_id';											
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
					}
					$pat_name = explode(',',$pat_data);//Bohanan, Shavonne
					if(!empty($pat_name) && count($pat_name) > 0){
						$lastName = isset($pat_name[0]) ? trim($pat_name[0]) : '';
						$firstName = isset($pat_name[1]) ? trim($pat_name[1]) : ''; 							
						if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
							$firstNameArr = explode(' ',$firstName);
							$firstName = $firstNameArr[0];
						}
						$tempArr = array('key' => 'first_name', 'value' => $firstName);
						//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
						
						$tempArr = array('key' => 'last_name', 'value' => $lastName);
						array_push($responseArray['rpt_header'], $tempArr);
					}
					else{
						$tempArr = array('key' => 'first_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
						$tempArr = array('key' => 'last_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
					
					break;
				}
				
				echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
				$elements = explode("\t",$line);
				
				print "<pre>";
				print_r($elements);
				print "</pre>";
				
			}//end foreach $rpt_lines			
								
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	
	/*print "<pre>";
	print_r($responseArray);
	print "</pre>";exit;*/
	
	return $responseArray;
}

function gastroArkansasHandler($fileName='',$reportContents='', $configurationName = '',$email_addr=''){
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	/*if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}*/
	if(!empty($tempArr) && count($tempArr) > 2){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		if(isset($tempArr[2]) && is_numeric($tempArr[2])){
			$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		}
		if(isset($tempArr[3]) && is_numeric($tempArr[3])){
			$fax_data_id = isset($tempArr[3]) ? $tempArr[3] : 0;
		}
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Medical';//Medical
	$department = 'Gastroenterology';
	$faxType = 'GI';
	$faxTypeMain = 'Progress Note';//Progress Note/Medical Record
				
	///////////////////////////////////////////
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		
		if(stripos($reportContents,'Patient Clinical Summary') !== false){
			$posStart = stripos($reportContents,'Patient Clinical Summary');	
			$reportContents = substr($reportContents,$posStart); //To end of contents
		}
		
		if(stripos($reportContents,'Location:') !== false){				
			$posEnd = stripos($reportContents,'Location:');	
			$reportContents = substr($reportContents,0,$posEnd);
		}
		
		
		$rpt_lines = explode("\n",$reportContents);
		/*
		print "<pre>";
		print_r($rpt_lines);
		print "</pre>";*/
		
		if(!empty($rpt_lines)){
			foreach($rpt_lines as $key => $line){
				
				if(stripos($line,'Patient Name:') !== false){
					//Guillermo Quintanilla  (Fname Lname)
					$field_val = trim(str_ireplace('Patient Name','',$line));
					$field_val = trim(str_ireplace(':','',$field_val));
					
					$posGender = stripos($field_val,'Gender');
					$field_val = trim(substr($field_val, 0, $posGender));
					
					$pat_name = explode(' ',$field_val);
					if(!empty($pat_name) && count($pat_name) > 0){
						$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
						
						$lastName = isset($pat_name[1]) ? trim($pat_name[1]) : ''; 							
						if(!empty($lastName) && count(explode(' ',$lastName)) > 1){
							$lastNameArr = explode(' ',$lastName);
							$lastName = $lastNameArr[0];
						}
						$tempArr = array('key' => 'last_name', 'value' => $lastName);
						//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
						array_push($responseArray['rpt_header'], $tempArr);						
					}
					else{
						$tempArr = array('key' => 'first_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
						$tempArr = array('key' => 'last_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
					
				}
				else if(stripos($line,'DOB') !== false){						
					$field_name = 'dob';
					$posDOB = stripos($line,'DOB');//Account #:	304879	DOB (age):	1/22/1967 (55)
					$lineContents = substr($line,$posDOB);//to the end of line						
					$elements = explode("\t",$lineContents);
					$field_val = isset($elements[1]) ? trim($elements[1]): '';// 1/22/1967 (55)	
					//$field_val = trim(str_ireplace('DOB:','',$field_val));
					
					if(stripos($field_val,'(') !== false){
						$posAge = stripos($field_val,'(');
						$field_val = trim(substr($field_val,0,$posAge));					
					}
					
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);
								
				}
				
				
				echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
				$elements = explode("\t",$line);
				
				print "<pre>";
				print_r($elements);
				print "</pre>";
				
			}//end foreach $rpt_lines
		}
	}
	///////////////////////////////////////////
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	$responseArray['department'] = $department;
	
	$tempArr = array('key' => 'department:', 'value' => $department);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	//print "<pre>";print_r($responseArray);print "</pre>";exit;
	return $responseArray;
}

//aocCenterHandler
function aocCenterHandler($fileName='',$reportContents='', $configurationName = '',$email_addr=''){
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Hospital';//Medical Center/UAMS Hospital
	$faxType = '';
	//'Clinic Location:,DOS:,Patient Name:,Patient ID:,Date of Birth:,Age:, CHIEF COMPLAINT:,HISTORY OF PRESENT ILLNESS:,SOCIAL HISTORY:'
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		/*print "<pre>";
		print_r($rpt_lines);
		print "</pre>";*/
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			//if(stripos($reportContents,'University of Arkansas for Medical Sciences')!== false)
			
			$faxType = 'Progress Note';
			$faxTypeMain = 'Progress Note';	//office evaluation			
			
			foreach($rpt_lines as $key => $line)
			{					
				//The stripos() function is used to determine the numeric position of the first occurrence of a string inside another string.
				if(stripos($line,'Clinic Location:') !== false)
				{
					$field_name = 'clinic_location';
					//$elements = explode("\t",$line);
					$field_val = trim(str_ireplace('Clinic Location:','',$line));
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);
				}
				else if(stripos($line,'DOS:') !== false)
				{
					$field_name = 'dos';
					//$elements = explode("\t",$line);
					$field_val = trim(str_ireplace('DOS:','',$line));
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);
				}
				else if(stripos($line,'Patient Name:') !== false)
				{
					////////////////////////////////////////////
					$field_val = trim(str_ireplace('Patient Name:','',$line));//Kattom, Jenna (Lname, Fname)
										
					$pat_name = explode(',',$field_val);
					if(!empty($pat_name) && count($pat_name) > 0){
						$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
						
						$fname = isset($pat_name[1]) ? trim($pat_name[1]) : '';
						$pat_fname = explode(' ', $fname);
						$fname = isset($pat_fname[0]) ? trim($pat_fname[0]) : '';
						/*if(count($pat_fname) > 1){							
							$fname = isset($pat_fname[2]) ? $pat_fname[2]: $pat_fname[1];
						}*/
						$tempArr = array('key' => 'first_name', 'value' => $fname);
						array_push($responseArray['rpt_header'], $tempArr);
					}
					else{
						$tempArr = array('key' => 'first_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
						$tempArr = array('key' => 'last_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
				}
				else if(stripos($line,'Patient ID:') !== false)
				{
					$field_name = 'MRN:';
					//$elements = explode("\t",$line);
					$field_val = trim(str_ireplace('Patient ID:','',$line));
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);
					
					//break;
				}
				else if(stripos($line,'Date of Birth:') !== false)
				{
					$field_name = 'dob';
					//$elements = explode("\t",$line);
					//$field_val = isset($elements[0]) ? $elements[0] : '';
					$field_val = trim(str_ireplace('Date of Birth:','',$line));					
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);					
				}
				else if(stripos($line,'Age:') !== false)
				{					
					$field_name = 'age';
					$field_val = trim(str_ireplace('Age:','',$line));
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);
				}
				
				
				echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
				$elements = explode("\t",$line);
				
				print "<pre>";
				print_r($elements);
				print "</pre>";
				
			}//end foreach $rpt_lines			
								
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	return $responseArray;
}

function expressRxHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	$tempArr = array('testName' => 'pharmacy_name', 'value' => 'Express Rx','flag'=>'','Reference'=>'');						
	array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		/*print "<pre>";
		print_r($rpt_lines);
		print "</pre>";*/
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			//Prescription Refill Request for:
			//if(in_array('REQUEST FOR A REFILL OR NEW PRESCRIPTION',$rpt_lines))
			//Multiple requests in single fax????
			if(stripos($reportContents,'REQUEST FOR FILL AUTHORIZATION')!== false)
			{
				$faxType = 'REQUEST FOR FILL AUTHORIZATION';
				$faxTypeMain = 'Refill Request';				
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'REQUEST FOR FILL AUTHORIZATION') !== false)
					{
						$field_name = 'request_date';//Date:   Requested Date:  Fax Date: 08/04/2019
						$nextKey = $key+1;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}
						$elements = explode("\t",$field_val);
						$field_val = isset($elements[1]) ? $elements[1]: '';	
						//$field_val = trim(str_ireplace('Fax Date:','',$field_val));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}//Rx: 4007970					
					else if(stripos($line,'Rx:') !== false){
						$field_name = 'rx_number';//Rx Number:						
						$field_val = trim(str_ireplace('Rx:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					else if(stripos($line,'Patient:') !== false){
						$field_name = 'Patient:';//Name:  (Lastname, FirstName) 
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';//JOHNSON, TINA						
						$field_val = trim(str_ireplace('Patient:','',$field_val));
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(',',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'DOB:') !== false){						
						$field_name = 'dob';													
						$field_val = trim(str_ireplace('DOB:','',$line));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}	
					else if(stripos($line,'Physician:') !== false){
						$field_name = 'prescriber_name';//PRESCRIBER:												
						$field_val = trim(str_ireplace('Physician:','',$line));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
							
					}										
					else if(stripos($line,'Drug:') !== false)
					{
						$field_name = 'drug_name';//Medication: //Drug:							
						$field_val = trim(str_ireplace('Drug:','',$line));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}					
					else if(stripos($line,'Directions:') !== false){
						$field_name = 'sig';//SIG:						
						$field_val = trim(str_ireplace('Directions:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);							
					}					
					else if(stripos($line,'Last Fill:') !== false){
						$field_name = 'date_last_filled';//Rx Number:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						//$field_val = trim(str_ireplace('Rx:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					else if(stripos($line,'Prev Fill:') !== false){
						$field_name = 'date_prev_filled';//Rx Number:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						//$field_val = trim(str_ireplace('Rx:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}// \nOrig Date: 12/20/2018 Quantity: 180 Cap\n
					else if(stripos($line,'Orig Date:') !== false && stripos($line,'Quantity:') !== false)
					{
						$field_name = 'date_written';
						$qtyPos = stripos($line,'Quantity:');
						$qtyStr = substr($line,$qtyPos);//to the end of line
						$field_val = substr($line,0,($qtyPos-1));
						$field_val = trim(str_ireplace('Orig Date:','',$field_val));
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						$field_name = 'qty_prescribed';
						$field_name = 'qty_dispensed';//Qty. Prescribed:  //Prescribed Qty:
					}									
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}
			else if(stripos($reportContents,'REQUIRES PRIOR AUTHORIZATION')!== false || stripos($reportContents,'Prior Authorization Required')!== false){
				$faxType = 'REQUIRES PRIOR AUTHORIZATION';
				$faxTypeMain = 'PA Request';				
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'Patient:') !== false){
						$field_name = 'Patient:';//Name:  (Lastname, FirstName) 
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';//JOHNSON, TINA						
						//$field_val = trim(str_ireplace('Patient:','',$field_val));
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(',',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'DOB:') !== false){						
						$field_name = 'dob';
						//$elements = explode("\t",$line);													
						$field_val = trim(str_ireplace('DOB:','',$line));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}
					else if(stripos($line,'Drug:') !== false){
						$field_name = 'drug_name';//Medication: //Drug:							
						$field_val = trim(str_ireplace('Drug:','',$line));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					else if(stripos($line,'NDC:') !== false){
						$field_name = 'ndc';//Medication: //Drug:							
						$field_val = trim(str_ireplace('NDC:','',$line));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					else if(stripos($line,'Disp Qty:') !== false){
						$field_name = 'qty_dispensed';
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: 0;						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						//$field_name = 'qty_prescribed';
						//$field_name = 'qty_dispensed';//Qty. Prescribed:  //Prescribed Qty:
					}
					else if(stripos($line,'Doctor:') !== false){
						$field_name = 'prescriber_name';//PRESCRIBER:
						//$elements = explode("\t",$line);
						//$field_val = isset($elements[1]) ? $elements[1]: 0;												
						
						$field_val = trim(str_ireplace('Doctor:','',$line));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
							
					}										
					else if(stripos($line,'Rx:') !== false){
						$field_name = 'rx_number';//Rx Number:						
						$field_val = trim(str_ireplace('Rx:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}
			else if(stripos($reportContents,'90-day supply')!== false){
				$faxType = 'Prescription Request for 90 day supply';
				$faxTypeMain = '90 DAYS SUPPLY';				
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'Patient Information:') !== false){
						$field_name = 'Patient:';//Name:  (Lastname, FirstName) 
						$nextKey = $key+1;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}
						$elements = explode("\t",$field_val);												
						$field_val = isset($elements[0]) ? $elements[0]: '';//LISA SHUBERT						
						//$field_val = trim(str_ireplace('Patient:','',$field_val));
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						/////////////////////
						$field_name = 'prescriber_name';//PRESCRIBER:
						//$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';
						//$field_val = trim(str_ireplace('Doctor:','',$line));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);	
					}
					else if(stripos($line,'DOB:') !== false){						
						$field_name = 'dob';
						$posDOB = stripos($line,'DOB:');
						$field_val = substr($line,$posDOB); 
						//$elements = explode("\t",$line);													
						$field_val = trim(str_ireplace('DOB:','',$field_val));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}
					else if(stripos($line,'Drug:') !== false){
						$field_name = 'drug_name';//Medication: //Drug:							
						$field_val = trim(str_ireplace('Drug:','',$line));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}					
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}
			else if((stripos($reportContents,'Controlled Substance Refill Request')!== false || stripos($reportContents,'Controlled	Substance Refill	Request')!== false) && (stripos($reportContents,'Message ID:')!== false || stripos($reportContents,'eRx ID:')!== false || stripos($reportContents,'eRx Network')!== false))
			{
				//RX Refill	Request
				$faxType = 'Controlled Substance Refill Request';
				$faxTypeMain = 'Controlled Substance Refill Request';				
				
				$posComments = stripos($reportContents,'Comments:');
				$posPrescriber = stripos($reportContents,'Prescriber');
				$posPatient = stripos($reportContents,'Patient');
				$posPrescription = stripos($reportContents,'Prescription');
				
				$prescriberContents 	= substr($reportContents,$posPrescriber, ($posPatient - $posPrescriber));
				$patientContents 		= substr($reportContents,$posPatient, ($posPrescription - $posPatient));
				$prescriptionContents 	= substr($reportContents,$posPrescription, ($posComments - $posPrescription));
				
				if(stripos($reportContents,'Date/Time:') !== false)
				{
					$field_name = 'request_date';//Date/Time: 06/30/2020	07:00
					
					$posStart = stripos($reportContents,'Date/Time:');
					$partialContents = substr($reportContents,$posStart);//to end of contents
					$rpt_lines = explode("\n",$partialContents);
					$line = $rpt_lines[0];//Take first line , ignore rest of lines				
					
					$elements = explode("\t",$line);
					$field_val = isset($elements[0]) ? trim($elements[0]): '';
					
					if(isset($elements[0]) && trim($elements[0]) == 'Date/Time:'){
						$field_val = isset($elements[1]) ? trim($elements[1]): '';
					}
												
					$field_val = trim(str_ireplace('Date/Time:','',$field_val));					
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);						
				}				
				
				if(!empty($prescriberContents)){
					$rpt_lines = explode("\n",$prescriberContents);
					//The array_shift() function, which is used to remove the first element 
					//from an array, returns the removed element. It also returns NULL, if the array is empty.
					//Note: If the keys are numeric, all elements will get new keys, starting from 0 and increases by 1
					$remove = array_shift($rpt_lines); 
					//unset($rpt_lines[0]) ;//It removes the first element without affecting the key values..
					$pharmacyKeys = array('pharmacy_name','pharmacy_address','pharmacy_city','pharmacy_phone','pharmacy_fax','pharmacy_npi','store_number');
					foreach($rpt_lines as $key => $line){
						$elements = explode("\t",$line);							
						if($key == 0){
							$field_name = 'prescriber_name';//PRESCRIBER:
							$field_val = isset($elements[0]) ? $elements[0]: '';						
							//$field_val = trim(str_ireplace('Prescription Refill Request for:','',$field_val));
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						////////////////////////////////////////
						$field_name = $pharmacyKeys[$key];												
						$field_val = isset($elements[1]) ? trim($elements[1]) : '';
						if(count($elements) > 3){
							$field_val = isset($elements[3]) ? trim($elements[3]) : '';
						}
						else if(count($elements) > 2){
							$field_val = isset($elements[2]) ? trim($elements[2]) : '';	
						}
						
						$field_val = trim(str_ireplace('Phone:','',$field_val));
						$field_val = trim(str_ireplace('Fax:','',$field_val));
						$field_val = trim(str_ireplace('NPI:','',$field_val));
						$field_val = trim(str_ireplace('NCPDP:','',$field_val));
												
						if($field_name == 'pharmacy_city'){
							//'pharmacy_city','pharmacy_state','pharmacy_zip',
							explode(" ",$field_val);
						}
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
					}
				}
				
				if(!empty($patientContents)){
					$rpt_lines = explode("\n",$patientContents);
					$remove = array_shift($rpt_lines);//Note: If the keys are numeric, all elements will get new keys, starting from 0
					//unset($rpt_lines[0]) ;//It removes the first element without affecting the key values..
					//foreach($rpt_lines as $key => $line){}
					$line = $rpt_lines[0];
					$elements = explode("\t",$line);
					$field_val = isset($elements[0]) ? trim($elements[0]): '';//(Fname Lname)
					$pat_name = explode(' ',$field_val);
					if(!empty($pat_name) && count($pat_name) > 0){
						$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
						$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
					else{
						$tempArr = array('key' => 'first_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
						$tempArr = array('key' => 'last_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
					//////////////////////////////////////
					$field_name = 'dob';
					$field_val = isset($elements[1]) ? trim($elements[1]) : '';
					if(count($elements)>2){
						$field_val = isset($elements[2]) ? trim($elements[2]) : '';
					}
											
					//$field_val = trim(str_ireplace('DOB:','',$line));
					//$field_val = trim(substr($field_val,0,10));											
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);
				}
				
				if(!empty($prescriptionContents)){
					$rpt_lines = explode("\n",$prescriptionContents);
					$remove = array_shift($rpt_lines);
					//unset($rpt_lines[0]) ;//It removes the first element without affecting the key values..
					foreach($rpt_lines as $key => $line){
						if(stripos($line,'Rx Number:') !== false && stripos($line,'Qty Prescribed:') !== false && stripos($line,'Last Dispensed:') !== false){
							$elements = explode("\t",$line);						
							///////////////////////////////
							$field_name = 'rx_number';//Rx Number:						
							$field_val = isset($elements[0]) ? $elements[0]: '';
							$field_val = trim(str_ireplace('Rx Number:','',$field_val));						
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);
							
							$field_name = 'qty_prescribed';
							$field_val = isset($elements[1]) ? trim($elements[1]) : '';
							if($field_val == 'Qty Prescribed:'){
								$field_val = isset($elements[2]) ? trim($elements[2]) : '';
							}
							$field_val = trim(str_ireplace('Qty Prescribed:','',$field_val));
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);
							
							$field_name = 'date_last_filled';//Date Last Filled:   //Last Filled:							
							$field_val = isset($elements[3]) ? $elements[3] : '';
							if(isset($elements[4])){
								$field_val = $elements[4];
							}
							$field_val = trim(str_ireplace('Last Dispensed:','',$field_val));						
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);						
						}
						else if(stripos($line,'Qty Dispensed:') !== false && stripos($line,'Date Written:') !== false)
						{
							$field_name = 'date_written';						
							$elements = explode("\t",$line);
							$field_val = isset($elements[3]) ? trim($elements[3]) : '';
							if(isset($elements[4])){
								$field_val = isset($elements[4]) ? trim($elements[4]) : '';
							}	
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);
							////////////////////////////////////////////
							$field_name = 'drug_name';//Medication: //Drug:
							$nextKey = $key+1;
							$field_val = '';
							if (array_key_exists($nextKey,$rpt_lines)){
								$field_val = trim($rpt_lines[$nextKey]);
							}
							$field_val = trim(str_ireplace('Prescribed:','',$field_val));					
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);						
						}
						else if(stripos($line,'SIG:') !== false){
							$field_name = 'sig';//SIG:
							$posSig = stripos($prescriptionContents,'SIG:');
							$field_val = substr($prescriptionContents,$posSig);						
							$field_val = trim(str_ireplace('SIG:','',$field_val));
							$field_val = trim(str_ireplace('Comments:','',$field_val));						
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);							
						}
						
					}//end foreach
				}
			}
			else if((stripos($reportContents,'Refill Request')!== false || stripos($reportContents,'Refill	Request')!== false) && (stripos($reportContents,'Message ID:')!== false || stripos($reportContents,'Message 1D:')!== false || stripos($reportContents,'eRx ID:')!== false || stripos($reportContents,'eRx Network')!== false))
			{
				//RX Refill	Request
				$faxType = 'Refill Request';
				$faxTypeMain = 'Refill Request';
				
				$posComments = stripos($reportContents,'Comments:');
				$posPrescriber = stripos($reportContents,'Prescriber');
				$posPatient = stripos($reportContents,'Patient');
				$posPrescription = stripos($reportContents,'Prescription');
				
				$prescriberContents 	= substr($reportContents,$posPrescriber, ($posPatient - $posPrescriber));
				$patientContents 		= substr($reportContents,$posPatient, ($posPrescription - $posPatient));
				$prescriptionContents 	= substr($reportContents,$posPrescription, ($posComments - $posPrescription));
				
				if(stripos($reportContents,'Date/Time:') !== false)
				{
					$field_name = 'request_date';//Date/Time: 06/30/2020	07:00
					
					$posStart = stripos($reportContents,'Date/Time:');
					$partialContents = substr($reportContents,$posStart);//to end of contents
					$rpt_lines = explode("\n",$partialContents);
					$line = $rpt_lines[0];//Take first line , ignore rest of lines				
					
					$elements = explode("\t",$line);
					$field_val = isset($elements[0]) ? trim($elements[0]): '';
					
					if(isset($elements[0]) && trim($elements[0]) == 'Date/Time:'){
						$field_val = isset($elements[1]) ? trim($elements[1]): '';
					}
												
					$field_val = trim(str_ireplace('Date/Time:','',$field_val));					
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);						
				}				
				
				if(!empty($prescriberContents)){
					$rpt_lines = explode("\n",$prescriberContents);
					//The array_shift() function, which is used to remove the first element 
					//from an array, returns the removed element. It also returns NULL, if the array is empty.
					//Note: If the keys are numeric, all elements will get new keys, starting from 0 and increases by 1
					$remove = array_shift($rpt_lines); 
					//unset($rpt_lines[0]) ;//It removes the first element without affecting the key values..
					$pharmacyKeys = array('pharmacy_name','pharmacy_address','pharmacy_city','pharmacy_phone','pharmacy_fax','pharmacy_npi','store_number');
					foreach($rpt_lines as $key => $line){
						$elements = explode("\t",$line);							
						if($key == 0){
							$field_name = 'prescriber_name';//PRESCRIBER:
							$field_val = isset($elements[0]) ? $elements[0]: '';						
							//$field_val = trim(str_ireplace('Prescription Refill Request for:','',$field_val));
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						////////////////////////////////////////
						$field_name = $pharmacyKeys[$key];												
						$field_val = isset($elements[1]) ? trim($elements[1]) : '';
						if(count($elements) > 3){
							$field_val = isset($elements[3]) ? trim($elements[3]) : '';
						}
						else if(count($elements) > 2){
							$field_val = isset($elements[2]) ? trim($elements[2]) : '';	
						}
						
						$field_val = trim(str_ireplace('Phone:','',$field_val));
						$field_val = trim(str_ireplace('Fax:','',$field_val));
						$field_val = trim(str_ireplace('NPI:','',$field_val));
						$field_val = trim(str_ireplace('NCPDP:','',$field_val));
												
						if($field_name == 'pharmacy_city'){
							//'pharmacy_city','pharmacy_state','pharmacy_zip',
							explode(" ",$field_val);
						}
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
					}
				}
				
				if(!empty($patientContents)){
					$rpt_lines = explode("\n",$patientContents);
					$remove = array_shift($rpt_lines);//Note: If the keys are numeric, all elements will get new keys, starting from 0
					//unset($rpt_lines[0]) ;//It removes the first element without affecting the key values..
					//foreach($rpt_lines as $key => $line){}
					$line = $rpt_lines[0];
					$elements = explode("\t",$line);
					$field_val = isset($elements[0]) ? trim($elements[0]): '';//(Fname Lname)
					$pat_name = explode(' ',$field_val);
					if(!empty($pat_name) && count($pat_name) > 0){
						$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
						$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
					else{
						$tempArr = array('key' => 'first_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
						$tempArr = array('key' => 'last_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
					//////////////////////////////////////
					$field_name = 'dob';
					$field_val = isset($elements[1]) ? trim($elements[1]) : '';
					if(count($elements)>2){
						$field_val = isset($elements[2]) ? trim($elements[2]) : '';
					}
											
					//$field_val = trim(str_ireplace('DOB:','',$line));
					//$field_val = trim(substr($field_val,0,10));											
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);
				}
				
				if(!empty($prescriptionContents)){
					$rpt_lines = explode("\n",$prescriptionContents);
					$remove = array_shift($rpt_lines);
					//unset($rpt_lines[0]) ;//It removes the first element without affecting the key values..
					foreach($rpt_lines as $key => $line){
						if(stripos($line,'Rx Number:') !== false && stripos($line,'Qty Prescribed:') !== false && stripos($line,'Last Dispensed:') !== false){
							$elements = explode("\t",$line);						
							///////////////////////////////
							$field_name = 'rx_number';//Rx Number:						
							$field_val = isset($elements[0]) ? $elements[0]: '';
							$field_val = trim(str_ireplace('Rx Number:','',$field_val));						
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);
							
							$field_name = 'qty_prescribed';
							$field_val = isset($elements[1]) ? trim($elements[1]) : '';
							if($field_val == 'Qty Prescribed:'){
								$field_val = isset($elements[2]) ? trim($elements[2]) : '';
							}
							$field_val = trim(str_ireplace('Qty Prescribed:','',$field_val));
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);
							
							$field_name = 'date_last_filled';//Date Last Filled:   //Last Filled:							
							$field_val = isset($elements[3]) ? $elements[3] : '';
							if(isset($elements[4])){
								$field_val = $elements[4];
							}
							$field_val = trim(str_ireplace('Last Dispensed:','',$field_val));						
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);						
						}
						else if(stripos($line,'Qty Dispensed:') !== false && stripos($line,'Date Written:') !== false)
						{
							$field_name = 'date_written';						
							$elements = explode("\t",$line);
							$field_val = isset($elements[3]) ? trim($elements[3]) : '';
							if(isset($elements[4])){
								$field_val = isset($elements[4]) ? trim($elements[4]) : '';
							}	
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);
							////////////////////////////////////////////
							$field_name = 'drug_name';//Medication: //Drug:
							$nextKey = $key+1;
							$field_val = '';
							if (array_key_exists($nextKey,$rpt_lines)){
								$field_val = trim($rpt_lines[$nextKey]);
							}
							$field_val = trim(str_ireplace('Prescribed:','',$field_val));					
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);						
						}
						else if(stripos($line,'SIG:') !== false){
							$field_name = 'sig';//SIG:
							$posSig = stripos($prescriptionContents,'SIG:');
							$field_val = substr($prescriptionContents,$posSig);						
							$field_val = trim(str_ireplace('SIG:','',$field_val));
							$field_val = trim(str_ireplace('Comments:','',$field_val));						
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);							
						}
						
					}//end foreach
				}
			}
			else if(stripos($reportContents,'IMPORTANT PATIENT SAFETY and HEALTH CONSIDERATION')!== false)
			{ 
				if(stripos($reportContents,'Requested Actions:') !== false){
					$posStart = stripos($reportContents,'Requested Actions:');					
					$reportContents = substr($reportContents,$posStart);//To end of contents					
				}
				else if(stripos($reportContents,'Patient Safety and Health Consideration') !== false){
					$posStart = stripos($reportContents,'Patient Safety and Health Consideration');					
					$reportContents = substr($reportContents,$posStart);//To end of contents					
				}
				
				$rpt_lines = explode("\n",$reportContents);
				/*print "<pre>";
				print_r($rpt_lines);
				print "</pre>";*/				
				$faxType = 'Non-adherence';
				$faxTypeMain = 'Non-adherence';
								
				foreach($rpt_lines as $key => $line){
					if(stripos($line,'Patient Name') !== false){
						$field_name = 'Patient:';//Name:  (Lastname, FirstName) 
						//$elements = explode("\t",$line);
						//$field_val = isset($elements[0]) ? $elements[0]: '';					
						
						$field_val = trim(str_ireplace('Patient Name','',$line));//MARIA IZQUIERDO(fname Lname)
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'Date of Birth') !== false){						
						$field_name = 'dob';													
						$field_val = trim(str_ireplace('Date of Birth','',$line));					
						$field_val = trim(substr($field_val,0,10));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}
					else if(stripos($line,'Patient Adherence:') !== false){						
						$field_name = 'Adherence';													
						$field_val = trim(str_ireplace('Patient Adherence:','',$line));						
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					else if(stripos($line,'Drug Safety Consideration:') !== false){
						$faxType = 'Drug Safety';
						$faxTypeMain = 'Drug Safety';
										
						$field_name = 'Drug Safety';													
						$field_val = trim(str_ireplace('Drug Safety Consideration:','',$line));						
						
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
				}
				
			}
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	return $responseArray;	
}

function eRxNetworkHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	
	//$tempArr = array('testName' => 'pharmacy_name', 'value' => 'eRx Network','flag'=>'','Reference'=>'');						
	//array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		/*print "<pre>";
		print_r($rpt_lines);
		print "</pre>";*/
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			//Prescription Refill Request for:
			//if(in_array('REQUEST FOR A REFILL OR NEW PRESCRIPTION',$rpt_lines))
			//Multiple requests in single fax????
			if((stripos($reportContents,'Controlled Substance Refill Request')!== false || stripos($reportContents,'Controlled	Substance Refill	Request')!== false))
			{
				//RX Refill	Request
				$faxType = 'Controlled Substance Refill Request';
				$faxTypeMain = 'Controlled Substance Refill Request';				
				
				$posComments = stripos($reportContents,'Comments:');
				$posPrescriber = stripos($reportContents,'Prescriber');
				$posPatient = stripos($reportContents,'Patient');
				$posPrescription = stripos($reportContents,'Prescription');
				
				$prescriberContents 	= substr($reportContents,$posPrescriber, ($posPatient - $posPrescriber));
				$patientContents 		= substr($reportContents,$posPatient, ($posPrescription - $posPatient));
				$prescriptionContents 	= substr($reportContents,$posPrescription, ($posComments - $posPrescription));
				
				if(stripos($reportContents,'Date/Time:') !== false)
				{
					$field_name = 'request_date';//Date/Time: 06/30/2020	07:00
					
					$posStart = stripos($reportContents,'Date/Time:');
					$partialContents = substr($reportContents,$posStart);//to end of contents
					$rpt_lines = explode("\n",$partialContents);
					$line = $rpt_lines[0];//Take first line , ignore rest of lines				
					
					$elements = explode("\t",$line);
					$field_val = isset($elements[0]) ? trim($elements[0]): '';
					
					if(isset($elements[0]) && trim($elements[0]) == 'Date/Time:'){
						$field_val = isset($elements[1]) ? trim($elements[1]): '';
					}
												
					$field_val = trim(str_ireplace('Date/Time:','',$field_val));					
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);						
				}				
				
				if(!empty($prescriberContents)){
					$rpt_lines = explode("\n",$prescriberContents);
					//The array_shift() function, which is used to remove the first element 
					//from an array, returns the removed element. It also returns NULL, if the array is empty.
					//Note: If the keys are numeric, all elements will get new keys, starting from 0 and increases by 1
					$remove = array_shift($rpt_lines); 
					//unset($rpt_lines[0]) ;//It removes the first element without affecting the key values..
					$pharmacyKeys = array('pharmacy_name','pharmacy_address','pharmacy_city','pharmacy_phone','pharmacy_fax','pharmacy_npi','store_number');
					foreach($rpt_lines as $key => $line){
						$elements = explode("\t",$line);							
						if($key == 0){
							$field_name = 'prescriber_name';//PRESCRIBER:
							$field_val = isset($elements[0]) ? $elements[0]: '';						
							//$field_val = trim(str_ireplace('Prescription Refill Request for:','',$field_val));
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						////////////////////////////////////////
						$field_name = $pharmacyKeys[$key];												
						$field_val = isset($elements[1]) ? trim($elements[1]) : '';
						if(count($elements) > 3){
							$field_val = isset($elements[3]) ? trim($elements[3]) : '';
						}
						else if(count($elements) > 2){
							$field_val = isset($elements[2]) ? trim($elements[2]) : '';	
						}
						
						$field_val = trim(str_ireplace('Phone:','',$field_val));
						$field_val = trim(str_ireplace('Fax:','',$field_val));
						$field_val = trim(str_ireplace('NPI:','',$field_val));
						$field_val = trim(str_ireplace('NCPDP:','',$field_val));
												
						if($field_name == 'pharmacy_city'){
							//'pharmacy_city','pharmacy_state','pharmacy_zip',
							explode(" ",$field_val);
						}
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
					}
				}
				
				if(!empty($patientContents)){
					$rpt_lines = explode("\n",$patientContents);
					$remove = array_shift($rpt_lines);//Note: If the keys are numeric, all elements will get new keys, starting from 0
					//unset($rpt_lines[0]) ;//It removes the first element without affecting the key values..
					//foreach($rpt_lines as $key => $line){}
					$line = $rpt_lines[0];
					$elements = explode("\t",$line);
					$field_val = isset($elements[0]) ? trim($elements[0]): '';//(Fname Lname)
					$pat_name = explode(' ',$field_val);
					if(!empty($pat_name) && count($pat_name) > 0){
						$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
						$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
					else{
						$tempArr = array('key' => 'first_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
						$tempArr = array('key' => 'last_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
					//////////////////////////////////////
					$field_name = 'dob';
					$field_val = isset($elements[1]) ? trim($elements[1]) : '';
					if(count($elements)>2){
						$field_val = isset($elements[2]) ? trim($elements[2]) : '';
					}
											
					//$field_val = trim(str_ireplace('DOB:','',$line));
					//$field_val = trim(substr($field_val,0,10));											
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);
				}
				
				if(!empty($prescriptionContents)){
					$rpt_lines = explode("\n",$prescriptionContents);
					$remove = array_shift($rpt_lines);
					//unset($rpt_lines[0]) ;//It removes the first element without affecting the key values..
					foreach($rpt_lines as $key => $line){
						if(stripos($line,'Rx Number:') !== false && stripos($line,'Qty Prescribed:') !== false && stripos($line,'Last Dispensed:') !== false){
							$elements = explode("\t",$line);						
							///////////////////////////////
							$field_name = 'rx_number';//Rx Number:						
							$field_val = isset($elements[0]) ? $elements[0]: '';
							$field_val = trim(str_ireplace('Rx Number:','',$field_val));						
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);
							
							$field_name = 'qty_prescribed';
							$field_val = isset($elements[1]) ? trim($elements[1]) : '';
							if($field_val == 'Qty Prescribed:'){
								$field_val = isset($elements[2]) ? trim($elements[2]) : '';
							}
							$field_val = trim(str_ireplace('Qty Prescribed:','',$field_val));
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);
							
							$field_name = 'date_last_filled';//Date Last Filled:   //Last Filled:							
							$field_val = isset($elements[3]) ? $elements[3] : '';
							if(isset($elements[4])){
								$field_val = $elements[4];
							}
							$field_val = trim(str_ireplace('Last Dispensed:','',$field_val));						
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);						
						}
						else if(stripos($line,'Qty Dispensed:') !== false && stripos($line,'Date Written:') !== false)
						{
							$field_name = 'date_written';						
							$elements = explode("\t",$line);
							$field_val = isset($elements[3]) ? trim($elements[3]) : '';
							if(isset($elements[4])){
								$field_val = isset($elements[4]) ? trim($elements[4]) : '';
							}	
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);
							////////////////////////////////////////////
							$field_name = 'drug_name';//Medication: //Drug:
							$nextKey = $key+1;
							$field_val = '';
							if (array_key_exists($nextKey,$rpt_lines)){
								$field_val = trim($rpt_lines[$nextKey]);
							}
							$field_val = trim(str_ireplace('Prescribed:','',$field_val));					
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);						
						}
						else if(stripos($line,'SIG:') !== false){
							$field_name = 'sig';//SIG:
							$posSig = stripos($prescriptionContents,'SIG:');
							$field_val = substr($prescriptionContents,$posSig);						
							$field_val = trim(str_ireplace('SIG:','',$field_val));
							$field_val = trim(str_ireplace('Comments:','',$field_val));						
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);							
						}
						
					}//end foreach
				}
			}
			else if((stripos($reportContents,'Refill Request')!== false || stripos($reportContents,'Refill	Request')!== false))
			{
				//RX Refill	Request
				$faxType = 'Refill Request';
				$faxTypeMain = 'Refill Request';
				
				$posComments = stripos($reportContents,'Comments:');
				$posPrescriber = stripos($reportContents,'Prescriber');
				$posPatient = stripos($reportContents,'Patient');
				$posPrescription = stripos($reportContents,'Prescription');
				
				$prescriberContents 	= substr($reportContents,$posPrescriber, ($posPatient - $posPrescriber));
				$patientContents 		= substr($reportContents,$posPatient, ($posPrescription - $posPatient));
				$prescriptionContents 	= substr($reportContents,$posPrescription, ($posComments - $posPrescription));
				
				if(stripos($reportContents,'Date/Time:') !== false)
				{
					$field_name = 'request_date';//Date/Time: 06/30/2020	07:00
					
					$posStart = stripos($reportContents,'Date/Time:');
					$partialContents = substr($reportContents,$posStart);//to end of contents
					$rpt_lines = explode("\n",$partialContents);
					$line = $rpt_lines[0];//Take first line , ignore rest of lines				
					
					$elements = explode("\t",$line);
					$field_val = isset($elements[0]) ? trim($elements[0]): '';
					
					if(isset($elements[0]) && trim($elements[0]) == 'Date/Time:'){
						$field_val = isset($elements[1]) ? trim($elements[1]): '';
					}
												
					$field_val = trim(str_ireplace('Date/Time:','',$field_val));					
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);						
				}				
				
				if(!empty($prescriberContents)){
					$rpt_lines = explode("\n",$prescriberContents);
					//The array_shift() function, which is used to remove the first element 
					//from an array, returns the removed element. It also returns NULL, if the array is empty.
					//Note: If the keys are numeric, all elements will get new keys, starting from 0 and increases by 1
					$remove = array_shift($rpt_lines); 
					//unset($rpt_lines[0]) ;//It removes the first element without affecting the key values..
					$pharmacyKeys = array('pharmacy_name','pharmacy_address','pharmacy_city','pharmacy_phone','pharmacy_fax','pharmacy_npi','store_number');
					foreach($rpt_lines as $key => $line){
						$elements = explode("\t",$line);							
						if($key == 0){
							$field_name = 'prescriber_name';//PRESCRIBER:
							$field_val = isset($elements[0]) ? $elements[0]: '';						
							//$field_val = trim(str_ireplace('Prescription Refill Request for:','',$field_val));
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						////////////////////////////////////////
						$field_name = $pharmacyKeys[$key];												
						$field_val = isset($elements[1]) ? trim($elements[1]) : '';
						if(count($elements) > 3){
							$field_val = isset($elements[3]) ? trim($elements[3]) : '';
						}
						else if(count($elements) > 2){
							$field_val = isset($elements[2]) ? trim($elements[2]) : '';	
						}
						
						$field_val = trim(str_ireplace('Phone:','',$field_val));
						$field_val = trim(str_ireplace('Fax:','',$field_val));
						$field_val = trim(str_ireplace('NPI:','',$field_val));
						$field_val = trim(str_ireplace('NCPDP:','',$field_val));
												
						if($field_name == 'pharmacy_city'){
							//'pharmacy_city','pharmacy_state','pharmacy_zip',
							explode(" ",$field_val);
						}
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
					}
				}
				
				if(!empty($patientContents)){
					$rpt_lines = explode("\n",$patientContents);
					$remove = array_shift($rpt_lines);//Note: If the keys are numeric, all elements will get new keys, starting from 0
					//unset($rpt_lines[0]) ;//It removes the first element without affecting the key values..
					//foreach($rpt_lines as $key => $line){}
					$line = $rpt_lines[0];
					$elements = explode("\t",$line);
					$field_val = isset($elements[0]) ? trim($elements[0]): '';//(Fname Lname)
					$pat_name = explode(' ',$field_val);
					if(!empty($pat_name) && count($pat_name) > 0){
						$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
						$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
					else{
						$tempArr = array('key' => 'first_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
						$tempArr = array('key' => 'last_name', 'value' => '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
					//////////////////////////////////////
					$field_name = 'dob';
					$field_val = isset($elements[1]) ? trim($elements[1]) : '';
					if(count($elements)>2){
						$field_val = isset($elements[2]) ? trim($elements[2]) : '';
					}
											
					//$field_val = trim(str_ireplace('DOB:','',$line));
					//$field_val = trim(substr($field_val,0,10));											
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);
				}
				
				if(!empty($prescriptionContents)){
					$rpt_lines = explode("\n",$prescriptionContents);
					$remove = array_shift($rpt_lines);
					//unset($rpt_lines[0]) ;//It removes the first element without affecting the key values..
					foreach($rpt_lines as $key => $line){
						if(stripos($line,'Rx Number:') !== false && stripos($line,'Qty Prescribed:') !== false && stripos($line,'Last Dispensed:') !== false){
							$elements = explode("\t",$line);						
							///////////////////////////////
							$field_name = 'rx_number';//Rx Number:						
							$field_val = isset($elements[0]) ? $elements[0]: '';
							$field_val = trim(str_ireplace('Rx Number:','',$field_val));						
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);
							
							$field_name = 'qty_prescribed';
							$field_val = isset($elements[1]) ? trim($elements[1]) : '';
							if($field_val == 'Qty Prescribed:'){
								$field_val = isset($elements[2]) ? trim($elements[2]) : '';
							}
							$field_val = trim(str_ireplace('Qty Prescribed:','',$field_val));
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);
							
							$field_name = 'date_last_filled';//Date Last Filled:   //Last Filled:							
							$field_val = isset($elements[3]) ? $elements[3] : '';
							if(isset($elements[4])){
								$field_val = $elements[4];
							}
							$field_val = trim(str_ireplace('Last Dispensed:','',$field_val));						
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);						
						}
						else if(stripos($line,'Qty Dispensed:') !== false && stripos($line,'Date Written:') !== false)
						{
							$field_name = 'date_written';						
							$elements = explode("\t",$line);
							$field_val = isset($elements[3]) ? trim($elements[3]) : '';
							if(isset($elements[4])){
								$field_val = isset($elements[4]) ? trim($elements[4]) : '';
							}	
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);
							////////////////////////////////////////////
							$field_name = 'drug_name';//Medication: //Drug:
							$nextKey = $key+1;
							$field_val = '';
							if (array_key_exists($nextKey,$rpt_lines)){
								$field_val = trim($rpt_lines[$nextKey]);
							}
							$field_val = trim(str_ireplace('Prescribed:','',$field_val));					
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);						
						}
						else if(stripos($line,'SIG:') !== false){
							$field_name = 'sig';//SIG:
							$posSig = stripos($prescriptionContents,'SIG:');
							$field_val = substr($prescriptionContents,$posSig);						
							$field_val = trim(str_ireplace('SIG:','',$field_val));
							$field_val = trim(str_ireplace('Comments:','',$field_val));						
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);							
						}
						
					}//end foreach
				}
			}
			else if(stripos($reportContents,'RxRenewal Request')!== false)
			{
				//RX Refill	Request
				$faxType = 'Refill Request';
				$faxTypeMain = 'Refill Request';
				$posComments = stripos($reportContents,'Comments:');
				if(stripos($reportContents,'Comments:') !==false){
					$reportContents 	= substr($reportContents,0, $posComments);					
				}
				  
				$posPharmacy = stripos($reportContents,'Pharmacy:');
				$posPrescriber = stripos($reportContents,'Prescriber:');
				$posPatient = stripos($reportContents,'Patient:');
				$posPrescription = stripos($reportContents,'Prescription:');
				if(stripos($reportContents,'Prescription:') !== false){
					$posPrescription = stripos($reportContents,'Prescription:');
				}
				else if(stripos($reportContents,'Drug Prescribed:') !== false && (stripos($reportContents,'Drug Prescribed:') < stripos($reportContents,'Rx Number:'))){
					$posPrescription = stripos($reportContents,'Drug Prescribed:');
				}
				
				$pharmacyContents 		= substr($reportContents,$posPharmacy, ($posPrescriber - $posPharmacy));
				$prescriberContents 	= substr($reportContents,$posPrescriber, ($posPatient - $posPrescriber));
				$patientContents 		= substr($reportContents,$posPatient, ($posPrescription - $posPatient));
				$prescriptionContents 	= substr($reportContents,$posPrescription, ($posComments - $posPrescription));
				
				if(stripos($reportContents,'Date:') !== false)
				{
					$field_name = 'request_date';//Date/Time: 06/30/2020	07:00
					
					$posStart = stripos($reportContents,'Date:');
					$partialContents = substr($reportContents,$posStart);//to end of contents
					$rpt_lines = explode("\n",$partialContents);
					$line = $rpt_lines[0];//Take first line , ignore rest of lines				
					
					$elements = explode("\t",$line);
					$field_val = isset($elements[0]) ? trim($elements[0]): '';
					
					if(isset($elements[0]) && trim($elements[0]) == 'Date:'){
						$field_val = isset($elements[1]) ? trim($elements[1]): '';
					}
												
					$field_val = trim(str_ireplace('Date:','',$field_val));					
					$tempArr = array('key' => $field_name, 'value' => $field_val);
					array_push($responseArray['rpt_header'], $tempArr);						
				}				
				
				if(!empty($pharmacyContents)){
					/*
					Pharmacy: KROGER PHARMACY 02500630
					Address:	8415 W MARKHAM ST	LITTLE ROCK, AR 72205
					Phone:	(501)227-8200	FAX: (501)227-8201	NCPDP:0415730	NPI: 1962436717
					*/
					$rpt_lines = explode("\n",$pharmacyContents);					
					$pharmacyKeys = array('pharmacy_name','pharmacy_address','pharmacy_city','pharmacy_phone','pharmacy_fax','pharmacy_npi','store_number');
					foreach($rpt_lines as $key => $line){
						$elements = explode("\t",$line);							
						if(stripos($line,'Pharmacy:') !== false){
							$field_val = trim(str_ireplace('Pharmacy:','',$line));
							$tempArr = array('testName' => 'pharmacy_name', 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);
						}
						else if(stripos($line,'Address:') !== false){
							$field_val = trim(str_ireplace('Address:','',$line));
							//$field_val = explode(",",$field_val);							
							$tempArr = array('testName' => 'pharmacy_address', 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);
						}
						else if(stripos($line,'Phone:') !== false){
							$posFax = stripos($line,'FAX:');
							$posNCPDP = stripos($line,'NCPDP:');
							$posNPI = stripos($line,'NPI:');
							
							$field_val = substr($line,0,$posFax);
							$field_val = trim(str_ireplace('Phone:','',$field_val));
							$tempArr = array('testName' => 'pharmacy_phone', 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);
							
							$field_val = substr($line,$posFax, ($posNCPDP - $posFax));
							$field_val = trim(str_ireplace('FAX:','',$field_val));
							$tempArr = array('testName' => 'pharmacy_fax', 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);
							
							$field_val = substr($line,$posNCPDP, ($posNPI - $posNCPDP));
							$field_val = trim(str_ireplace('NCPDP:','',$field_val));
							$tempArr = array('testName' => 'store_number', 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);
							
							$field_val = substr($line,$posNPI);
							$field_val = trim(str_ireplace('NPI:','',$field_val));
							$tempArr = array('testName' => 'pharmacy_npi', 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);
							
						}
												
					}
				}
				
				if(!empty($prescriberContents)){
					$rpt_lines = explode("\n",$prescriberContents);
					foreach($rpt_lines as $key => $line){
						$elements = explode("\t",$line);							
						if(stripos($line,'Prescriber:') !== false){
							$field_name = 'prescriber_name';//PRESCRIBER:
							//$field_val = isset($elements[0]) ? $elements[0]: '';						
							$field_val = trim(str_ireplace('Prescriber:','',$line));
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						////////////////////////////////////////
						
					}
				}
				
				if(!empty($patientContents)){
					$rpt_lines = explode("\n",$patientContents);
					foreach($rpt_lines as $key => $line){
						$elements = explode("\t",$line);							
						if(stripos($line,'Patient:') !== false){							
							//$field_val = isset($elements[0]) ? $elements[0]: '';						
							$field_val = trim(str_ireplace('Patient:','',$line));//WALTER S RAGLAND
							$pat_name = explode(' ',$field_val);//First Mid Last
							if(!empty($pat_name) && count($pat_name) > 0){
								$firstName = $lastName = '';
								if(count($pat_name) > 2){
									$firstName = isset($pat_name[0]) ? trim($pat_name[0]) : '';
									$lastName  = isset($pat_name[2]) ? trim($pat_name[2]) : '';
								}
								else{
									$firstName = isset($pat_name[0]) ? trim($pat_name[0]) : '';
									$lastName  = isset($pat_name[1]) ? trim($pat_name[1]) : '';
								}
								$tempArr = array('key' => 'first_name', 'value' => $firstName);
								array_push($responseArray['rpt_header'], $tempArr);
								$tempArr = array('key' => 'last_name', 'value' => $lastName);
								array_push($responseArray['rpt_header'], $tempArr);
							}
							else{
								$tempArr = array('key' => 'first_name', 'value' => '');
								array_push($responseArray['rpt_header'], $tempArr);
								$tempArr = array('key' => 'last_name', 'value' => '');
								array_push($responseArray['rpt_header'], $tempArr);
							}							
						}
						else if(stripos($line,'Dob:') !== false){
							//Phone:	Dob: 19640409	Gender: M
							$posDOB = stripos($line,'Dob:');
							$posGender = stripos($line,'Gender:');												
							
							$field_name = 'dob';
							$field_val = substr($line,$posDOB, ($posGender - $posDOB));
							$field_val = trim(str_ireplace('Dob:','',$field_val));
							//$field_val = trim(str_ireplace('/','',$field_val));
							//$field_val = trim(str_ireplace('-','',$field_val));
							if(substr_count($field_val,'/') > 0){
								
							}
							else{
								//$field_val = substr($field_val,0,4).'/'.substr($field_val,4,2).'/'.substr($field_val,6,2);
								$field_val = substr($field_val,4,2).'/'.substr($field_val,6,2).'/'.substr($field_val,0,4);//mm/dd/YYYY
							}
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
							
						} 
						////////////////////////////////////////
						
					}
					
				}
				
				if(!empty($prescriptionContents)){
					/*
					Rx Number: 6596758
					Qty.Prescribed:	30	Thirty	Date Written: 20200610
					Qty.Dispensed:	30	Thirty	Last Filled:	20200611
					Drug Prescribed: VIAGRA 100 MG TABLET
					Drug Dispensed: SILDENAFIL 100 MG TABLET
					SIG:	TAKE ONE TABLET BY MOUTH DAILY ONE HOUR PRIOR TO SEX					
					*/
					$rpt_lines = explode("\n",$prescriptionContents);					
					foreach($rpt_lines as $key => $line){
						if(stripos($line,'Rx Number:') !== false ){
							$elements = explode("\t",$line);						
							///////////////////////////////
							$field_name = 'rx_number';//Rx Number:
							$field_val = trim(str_ireplace('Rx Number:','',$line));						
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);												
						}
						else if(stripos($line,'Qty.Prescribed:') !== false && stripos($line,'Date Written:') !== false){
							$elements = explode("\t",$line);						
							///////////////////////////////	
							$posWritten = stripos($line,'Date Written:');
							$field_val 	= substr($line,0, $posWritten);
							$field_val = trim(str_ireplace('Qty.Prescribed:','',$field_val));						
							$field_name = 'qty_prescribed';							
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);
							
							$field_name = 'date_written';						
							$field_val 	= substr($line,$posWritten);
							$field_val = trim(str_ireplace('Date Written:','',$field_val));	//20200610
							//$field_val = substr($field_val,0,4).'/'.substr($field_val,4,2).'/'.substr($field_val,6,2);
							$field_val = substr($field_val,4,2).'/'.substr($field_val,6,2).'/'.substr($field_val,0,4);//mm/dd/YYYY
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);						
													
						}
						else if(stripos($line,'Qty.Dispensed:') !== false && stripos($line,'Last Filled:') !== false){
							$elements = explode("\t",$line);						
							///////////////////////////////							
							$posFilled = stripos($line,'Last Filled:');
							$field_val 	= substr($line,$posFilled);
							$field_val = trim(str_ireplace('Last Filled:','',$field_val));	//20200610
							//$field_val = substr($field_val,0,4).'/'.substr($field_val,4,2).'/'.substr($field_val,6,2);
							$field_val = substr($field_val,4,2).'/'.substr($field_val,6,2).'/'.substr($field_val,0,4);//mm/dd/YYYY
							$field_name = 'date_last_filled';//Date Last Filled:   //Last Filled:																				
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);						
						}
						else if(stripos($line,'Drug Prescribed:') !== false)
						{							
							$field_name = 'drug_name';//Medication: //Drug:							
							$field_val = trim(str_ireplace('Drug Prescribed:','',$line));					
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);						
						}
						else if(stripos($line,'SIG:') !== false){
							$field_name = 'sig';//SIG:
							$posSig = stripos($prescriptionContents,'SIG:');
							$field_val = substr($prescriptionContents,$posSig);						
							$field_val = trim(str_ireplace('SIG:','',$field_val));
							$field_val = trim(str_ireplace('Comments:','',$field_val));						
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);							
						}
						
					}//end foreach
				}
			}
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	return $responseArray;	
}

//remedyDrugHandler (expressRxHandler)
function remedyDrugHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	$tempArr = array('testName' => 'pharmacy_name', 'value' => 'Remedy Drug','flag'=>'','Reference'=>'');						
	array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		/*print "<pre>";
		print_r($rpt_lines);
		print "</pre>";*/
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			//Prescription Refill Request for:
			//if(in_array('REQUEST FOR A REFILL OR NEW PRESCRIPTION',$rpt_lines))
			//Multiple requests in single fax????
			if(stripos($reportContents,'REQUEST FOR FILL AUTHORIZATION')!== false)
			{
				$faxType = 'REQUEST FOR FILL AUTHORIZATION';
				$faxTypeMain = 'Refill Request';				
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'REQUEST FOR FILL AUTHORIZATION') !== false)
					{
						$field_name = 'request_date';//Date:   Requested Date:  Fax Date: 08/04/2019
						$nextKey = $key+1;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}
						$elements = explode("\t",$field_val);
						$field_val = isset($elements[1]) ? $elements[1]: '';	
						//$field_val = trim(str_ireplace('Fax Date:','',$field_val));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}//Rx: 4007970					
					else if(stripos($line,'Rx:') !== false){
						$field_name = 'rx_number';//Rx Number:						
						$field_val = trim(str_ireplace('Rx:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					else if(stripos($line,'Patient:') !== false){
						$field_name = 'Patient:';//Name:  (Lastname, FirstName) 
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';//JOHNSON, TINA						
						$field_val = trim(str_ireplace('Patient:','',$field_val));
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(',',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'DOB:') !== false){						
						$field_name = 'dob';													
						$field_val = trim(str_ireplace('DOB:','',$line));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}	
					else if(stripos($line,'Physician:') !== false){
						$field_name = 'prescriber_name';//PRESCRIBER:												
						$field_val = trim(str_ireplace('Physician:','',$line));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
							
					}										
					else if(stripos($line,'Drug:') !== false)
					{
						$field_name = 'drug_name';//Medication: //Drug:							
						$field_val = trim(str_ireplace('Drug:','',$line));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}					
					else if(stripos($line,'Directions:') !== false){
						$field_name = 'sig';//SIG:						
						$field_val = trim(str_ireplace('Directions:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);							
					}					
					else if(stripos($line,'Last Fill:') !== false){
						$field_name = 'date_last_filled';//Rx Number:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						//$field_val = trim(str_ireplace('Rx:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					else if(stripos($line,'Prev Fill:') !== false){
						$field_name = 'date_prev_filled';//Rx Number:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						//$field_val = trim(str_ireplace('Rx:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}// \nOrig Date: 12/20/2018 Quantity: 180 Cap\n
					else if(stripos($line,'Orig Date:') !== false && stripos($line,'Quantity:') !== false)
					{
						$field_name = 'date_written';
						$qtyPos = stripos($line,'Quantity:');
						$qtyStr = substr($line,$qtyPos);//to the end of line
						$field_val = substr($line,0,($qtyPos-1));
						$field_val = trim(str_ireplace('Orig Date:','',$field_val));
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						$field_name = 'qty_prescribed';
						$field_name = 'qty_dispensed';//Qty. Prescribed:  //Prescribed Qty:
					}									
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}									
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	return $responseArray;	
}

//Drug Emporium (remedyDrugHandler,expressRxHandler)
function drugEmporiumHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	
	//$tempArr = array('testName' => 'pharmacy_name', 'value' => 'Drug Emporium','flag'=>'','Reference'=>'');						
	//array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between		
		$reportContents = str_ireplace('DRUG EMPORIUM PHARMC','',$reportContents);
		$posStart = stripos($reportContents,'Drug Emporium');
		$reportContents = substr($reportContents,$posStart);//To end of report
		$rpt_lines = explode("\n",$reportContents);
		
		$pharmacy_name = isset($rpt_lines[0]) ? trim($rpt_lines[0]) : '';
		$pharmacy_address = isset($rpt_lines[1]) ? trim($rpt_lines[1]) : '';
		$pharmacy_city = isset($rpt_lines[2]) ? trim($rpt_lines[2]) : '';
		$pharmacy_state = $pharmacy_zip = $pharmacy_phone = $pharmacy_fax = '';
		if(stripos($pharmacy_city,',')!== false){
			$tempContent = explode(",",$pharmacy_city);
			$pharmacy_city = trim($tempContent[0]);
			$state = isset($tempContent[1]) ? trim($tempContent[1]) : '';
			if(!empty($state)){
				$tempContent = explode(" ",$state);
				$pharmacy_state = isset($tempContent[0]) ? trim($tempContent[0]) : '';
				$pharmacy_zip = isset($tempContent[1]) ? trim($tempContent[1]) : '';
			}
			
		}
		
		$tempContent = isset($rpt_lines[3]) ? trim($rpt_lines[3]) : '';
		if(stripos($tempContent,'Fax')!== false){
			$posFax = stripos($tempContent,'Fax');
			$pharmacy_fax = substr($tempContent,$posFax);
			$pharmacy_fax = trim(str_ireplace('Fax:','',$pharmacy_fax));
			$pharmacy_fax = trim(str_ireplace('Fax','',$pharmacy_fax));
			$pharmacy_phone = trim(substr($tempContent,0,$posFax));
		}
		$tempArr = array('testName' => 'pharmacy_name', 'value' => $pharmacy_name,'flag'=>'','Reference'=>'');						
		array_push($responseArray['rpt_detail'], $tempArr);
		
		$tempArr = array('testName' => 'pharmacy_address', 'value' => $pharmacy_address,'flag'=>'','Reference'=>'');						
		array_push($responseArray['rpt_detail'], $tempArr);
		
		$tempArr = array('testName' => 'pharmacy_city', 'value' => $pharmacy_city,'flag'=>'','Reference'=>'');						
		array_push($responseArray['rpt_detail'], $tempArr);
		$tempArr = array('testName' => 'pharmacy_state', 'value' => $pharmacy_state,'flag'=>'','Reference'=>'');						
		array_push($responseArray['rpt_detail'], $tempArr);
		$tempArr = array('testName' => 'pharmacy_zip', 'value' => $pharmacy_zip,'flag'=>'','Reference'=>'');						
		array_push($responseArray['rpt_detail'], $tempArr);
		$tempArr = array('testName' => 'pharmacy_phone', 'value' => $pharmacy_phone,'flag'=>'','Reference'=>'');						
		array_push($responseArray['rpt_detail'], $tempArr);
		$tempArr = array('testName' => 'pharmacy_fax', 'value' => $pharmacy_fax,'flag'=>'','Reference'=>'');						
		array_push($responseArray['rpt_detail'], $tempArr);
		//////////////////////////////////////////////////////////
		$controlDrugFormat = 0;
		if(stripos($reportContents,'REQUEST FOR FILL AUTHORIZATION')!== false)
		{
			if(stripos($reportContents,'NEW DEA CONTROL DRUG REQUIREMENTS')!== false){
				$controlDrugFormat = 1;
			}
			$posStart = stripos($reportContents,'REQUEST FOR FILL AUTHORIZATION');
			$reportContents = substr($reportContents,$posStart);//To end of contents
			$posStart = stripos($reportContents,'REQUEST FOR FILL AUTHORIZATION');
			if(stripos($reportContents,'Physician Section')!== false){				
				$posEnd = stripos($reportContents,'Physician Section');
				$reportContents = substr($reportContents,$posStart, ($posEnd - $posStart));
			}
			else if(stripos($reportContents,'New Order Authorization')!== false){
				$posEnd = stripos($reportContents,'New Order Authorization');
				$reportContents = substr($reportContents,$posStart, ($posEnd - $posStart));
			}
			else if(stripos($reportContents,'Now Order Authorization')!== false){
				$posEnd = stripos($reportContents,'Now Order Authorization');
				$reportContents = substr($reportContents,$posStart, ($posEnd - $posStart));
			}
		}
		$rpt_lines = explode("\n",$reportContents);
		/*print "<pre>";
		print_r($rpt_lines);
		print "</pre>";*/
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			//Prescription Refill Request for:
			//if(in_array('REQUEST FOR A REFILL OR NEW PRESCRIPTION',$rpt_lines))
			//Multiple requests in single fax????
			//
			if(stripos($reportContents,'REQUEST FOR FILL AUTHORIZATION')!== false)
			{
				$faxType = 'REQUEST FOR FILL AUTHORIZATION';
				$faxTypeMain = 'Refill Request';
				if($controlDrugFormat == 1){
					foreach($rpt_lines as $key => $line){
						if(stripos($line,'Patient:') !== false){
							$field_name = 'Patient:';//Name:  (Lastname, FirstName) 
							$elements = explode("\t",$line);
							$field_val = isset($elements[0]) ? $elements[0]: '';//JOHNSON, TINA						
							$field_val = trim(str_ireplace('Patient:','',$field_val));
							$field_val = trim(str_ireplace('Patient','',$field_val));							
							//$tempArr = array('key' => $field_name, 'value' => $field_val);
							//array_push($responseArray['rpt_header'], $tempArr);
							
							$pat_name = explode(',',$field_val);
							if(!empty($pat_name) && count($pat_name) > 0){
								$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
								array_push($responseArray['rpt_header'], $tempArr);
								$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
								array_push($responseArray['rpt_header'], $tempArr);
							}
							else{
								$tempArr = array('key' => 'first_name', 'value' => '');
								array_push($responseArray['rpt_header'], $tempArr);
								$tempArr = array('key' => 'last_name', 'value' => '');
								array_push($responseArray['rpt_header'], $tempArr);
							}
						}
						else if(stripos($line,'REQUEST FOR FILL AUTHORIZATION') !== false)
						{
							$field_name = 'request_date';//Date:   Requested Date:  Fax Date: 08/04/2019
							$nextKey = $key+1;
							$field_val = '';
							if (array_key_exists($nextKey,$rpt_lines)){
								$field_val = trim($rpt_lines[$nextKey]);
							}
							$elements = explode("\t",$field_val);
							$field_val = isset($elements[1]) ? $elements[1]: '';	
							//$field_val = trim(str_ireplace('Fax Date:','',$field_val));					
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);						
						}//Phone: (501) 993-7549 DOB: 04/02/1973 Rx Number: 4072475				
						else if(stripos($line,'Phone:') !== false && stripos($line,'DOB:') !== false && stripos($line,'Rx Number:') !== false){
							//$elements = explode("\t",$line);
							//$field_val = isset($elements[0]) ? $elements[0]: '';
							
							$posDob = stripos($line,'DOB:');
							$posRx = stripos($line,'Rx Number:');
							$dob = substr($line,$posDob,($posRx - $posDob));
							$field_name = 'dob';													
							$field_val = trim(str_ireplace('DOB:','',$dob));					
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
							
							$rx_number = substr($line,$posRx);//To end of line
							$field_name = 'rx_number';//Rx Number:												
							$field_val = trim(str_ireplace('Rx Number:','',$rx_number));						
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);						
						}													
						else if(stripos($line,'Medication:') !== false)
						{
							$field_name = 'drug_name';//Medication: //Drug:							
							$field_val = trim(str_ireplace('Medication:','',$line));					
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);						
						}
						else if(stripos($line,'Directions:') !== false){
							$field_name = 'sig';//SIG:	
							$posDir = stripos($line,'Directions:');
							$posFill = stripos($line,'Last Fill:');
							$dir = substr($line,$posDir,($posFill - $posDir));												
							$field_val = trim(str_ireplace('Directions:','',$dir));						
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);							
						}																	
						else if(stripos($line,'Last Fill:') !== false && stripos($line,'Original Date:') !== false){
							$field_name = 'date_last_filled';//Rx Number:
							//$elements = explode("\t",$line);
							//$field_val = isset($elements[1]) ? $elements[1]: '';						
							$posFill = stripos($line,'Last Fill:');
							$posDate = stripos($line,'Original Date:');
							
							$lastFill = substr($line,$posFill,($posDate - $posFill));
							$field_val = trim(str_ireplace('Last Fill:','',$lastFill));
							$field_val = substr($field_val,0,10);
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);
							
							//$field_val = trim(str_ireplace('Rx:','',$line));
							$origDate = substr($line,$posDate);
							$field_val = trim(str_ireplace('Original Date:','',$origDate));
							$field_name = 'date_written';	
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);					
													
						}
						else if(stripos($line,'Prev Fill:') !== false){
							$field_name = 'date_prev_filled';//Rx Number:
							//$elements = explode("\t",$line);
							//$field_val = isset($elements[1]) ? $elements[1]: '';						
							$field_val = trim(str_ireplace('Prev Fill:','',$line));	
							$field_val = substr($field_val,0,10);					
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);						
						}															
						else if(stripos($line,'Physician:') !== false){
							$field_name = 'prescriber_name';//PRESCRIBER:	
							$posPhone = stripos($line,'Phone:');
							$field_val = substr($line,0,$posPhone);											
							$field_val = trim(str_ireplace('Physician:','',$field_val));
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);						
								
						}	
						
						echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
						$elements = explode("\t",$line);
						
						print "<pre>";
						print_r($elements);
						print "</pre>";
						
					}//end foreach $rpt_lines
				}				
				else{
					foreach($rpt_lines as $key => $line){
					
						if(stripos($line,'REQUEST FOR FILL AUTHORIZATION') !== false)
						{
							$field_name = 'request_date';//Date:   Requested Date:  Fax Date: 08/04/2019
							$nextKey = $key+1;
							$field_val = '';
							if (array_key_exists($nextKey,$rpt_lines)){
								$field_val = trim($rpt_lines[$nextKey]);
							}
							$elements = explode("\t",$field_val);
							$field_val = isset($elements[1]) ? $elements[1]: '';	
							//$field_val = trim(str_ireplace('Fax Date:','',$field_val));					
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);						
						}//Rx: 4007970					
						else if(stripos($line,'Rx:') !== false){
							$field_name = 'rx_number';//Rx Number:	
							$elements = explode("\t",$line);
							$field_val = isset($elements[0]) ? $elements[0]: '';					
							$field_val = trim(str_ireplace('Rx:','',$field_val));						
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);						
						}
						else if(stripos($line,'Patient:') !== false){
							$field_name = 'Patient:';//Name:  (Lastname, FirstName) 
							$elements = explode("\t",$line);
							$field_val = isset($elements[0]) ? $elements[0]: '';//JOHNSON, TINA						
							$field_val = trim(str_ireplace('Patient:','',$field_val));
							//$tempArr = array('key' => $field_name, 'value' => $field_val);
							//array_push($responseArray['rpt_header'], $tempArr);
							
							$pat_name = explode(',',$field_val);
							if(!empty($pat_name) && count($pat_name) > 0){
								$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
								array_push($responseArray['rpt_header'], $tempArr);
								$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
								array_push($responseArray['rpt_header'], $tempArr);
							}
							else{
								$tempArr = array('key' => 'first_name', 'value' => '');
								array_push($responseArray['rpt_header'], $tempArr);
								$tempArr = array('key' => 'last_name', 'value' => '');
								array_push($responseArray['rpt_header'], $tempArr);
							}
						}
						else if(stripos($line,'DOB') !== false){						
							$field_name = 'dob';													
							$field_val = trim(str_ireplace('DOB','',$line));
							$field_val = trim(str_ireplace(':','',$field_val));
							$field_val = trim(str_ireplace(';','',$field_val));
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);
							
						}	
						else if(stripos($line,'Physician:') !== false){
							$field_name = 'prescriber_name';//PRESCRIBER:												
							$field_val = trim(str_ireplace('Physician:','',$line));
							$tempArr = array('key' => $field_name, 'value' => $field_val);
							array_push($responseArray['rpt_header'], $tempArr);						
								
						}										
						else if(stripos($line,'Drug:') !== false)
						{
							$field_name = 'drug_name';//Medication: //Drug:							
							$field_val = trim(str_ireplace('Drug:','',$line));					
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);						
						}					
						else if(stripos($line,'Directions:') !== false){
							$field_name = 'sig';//SIG:						
							$field_val = trim(str_ireplace('Directions:','',$line));						
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);							
						}					
						else if(stripos($line,'Last Fill:') !== false){
							$field_name = 'date_last_filled';//Rx Number:
							$elements = explode("\t",$line);
							$field_val = isset($elements[1]) ? $elements[1]: '';						
							//$field_val = trim(str_ireplace('Rx:','',$line));						
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);						
						}
						else if(stripos($line,'Prev Fill:') !== false){
							$field_name = 'date_prev_filled';//Rx Number:
							$elements = explode("\t",$line);
							$field_val = isset($elements[1]) ? $elements[1]: '';						
							//$field_val = trim(str_ireplace('Rx:','',$line));						
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);						
						}// \nOrig Date: 12/20/2018 Quantity: 180 Cap\n
						else if(stripos($line,'Orig Date:') !== false && stripos($line,'Quantity:') !== false)
						{
							$field_name = 'date_written';
							$qtyPos = stripos($line,'Quantity:');
							$qtyStr = substr($line,$qtyPos);//to the end of line
							$field_val = substr($line,0,($qtyPos-1));
							$field_val = trim(str_ireplace('Orig Date:','',$field_val));
							$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
							array_push($responseArray['rpt_detail'], $tempArr);
							
							$field_name = 'qty_prescribed';
							$field_name = 'qty_dispensed';//Qty. Prescribed:  //Prescribed Qty:
						}									
						
						echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
						$elements = explode("\t",$line);
						
						print "<pre>";
						print_r($elements);
						print "</pre>";
						
					}//end foreach $rpt_lines
				}
			}									
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	return $responseArray;	
}

function prescPadPharmacyHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	$tempArr = array('testName' => 'pharmacy_name', 'value' => 'Prescription Pad','flag'=>'','Reference'=>'');						
	array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		print "<pre>";
		print_r($rpt_lines);
		print "</pre>";
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			
			/*foreach($rpt_lines as $key => $line){
				
				echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
				$elements = explode("\t",$line);
				
				print "<pre>";
				print_r($elements);
				print "</pre>";
			}*/
			
			//if(in_array('Request Refill Authorization From',$rpt_lines))
			if(stripos($reportContents,'Request Refill Authorization From:') !== false)
			{
				$faxType = 'Request Refill Authorization From';
				$faxTypeMain = 'Refill Request';
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'Date:') !== false && stripos($line,'Medication:') !== false){
						$field_name = 'request_date';//Date:   Requested Date:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: ''; //mm/dd/yyyy	
						//$field_val = trim(str_ireplace('Date:','',$field_val));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);	
						
						$field_name = 'drug_name';//Medication: //Drug:						
						$field_val = isset($elements[2]) ? $elements[2]: '';
						$field_val = trim(str_ireplace('Medication:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
											
					}
					else if(stripos($line,'Qty Written:') !== false){
						$field_name = 'qty_prescribed';//Qty. Prescribed:  //Prescribed Qty:
						$elements = explode("\t",$line);
						$field_val = '';
						foreach($elements as $item){
							if(stripos($item,'Qty Written:') !== false){
								$field_val = trim(str_ireplace('Qty Written:','',$item));
							}
						}
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}					
					else if(stripos($line,'Rx:') !== false && (stripos($line,'Date Wile:') !== false || stripos($line,'Date Written:') !== false) && stripos($line,'Last Filled:') !== false){
						$elements = explode("\t",$line);
						foreach($elements as $item){
							if(stripos($item,'Rx:') !== false){
								$field_name = 'rx_number';//Rx Number:
								$field_val = trim(str_ireplace('Rx:','',$item));						
								$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
								array_push($responseArray['rpt_detail'], $tempArr);
							}
							else if(stripos($item,'Date Wile:') !== false || stripos($item,'Date Written:') !== false){
								$field_name = 'date_written';//Date Written:
								$field_val = trim(str_ireplace('Date Wile:','',$item));	
								$field_val = trim(str_ireplace('Date Written:','',$field_val));					
								$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
								array_push($responseArray['rpt_detail'], $tempArr);	
							}
							
						}
					}
					else if(stripos($line,'Last Filled:') !== false && stripos($line,'Directions:') !== false){
						$elements = explode("\t",$line);
						$field_name = 'date_last_filled';//Date Last Filled:   //Last Filled:
						$field_val = '';
						if(isset($elements[1])){																					
							$field_val = trim($elements[1]);													
						}
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
					}
					//Dispensed 6\ttime(s) for a total Qty of 180.000
					else if(stripos($line,'Dispensed') !== false && stripos($line,'total Qty') !== false){
						$elements = explode("\t",$line);
						$field_name = 'sig';//SIG:
						$field_val = isset($elements[2]) ? $elements[2]: '';						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
					}
					else if(stripos($line,'Refills Originally Authorized:') !== false){
						$field_name = 'refills';//Prescribed Refills:						
						$field_val = trim(str_ireplace('Refills Originally Authorized:','',$line));												
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					else if(stripos($line,'Plus') !== false && stripos($line,'Refills') !== false && stripos($line,'Date:') !== false){
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';//Fname Lname
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'DOB:') !== false && stripos($line,'Phone:') !== false){
						//DOB: 10/07/1955 Phone: (501) 943-2165
						$posPh = stripos($line,'Phone:');
						$phNumber = substr($line,$posPh);//to the end of line
						$dobStr = '';
						if($posPh > 0){
							$dobStr = substr($line,0,$posPh-1);
						}
						$field_name = 'dob';
						$field_val = '';
						if(!empty($dobStr)){
							$field_val = trim(str_ireplace('DOB:','',$dobStr));	
						}												
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
					}
					/*
					else if(stripos($line,'PRESCRIBER:') !== false || stripos($line,'PRESCRIBER :') !== false){
						$field_name = 'PRESCRIBER:';
					}
					else if(stripos($line,'Name:') !== false && stripos($line,'From:') !== false){
						$field_name = 'prescriber_name';//PRESCRIBER:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						$tempArr = array('key' => $field_name, 'value' => $field_val);

						array_push($responseArray['rpt_header'], $tempArr);						
							
					} //Store # 17611
					else if(stripos($line,'FOR PATIENT:') !== false || stripos($line,'FOR PATIENT :') !== false){
						$field_name = 'FOR PATIENT:';
					}														
					else if(stripos($line,'Pharmacy Comments:') !== false){
						$field_name = 'pharmacy_comments';//Pharmacy Comments:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);							
					}*/
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
				
			}						
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	return $responseArray;
}

function watsonPharmacyHandler($fileName='',$reportContents='', $configurationName = '',$email_addr=''){
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	$tempArr = array('testName' => 'pharmacy_name', 'value' => 'Watson Pharmacy','flag'=>'','Reference'=>'');						
	array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		print "<pre>";
		print_r($rpt_lines);
		print "</pre>";
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			
			/*foreach($rpt_lines as $key => $line){
				
				echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
				$elements = explode("\t",$line);
				
				print "<pre>";
				print_r($elements);
				print "</pre>";
			}*/
			
			//if(in_array('Request Refill Authorization From',$rpt_lines))
			if(stripos($reportContents,'Request Refill Authorization From:') !== false)
			{
				$faxType = 'Request Refill Authorization From';
				$faxTypeMain = 'Refill Request';
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'Date:') !== false && stripos($line,'Medication:') !== false){
						$field_name = 'request_date';//Date:   Requested Date:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: ''; //mm/dd/yyyy	
						//$field_val = trim(str_ireplace('Date:','',$field_val));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);	
						
						$field_name = 'drug_name';//Medication: //Drug:						
						$field_val = isset($elements[2]) ? $elements[2]: '';
						$field_val = trim(str_ireplace('Medication:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
											
					}
					else if(stripos($line,'Qty Written:') !== false){
						$field_name = 'qty_prescribed';//Qty. Prescribed:  //Prescribed Qty:
						$elements = explode("\t",$line);
						$field_val = '';
						foreach($elements as $item){
							if(stripos($item,'Qty Written:') !== false){
								$field_val = trim(str_ireplace('Qty Written:','',$item));
							}
						}
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}					
					else if(stripos($line,'Rx:') !== false && (stripos($line,'Date Wile:') !== false || stripos($line,'Date Written:') !== false) && stripos($line,'Last Filled:') !== false){
						$elements = explode("\t",$line);
						foreach($elements as $item){
							if(stripos($item,'Rx:') !== false){
								$field_name = 'rx_number';//Rx Number:

								$field_val = trim(str_ireplace('Rx:','',$item));						
								$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
								array_push($responseArray['rpt_detail'], $tempArr);
							}
							else if(stripos($item,'Date Wile:') !== false || stripos($item,'Date Written:') !== false){
								$field_name = 'date_written';//Date Written:
								$field_val = trim(str_ireplace('Date Wile:','',$item));	
								$field_val = trim(str_ireplace('Date Written:','',$field_val));					
								$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
								array_push($responseArray['rpt_detail'], $tempArr);	
							}
							
						}
					}
					else if(stripos($line,'Last Filled:') !== false && stripos($line,'Directions:') !== false){
						$elements = explode("\t",$line);
						$field_name = 'date_last_filled';//Date Last Filled:   //Last Filled:
						$field_val = '';
						if(isset($elements[1])){																					
							$field_val = trim($elements[1]);													
						}
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
					}					
					else if(stripos($line,'Dispensed') !== false && stripos($line,'total Qty') !== false){
						$elements = explode("\t",$line);
						$field_name = 'sig';//SIG:
						$field_val = isset($elements[2]) ? $elements[2]: '';						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
					}
					else if(stripos($line,'Refills Originally Authorized:') !== false){
						$field_name = 'refills';//Prescribed Refills:						
						$field_val = trim(str_ireplace('Refills Originally Authorized:','',$line));												
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					else if(stripos($line,'Plus') !== false && stripos($line,'Refills') !== false && stripos($line,'Date:') !== false){
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';//Fname Lname
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'DOB:') !== false && stripos($line,'Phone:') !== false){
						//DOB: 10/07/1955 Phone: (501) 943-2165
						$posPh = stripos($line,'Phone:');
						$phNumber = substr($line,$posPh);//to the end of line
						$dobStr = '';
						if($posPh > 0){
							$dobStr = substr($line,0,$posPh-1);
						}
						$field_name = 'dob';
						$field_val = '';
						if(!empty($dobStr)){
							$field_val = trim(str_ireplace('DOB:','',$dobStr));	
						}												
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
					}
					/*
					else if(stripos($line,'PRESCRIBER:') !== false || stripos($line,'PRESCRIBER :') !== false){
						$field_name = 'PRESCRIBER:';
					}
					else if(stripos($line,'Name:') !== false && stripos($line,'From:') !== false){
						$field_name = 'prescriber_name';//PRESCRIBER:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						$tempArr = array('key' => $field_name, 'value' => $field_val);

						array_push($responseArray['rpt_header'], $tempArr);						
							
					} //Store # 17611
					else if(stripos($line,'FOR PATIENT:') !== false || stripos($line,'FOR PATIENT :') !== false){
						$field_name = 'FOR PATIENT:';
					}														
					else if(stripos($line,'Pharmacy Comments:') !== false){
						$field_name = 'pharmacy_comments';//Pharmacy Comments:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);							
					}*/
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
				
			}
			else if(stripos($reportContents,'New Controlled Rx Authorization Form:') !== false)
			{
				$faxType = 'New Controlled Rx Authorization Form';
				$faxTypeMain = 'Controlled Refill Request';
				
				$posSentBy = stripos($reportContents,'Sent By:');
				$tempContents = substr($reportContents,$posSentBy);// Taken from Sent By to end of report
				
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'Patient:') !== false && stripos($line,'Patient Name:') !== false){
						
						$elements = explode("\t",$line); //FAITH E MCGILL
						$field_val = isset($elements[0]) ? $elements[0]: '';//Fname Mid Lname
						$field_val = trim(str_ireplace('Patient:','',$field_val));
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$lname = '';
							if(count($pat_name) > 1){
								$lname = isset($pat_name[2]) ? $pat_name[2]: $pat_name[1];
							}
							$tempArr = array('key' => 'last_name', 'value' => $lname);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'DOB:') !== false){
						//DOB: 5/15/1978
						$field_name = 'dob';
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';																		
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}
					else if(stripos($line,'Rx:') !== false){
						$field_name = 'rx_number';//Rx Number:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					else if(stripos($line,'Medication') !== false && strlen(trim($line)) > 12){//'Medication' exist two times						
						$field_name = 'drug_name';//Medication: //Drug:						
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Medication','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						///////////////////////////////////////////////////////
						$field_name = 'qty_prescribed';//Qty. Prescribed:  //Prescribed Qty:
						$nextKey = $key+1;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
							$elements = explode("\t",$field_val);
							$field_val = isset($elements[0]) ? $elements[0]: '';
							$field_val = trim(str_ireplace('Qty Written:','',$field_val));
						}
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
																	
					}	
					else if(stripos($line,'Refills Originally Authorized:') !== false){
						$field_name = 'refills';//Prescribed Refills:
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';						
						$field_val = trim(str_ireplace('Refills Originally Authorized:','',$field_val));												
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}	
					/*else if(stripos($line,'Directions:') !== false && stripos($line,'Directions:') > stripos($line,'Refills Originally Authorized:')){
						$field_name = 'sig';//SIG:
						$field_val = trim(str_ireplace('Directions:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
					}*/	
					else if(stripos($line,'Last Filled:') !== false && stripos($line,'Date Written:') !== false){
						$elements = explode("\t",$line);
						$field_name = 'date_last_filled';//Date Last Filled:   //Last Filled:
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						////////////////////////////
						$field_name = 'date_written';//Date Last Filled:   //Last Filled:
						$field_val = isset($elements[2]) ? $elements[2]: '';	
						$field_val = trim(str_ireplace('Date Written:','',$field_val));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
					}		
					/*else if(stripos($line,'Date:') !== false && stripos($line,'Date:') > stripos($line,'Sent By:')){
						$field_name = 'request_date';//Date:   Requested Date:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: ''; //Date:	01/22/2020 8:09 AM	
						//$field_val = trim(str_ireplace('Date:','',$field_val));					
						if(count(explode("/",$field_val)) > 2){
							$tempArr = array('key' => $field_name, 'value' => $field_val);
						}
						else{
							$field_val = trim(str_ireplace('/','',$field_val));
							$field_val = substr($field_val,0,2).'/'.substr($field_val,2,2).'/'.substr($field_val,4);
							$tempArr = array('key' => $field_name, 'value' => $field_val);
						}
						array_push($responseArray['rpt_header'], $tempArr);		
											
					}*/
										
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
				
				/////////////////////////////////////////
				$posRefill = stripos($reportContents,'Refills Originally Authorized:');
				$reportContents = substr($reportContents,$posRefill);
				$rpt_lines = explode("\n",$reportContents);
				foreach($rpt_lines as $key => $line){
					if(stripos($line,'Directions:') !== false ){
						$field_name = 'sig';//SIG:
						$field_val = trim(str_ireplace('Directions:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
						break;
					}
				}
				////////////////////////////////////////////
				$rpt_lines = explode("\n",$tempContents);
				$line = isset($rpt_lines[1]) ? trim($rpt_lines[1]) : '';
				if(stripos($line,'Date:') !== false){
					$field_name = 'request_date';//Date:   Requested Date:
					$elements = explode("\t",$line);
					$field_val = isset($elements[1]) ? $elements[1]: ''; //Date:	01/22/2020 8:09 AM	
					//$field_val = trim(str_ireplace('Date:','',$field_val));					
					if(count(explode("/",$field_val)) > 2){
						$tempArr = array('key' => $field_name, 'value' => $field_val);
					}
					else{
						$field_val = trim(str_ireplace('/','',$field_val));
						$field_val = substr($field_val,0,2).'/'.substr($field_val,2,2).'/'.substr($field_val,4);
						$tempArr = array('key' => $field_name, 'value' => $field_val);
					}
					array_push($responseArray['rpt_header'], $tempArr);		
										
				}
				
			}
			else if(in_array('Prior Authorization Request',$rpt_lines)){
				$faxType = 'Prior Authorization Request';
				$faxTypeMain = 'PA Request';
			}			
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	return $responseArray;	
}

function smithPharmacyHandler($fileName='',$reportContents='', $configurationName = '',$email_addr=''){
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	if($configurationName=='smithDrug')
		$tempArr = array('testName' => 'pharmacy_name', 'value' => 'Smith Drug and Compounding','flag'=>'','Reference'=>'');						
	else
		$tempArr = array('testName' => 'pharmacy_name', 'value' => 'Smith Family Pharmacy','flag'=>'','Reference'=>'');
		
	array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		/*print "<pre>";
		print_r($rpt_lines);
		print "</pre>";*/
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			//Prescription Refill Request for:
			//if(in_array('REQUEST FOR A REFILL OR NEW PRESCRIPTION',$rpt_lines))
			//Multiple requests in single fax????
			if(stripos($reportContents,'REQUEST FOR FILL AUTHORIZATION')!== false)
			{
				$faxType = 'REQUEST FOR FILL AUTHORIZATION';
				$faxTypeMain = 'Refill Request';				
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'REQUEST FOR FILL AUTHORIZATION') !== false)
					{
						$field_name = 'request_date';//Date:   Requested Date:  Fax Date: 08/04/2019
						$nextKey = $key+1;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}
						$elements = explode("\t",$field_val);
						$field_val = isset($elements[1]) ? $elements[1]: '';	
						//$field_val = trim(str_ireplace('Fax Date:','',$field_val));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}//Rx: 4007970					
					else if(stripos($line,'Rx:') !== false){
						$field_name = 'rx_number';//Rx Number:						
						$field_val = trim(str_ireplace('Rx:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					else if(stripos($line,'Patient:') !== false){
						$field_name = 'Patient:';//Name:  (Lastname, FirstName) 
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';//JOHNSON, TINA						
						$field_val = trim(str_ireplace('Patient:','',$field_val));
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(',',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'DOB:') !== false){						
						$field_name = 'dob';													
						$field_val = trim(str_ireplace('DOB:','',$line));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}	
					else if(stripos($line,'Physician:') !== false){
						$field_name = 'prescriber_name';//PRESCRIBER:												
						$field_val = trim(str_ireplace('Physician:','',$line));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
							
					}										
					else if(stripos($line,'Drug:') !== false)
					{
						$field_name = 'drug_name';//Medication: //Drug:							
						$field_val = trim(str_ireplace('Drug:','',$line));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}					
					else if(stripos($line,'Directions:') !== false){
						$field_name = 'sig';//SIG:						
						$field_val = trim(str_ireplace('Directions:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);							
					}					
					else if(stripos($line,'Last Fill:') !== false){
						$field_name = 'date_last_filled';//Rx Number:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						//$field_val = trim(str_ireplace('Rx:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					else if(stripos($line,'Prev Fill:') !== false){
						$field_name = 'date_prev_filled';//Rx Number:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						//$field_val = trim(str_ireplace('Rx:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}// \nOrig Date: 12/20/2018 Quantity: 180 Cap\n
					else if(stripos($line,'Orig Date:') !== false && stripos($line,'Quantity:') !== false)
					{
						$field_name = 'date_written';
						$qtyPos = stripos($line,'Quantity:');
						$qtyStr = substr($line,$qtyPos);//to the end of line
						$field_val = substr($line,0,($qtyPos-1));
						$field_val = trim(str_ireplace('Orig Date:','',$field_val));
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						$field_name = 'qty_prescribed';
						$field_name = 'qty_dispensed';//Qty. Prescribed:  //Prescribed Qty:
					}									
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}									
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	return $responseArray;
}

function blandfordPharmacyHandler($fileName='',$reportContents='', $configurationName = '',$email_addr=''){
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	$tempArr = array('testName' => 'pharmacy_name', 'value' => 'Blandford Pharmacy','flag'=>'','Reference'=>'');						
	array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		/*print "<pre>";
		print_r($rpt_lines);
		print "</pre>";*/
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			//Prescription Refill Request for:
			//if(in_array('REQUEST FOR A REFILL OR NEW PRESCRIPTION',$rpt_lines))
			//Multiple requests in single fax????
			if(stripos($reportContents,'REFILL AUTHORTIZATTION	REQUEST')!== false || stripos($reportContents,'REFILL	AUTHORTIZATTION	REQUEST')!== false)
			{
				$faxType = 'REFILL AUTHORTIZATTION	REQUEST';
				$faxTypeMain = 'Refill Request';				
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'Fax reference LLLCZCYYSRYLLL') !== false)
					{
						$field_name = 'request_date';//Date:   Requested Date:  Fax Date: 08/04/2019
						/*$nextKey = $key+1;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}
						$elements = explode("\t",$field_val);
						$field_val = isset($elements[1]) ? $elements[1]: '';*/	
						$field_val = trim(str_ireplace('Fax reference LLLCZCYYSRYLLL','',$line));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}
					else if(stripos($line,'PATIENT:') !== false){
						$field_name = 'Patient:';//Name:  (Lastname, FirstName) 
						//$elements = explode("\t",$line);
						//$field_val = isset($elements[0]) ? $elements[0]: '';//ALWADII, HALIA						
						$field_val = trim(str_ireplace('PATIENT:','',$line));
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(',',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'DOB:') !== false && stripos($line,'SEX:') !== false){						
						$posSex = stripos($line,'SEX:');
						$field_name = 'dob';
						$field_val = substr($line,0,($posSex-1));													
						$field_val = trim(str_ireplace('DOB:','',$field_val));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}//Rx: 4007970					
					else if(stripos($line,'PRESCRIBED:') !== false && stripos($line,'PHARMACY RXNBR:') !== false)
					{
						$field_name = 'drug_name';//Medication: //Drug:	
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';						
						$field_val = trim(str_ireplace('PRESCRIBED:','',$line));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						///////////////////////////
						$field_name = 'rx_number';//Rx Number:
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						$field_val = trim(str_ireplace('PHARMACY RXNBR:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);							
					}											
					/*else if(stripos($line,'Physician:') !== false){
						$field_name = 'prescriber_name';//PRESCRIBER:												
						$field_val = trim(str_ireplace('Physician:','',$line));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
							
					}*/	
					else if(stripos($line,'WRITTEN:') !== false && stripos($line,'ORIG QUANTITY:') !== false)
					{
						$field_name = 'date_written';
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						$field_name = 'qty_prescribed';
						//$qtyPos = stripos($line,'Quantity:');
						//$qtyStr = substr($line,$qtyPos);//to the end of line
						//$field_val = substr($line,0,($qtyPos-1));
						$field_val = isset($elements[1]) ? $elements[1]: '';
						$field_val = trim(str_ireplace('ORIG QUANTITY:','',$field_val));
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						$field_name = 'refills';//Prescribed Refills:	
						$field_val = isset($elements[2]) ? $elements[2]: '';
						$field_val = explode(":",$field_val);
						$field_val = isset($field_val[1]) ? trim($field_val[1]): 0;					
						//$field_val = trim(str_ireplace('AUTH D REFILLS:','',$line));												
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						$field_name = 'qty_dispensed';//Qty. Prescribed:  //Prescribed Qty:
					}									
					else if(stripos($line,'LAST FILL:') !== false && stripos($line,'QUANTITY LEFT:') !== false && stripos($line,'DAYS SUPPLY:') !== false){
						//LAST FILL: 10/03/19\tQUANTITY LEFT: O\tDAYS SUPPLY:
						$field_name = 'date_last_filled';//Rx Number:
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';						
						$field_val = trim(str_ireplace('LAST FILL:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
												
					}
					else if(stripos($line,'DIRECTIONS:') !== false){
						$field_name = 'sig';//SIG:						
						$field_val = trim(str_ireplace('DIRECTIONS:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);							
					}
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}									
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	return $responseArray;	
}

function super1PharmacyHandler($fileName='',$reportContents='', $configurationName = '',$email_addr=''){
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	$tempArr = array('testName' => 'pharmacy_name', 'value' => 'Super 1 Pharmacy','flag'=>'','Reference'=>'');						
	array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		/*print "<pre>";
		print_r($rpt_lines);
		print "</pre>";*/
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			//Prescription Refill Request for:
			//if(in_array('REQUEST FOR A REFILL OR NEW PRESCRIPTION',$rpt_lines))
			if(stripos($reportContents,'REFILL AUTHORIZATION')!== false)
			{
				$faxType = 'REFILL AUTHORIZATION';
				$faxTypeMain = 'Refill Request';				
				foreach($rpt_lines as $key => $line){
					//Patient Information:	Prescriber Information:
					if(stripos($line,'Patient Information:') !== false && stripos($line,'Prescriber Information:') !== false){
						$nextKey = $key+1;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}
						$elements = explode("\t",$field_val);
						$field_val = isset($elements[0]) ? $elements[0]: '';//AHMAD, SHAHID (Lname, Fname)
						$pat_name = explode(',',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						//////////////////////////////////////////
						$field_name = 'prescriber_name';//PRESCRIBER:						
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						//$field_val = trim(str_ireplace('Prescription Refill Request for:','',$field_val));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
							
					}
					else if(stripos($line,'DOB:') !== false){						
						$field_name = 'dob';
						$elements = explode("\t",$line);						
						$field_val = isset($elements[0]) ? $elements[0]: '';// 01/19/1962	
						$field_val = trim(str_ireplace('DOB:','',$field_val));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}
					else if(stripos($line,'Prescription Information:') !== false)
					{
						$nextKey = $key+1;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}
						$field_name = 'drug_name';//Medication: //Drug:
						//$elements = explode("\t",$line);
						//$field_val = isset($elements[0]) ? $elements[0]: '';	
						//$field_val = trim(str_ireplace('Drug Dispensed:','',$field_val));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
					}
					else if(stripos($line,'Written Quantity:') !== false){
						$field_name = 'qty_prescribed';//Qty. Prescribed:  //Prescribed Qty:
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Written Quantity:','',$field_val));							
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
						
						/*$field_name = 'qty_dispensed';//Qty. Prescribed:  //Prescribed Qty:						
						$field_val = isset($elements[1]) ? $elements[1]: '';
						$field_val = trim(str_ireplace('Dispensed Quantity:','',$field_val));							
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	*/
					}
					/*else if(stripos($line,'SIG') !== false){
						$field_name = 'sig';//SIG:						
						$field_val = trim(str_ireplace('SIG:','',$line));						
						//$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						//array_push($responseArray['rpt_detail'], $tempArr);							
					}					
					else if(stripos($line,'Fax Date:') !== false)
					{
						$field_name = 'request_date';//Date:   Requested Date:  Fax Date: 08/04/2019
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';	
						$field_val = trim(str_ireplace('Fax Date:','',$field_val));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}*/					
					else if(stripos($line,'Rx #') !== false || stripos($line,'Fx #') !== false){
						$field_name = 'rx_number';//Rx Number:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';
						$field_val = trim(str_ireplace('Rx #','',$field_val));	
						$field_val = trim(str_ireplace('Fx #','',$field_val));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					else if(stripos($line,'Last Fill Date:') !== false){
						$field_name = 'date_last_filled';						
						$field_val = trim(str_ireplace('Last Fill Date:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}
			else if(stripos($reportContents,'CONTROLLED SUBSTANCE REFILL REMINDER')!== false)
			{
				$faxType = 'CONTROLLED SUBSTANCE REFILL REMINDER';
				$faxTypeMain = 'Controlled Substance Refill Request';				
				foreach($rpt_lines as $key => $line){
					//Patient Information:	Prescriber Information:
					if((stripos($line,'Patient Information:') !== false || stripos($line,'Fatient Information:') !== false) && (stripos($line,'Prescriber Information:') !== false || stripos($line,'Frescriber Information:') !== false)){
						$nextKey = $key+1;
						$field_val = '';

						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}
						$elements = explode("\t",$field_val);
						$field_val = isset($elements[0]) ? $elements[0]: '';//AHMAD, SHAHID (Lname, Fname)
						$pat_name = explode(',',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						//////////////////////////////////////////
						$field_name = 'prescriber_name';//PRESCRIBER:						
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						//$field_val = trim(str_ireplace('Prescription Refill Request for:','',$field_val));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
							
					}
					else if(stripos($line,'DOB:') !== false){						
						$field_name = 'dob';
						$elements = explode("\t",$line);						
						$field_val = isset($elements[0]) ? $elements[0]: '';// 01/19/1962	
						$field_val = trim(str_ireplace('DOB:','',$field_val));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}
					else if(stripos($line,'Prescription Information:') !== false)
					{
						$nextKey = $key+1;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}
						$field_name = 'drug_name';//Medication: //Drug:
						//$elements = explode("\t",$line);
						//$field_val = isset($elements[0]) ? $elements[0]: '';	
						//$field_val = trim(str_ireplace('Drug Dispensed:','',$field_val));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
					}
					else if(stripos($line,'Written Quantity:') !== false){
						$field_name = 'qty_prescribed';//Qty. Prescribed:  //Prescribed Qty:
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Written Quantity:','',$field_val));							
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
						
						/*$field_name = 'qty_dispensed';//Qty. Prescribed:  //Prescribed Qty:						
						$field_val = isset($elements[1]) ? $elements[1]: '';
						$field_val = trim(str_ireplace('Dispensed Quantity:','',$field_val));							
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	*/
					}
					/*else if(stripos($line,'SIG') !== false){
						$field_name = 'sig';//SIG:						
						$field_val = trim(str_ireplace('SIG:','',$line));						
						//$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						//array_push($responseArray['rpt_detail'], $tempArr);							
					}					
					else if(stripos($line,'Fax Date:') !== false)
					{
						$field_name = 'request_date';//Date:   Requested Date:  Fax Date: 08/04/2019
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';	
						$field_val = trim(str_ireplace('Fax Date:','',$field_val));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}*/					
					else if(stripos($line,'Rx #') !== false || stripos($line,'Fx #') !== false){
						$field_name = 'rx_number';//Rx Number:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';
						$field_val = trim(str_ireplace('Rx #','',$field_val));	
						$field_val = trim(str_ireplace('Fx #','',$field_val));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					else if(stripos($line,'Last Fill Date:') !== false){
						$field_name = 'date_last_filled';						
						$field_val = trim(str_ireplace('Last Fill Date:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}			
		}//end if rpt_lines
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	return $responseArray;
}

//eaglePharmacyHandler
function eaglePharmacyHandler($fileName='',$reportContents='', $configurationName = '',$email_addr=''){
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	$tempArr = array('testName' => 'pharmacy_name', 'value' => 'Eagle Pharmacy','flag'=>'','Reference'=>'');						
	array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		/*print "<pre>";
		print_r($rpt_lines);
		print "</pre>";*/
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			//Prescription Refill Request for:
			//if(in_array('REQUEST FOR A REFILL OR NEW PRESCRIPTION',$rpt_lines))
			if(stripos($reportContents,'INCOMPLETE ENROLLMENT')!== false)
			{
				$faxType = 'INCOMPLETE ENROLLMENT';
				$faxTypeMain = 'INCOMPLETE ENROLLMENT';				
				foreach($rpt_lines as $key => $line){
					//Patient Information:	Prescriber Information:
					if(stripos($line,'Patient Name:') !== false){						
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';//WALTER RAGLAND
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						//////////////////////////////////////////
						/*$field_name = 'prescriber_name';//PRESCRIBER:						
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						//$field_val = trim(str_ireplace('Prescription Refill Request for:','',$field_val));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);	*/					
							
					}
					else if(stripos($line,'Patient DOB:') !== false){						
						$field_name = 'dob';
						$elements = explode("\t",$line);						
						$field_val = isset($elements[1]) ? $elements[1]: '';// 01/19/1962	
						//$field_val = trim(str_ireplace('Patient DOB:','',$field_val));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}
			else if(stripos($reportContents,'To Whom it May Concern')!== false)
			{
				$faxType = 'In-Appropriate Recipient';
				$faxTypeMain = 'In-Appropriate Recipient';				
				foreach($rpt_lines as $key => $line){
					//Patient Information:	Prescriber Information:
					if(stripos($line,'Patient First Name:') !== false){						
						$field_val = trim(str_ireplace('Patient First Name:','',$line));
						$tempArr = array('key' => 'first_name', 'value' => isset($field_val) ? trim($field_val) : '');
						array_push($responseArray['rpt_header'], $tempArr);
					}
					else if(stripos($line,'Patient Last Name:') !== false){												
						$field_val = trim(str_ireplace('Patient Last Name:','',$line));
						$tempArr = array('key' => 'last_name', 'value' => isset($field_val) ? trim($field_val) : '');
						array_push($responseArray['rpt_header'], $tempArr);							
					}
					else if(stripos($line,'DOB:') !== false){						
						$field_name = 'dob';							
						$field_val = trim(str_ireplace('DOB:','',$line));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}			
		}//end if rpt_lines
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	return $responseArray;
}

function envolvePharmacyHandler($fileName='',$reportContents='', $configurationName = '',$email_addr=''){
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	$tempArr = array('testName' => 'pharmacy_name', 'value' => 'Envolve Pharmacy','flag'=>'','Reference'=>'');						
	array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		/*print "<pre>";
		print_r($rpt_lines);
		print "</pre>";*/
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			//Prescription Refill Request for:
			//if(in_array('REQUEST FOR A REFILL OR NEW PRESCRIPTION',$rpt_lines))
			if(stripos($reportContents,'Prior Authorization Request')!== false)
			{
				$faxType = 'Prior Authorization Request';
				$faxTypeMain = 'PA Request';				
				foreach($rpt_lines as $key => $line){
					//Patient Information:	Prescriber Information:
					if(stripos($line,'Member name:') !== false){						
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';//SABINO RESENDIZ ARREO
						$field_val = trim(str_ireplace('Member name:','',$field_val));
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						//////////////////////////////////////////
						$field_name = 'prescriber_name';//PRESCRIBER:						
						$field_val = isset($elements[0]) ? $elements[0]: '';						
						$field_val = trim(str_ireplace('Prescriber name (print):','',$field_val));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);					
							
					}
					else if(stripos($line,'Date of Birth:') !== false){						
						$field_name = 'dob';
						$elements = explode("\t",$line);						
						$field_val = isset($elements[1]) ? $elements[1]: '';// 01/19/1962	
						$field_val = trim(str_ireplace('Date of Birth:','',$field_val));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}
					else if('Drug name and strength:'){
						$nextKey = $key+1;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}
						$field_name = 'drug_name';//Medication: //Drug:
						$elements = explode("\t",$field_val);						
						$field_val = isset($elements[0]) ? $elements[0]: '';
						//$field_val = trim(str_ireplace('Medication:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}						
		}//end if rpt_lines
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	return $responseArray;
}

function cartiCenterHandler($fileName='',$reportContents='', $configurationName = '',$email_addr=''){
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Hospital';//Medical Center/UAMS Hospital
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		/*print "<pre>";
		print_r($rpt_lines);
		print "</pre>";*/
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			if(stripos($reportContents,'Confirmation of Scheduled Appointment')!==false){
				$faxType = 'Confirmation of Scheduled Appointment';
				$faxTypeMain = 'Confirmation of Appointment';				
				foreach($rpt_lines as $key => $line){					
					//Patient:
					if(stripos($line,'Patient:') !== false && stripos($line,'Note Date:') !== false){
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';//Hamid Kameli
						$field_val = trim(str_ireplace('Patient:','',$field_val));
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$firstName = isset($pat_name[0]) ? trim($pat_name[0]) : ''; 							
							if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
								$firstNameArr = explode(' ',$firstName);
								$firstName = $firstNameArr[0];
							}
							$tempArr = array('key' => 'first_name', 'value' => $firstName);
							//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'Thank you for referring') !== false){						
						$nextKey = $key+1;//Name
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}
						////////////////////////////////
						$nextKey = $key+2;//DOB
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}
						$field_name = 'dob';													
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}
			else if(stripos($reportContents,'Med Onc Initial Visit Extended')!==false){
				$faxType = 'Med Onc Initial Visit Extended';
				$faxTypeMain = 'Progress Note';				
				foreach($rpt_lines as $key => $line){					
					//Patient:
					if(stripos($line,'Patient:') !== false && stripos($line,'Note Date:') !== false){
						$field_name = 'note_date';
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';
						$field_val = trim(str_ireplace('Note Date:','',$field_val));	
						$tempArr = array('key' => $field_name, 'value' => $field_val);						
						array_push($responseArray['rpt_header'], $tempArr);
					}
					else if(stripos($line,'Patient Name:') !== false){
						//$elements = explode("\t",$line);
						//$field_val = isset($elements[0]) ? $elements[0]: '';//Hamid Kameli
						$field_val = trim(str_ireplace('Patient Name:','',$line));
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$firstName = isset($pat_name[0]) ? trim($pat_name[0]) : ''; 							
							if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
								$firstNameArr = explode(' ',$firstName);
								$firstName = $firstNameArr[0];
							}
							$tempArr = array('key' => 'first_name', 'value' => $firstName);
							//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'DOB:') !== false){						
						$field_val = trim(str_ireplace('DOB:','',$line));
						$field_name = 'dob';													
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}
					else if(stripos($line,'MRN:') !== false){						
						$field_val = trim(str_ireplace('MRN:','',$line));
						$field_name = 'MRN';													
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}
					else if(stripos($line,'Provider:') !== false){						
						$field_val = trim(str_ireplace('Provider:','',$line));
						$field_name = 'provider';													
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}
					else if(stripos($line,'Date of Service:') !== false){						
						$field_val = trim(str_ireplace('Date of Service:','',$line));
						$field_name = 'dos';													
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}
			else if(stripos($reportContents,'Med Onc Initial Visit')!==false){
				$faxType = 'Med Onc Initial Visit';
				$faxTypeMain = 'Progress Note';				
				foreach($rpt_lines as $key => $line){					
					//Patient:
					if(stripos($line,'Patient:') !== false && stripos($line,'Note Date:') !== false){
						$field_name = 'note_date';
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';
						$field_val = trim(str_ireplace('Note Date:','',$field_val));	
						$tempArr = array('key' => $field_name, 'value' => $field_val);						
						array_push($responseArray['rpt_header'], $tempArr);
					}
					else if(stripos($line,'Patient Name:') !== false){
						//$elements = explode("\t",$line);
						//$field_val = isset($elements[0]) ? $elements[0]: '';//Hamid Kameli
						$field_val = trim(str_ireplace('Patient Name:','',$line));
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$firstName = isset($pat_name[0]) ? trim($pat_name[0]) : ''; 							
							if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
								$firstNameArr = explode(' ',$firstName);
								$firstName = $firstNameArr[0];
							}
							$tempArr = array('key' => 'first_name', 'value' => $firstName);
							//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'DOB:') !== false){						
						$field_val = trim(str_ireplace('DOB:','',$line));
						$field_name = 'dob';													
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}
					else if(stripos($line,'MRN:') !== false){						
						$field_val = trim(str_ireplace('MRN:','',$line));
						$field_name = 'MRN';													
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}
					else if(stripos($line,'Provider:') !== false){						
						$field_val = trim(str_ireplace('Provider:','',$line));
						$field_name = 'provider';													
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}
					else if(stripos($line,'Date of Service:') !== false){						
						$field_val = trim(str_ireplace('Date of Service:','',$line));
						$field_name = 'dos';													
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}
			else if(stripos($reportContents,'Med Onc Follow Up')!==false){
				$faxType = 'Med Onc Follow Up';
				$faxTypeMain = 'Progress Note';				
				foreach($rpt_lines as $key => $line){					
					//Patient:
					if(stripos($line,'Patient Name:') !== false){
						//$elements = explode("\t",$line);
						//$field_val = isset($elements[0]) ? $elements[0]: '';//Hamid Kameli
						$field_val = trim(str_ireplace('Patient Name:','',$line));
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$firstName = isset($pat_name[0]) ? trim($pat_name[0]) : ''; 							
							if(!empty($firstName) && count(explode(' ',$firstName)) > 1){
								$firstNameArr = explode(' ',$firstName);
								$firstName = $firstNameArr[0];
							}
							$tempArr = array('key' => 'first_name', 'value' => $firstName);
							//$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'DOB:') !== false){						
						$field_val = trim(str_ireplace('DOB:','',$line));
						$field_name = 'dob';													
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}								
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	return $responseArray;	
}

function medicineManPharmacyHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	$tempArr = array('testName' => 'pharmacy_name', 'value' => 'Medicine Man Pharmacy','flag'=>'','Reference'=>'');						
	array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		/*print "<pre>";
		print_r($rpt_lines);
		print "</pre>";*/
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			//Prescription Refill Request for:
			//if(in_array('REQUEST FOR A REFILL OR NEW PRESCRIPTION',$rpt_lines))
			//Multiple requests in single fax????
			if(stripos($reportContents,'REQUEST FOR FILL AUTHORIZATION')!== false)
			{
				$faxType = 'REQUEST FOR FILL AUTHORIZATION';
				$faxTypeMain = 'Refill Request';				
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'REQUEST FOR FILL AUTHORIZATION') !== false)
					{
						$field_name = 'request_date';//Date:   Requested Date:  Fax Date: 08/04/2019
						$nextKey = $key+1;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}
						$elements = explode("\t",$field_val);
						$field_val = isset($elements[1]) ? $elements[1]: '';	
						//$field_val = trim(str_ireplace('Fax Date:','',$field_val));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}//Rx: 4007970					
					else if(stripos($line,'Rx:') !== false){
						$field_name = 'rx_number';//Rx Number:						
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Rx:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					else if(stripos($line,'Patient:') !== false || stripos($line,'Patient.') !== false){
						$field_name = 'Patient:';//Name:  (Lastname, FirstName) 
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';//JOHNSON, TINA						
						$field_val = trim(str_ireplace('Patient.','',$field_val));
						$field_val = trim(str_ireplace('Patient:','',$field_val));
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(',',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'DOB:') !== false){						
						$field_name = 'dob';													
						$field_val = trim(str_ireplace('DOB:','',$line));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}	
					else if(stripos($line,'Physician:') !== false){
						$field_name = 'prescriber_name';//PRESCRIBER:												
						$field_val = trim(str_ireplace('Physician:','',$line));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
							
					}										
					else if(stripos($line,'Drug:') !== false)
					{
						$field_name = 'drug_name';//Medication: //Drug:							
						$field_val = trim(str_ireplace('Drug:','',$line));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}					
					else if(stripos($line,'Directions:') !== false){
						$field_name = 'sig';//SIG:						
						$field_val = trim(str_ireplace('Directions:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);							
					}					
					else if(stripos($line,'Last Fill:') !== false){
						$field_name = 'date_last_filled';//Rx Number:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						//$field_val = trim(str_ireplace('Rx:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					else if(stripos($line,'Prev Fill:') !== false){
						$field_name = 'date_prev_filled';//Rx Number:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						//$field_val = trim(str_ireplace('Rx:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}// \nOrig Date: 12/20/2018 Quantity: 180 Cap\n
					else if(stripos($line,'Orig Date:') !== false && stripos($line,'Quantity:') !== false)
					{
						$field_name = 'date_written';
						$qtyPos = stripos($line,'Quantity:');
						$qtyStr = substr($line,$qtyPos);//to the end of line
						$field_val = substr($line,0,($qtyPos-1));
						$field_val = trim(str_ireplace('Orig Date:','',$field_val));
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						$field_name = 'qty_prescribed';
						$field_name = 'qty_dispensed';//Qty. Prescribed:  //Prescribed Qty:
					}									
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}									
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	return $responseArray;	
}

//rheaDrugHandler
function rheaDrugHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	$tempArr = array('testName' => 'pharmacy_name', 'value' => 'RHEA DRUG','flag'=>'','Reference'=>'');						
	array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		/*print "<pre>";
		print_r($rpt_lines);
		print "</pre>";*/
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			//Prescription Refill Request for:
			//if(in_array('REQUEST FOR A REFILL OR NEW PRESCRIPTION',$rpt_lines))
			//Multiple requests in single fax????
			if(stripos($reportContents,'REFILL AUTHORIZATION REQUEST FORM')!== false)
			{
				$faxType = 'REFILL AUTHORIZATION REQUEST FORM';
				$faxTypeMain = 'Refill Request';				
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'REFILL REQUEST FOR RX:') !== false){
						$field_name = 'rx_number';//Rx Number:						
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';
						//$field_val = trim(str_ireplace('Rx:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					else if(stripos($line,'PATIENT:') !== false && stripos($line,'DOB:') !== false){
						$field_name = 'Patient:';//Name:  (FirstName mid Lastname) CAROL A RHOADS
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';//CAROL A RHOADS						
						$field_val = trim(str_ireplace('PATIENT.','',$field_val));
						$field_val = trim(str_ireplace('PATIENT:','',$field_val));
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$lname = '';
							if(count($pat_name) > 1){ //Name:  (FirstName mid Lastname) LILLIAN CASEY ORR
								$lname = isset($pat_name[2]) ? $pat_name[2]: $pat_name[1];
							}
							$tempArr = array('key' => 'last_name', 'value' => $lname);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						/////////////////////////////
						$field_name = 'dob';	
						$field_val = isset($elements[1]) ? $elements[1]: '';												
						$field_val = trim(str_ireplace('DOB:','',$field_val));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
					}					
					else if(stripos($line,'DOCTOR:') !== false){
						$field_name = 'prescriber_name';//PRESCRIBER:
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';												
						$field_val = trim(str_ireplace('DOCTOR:','',$field_val));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
							
					}										
					else if(stripos($line,'PRESCRIBED DRUG:') !== false)
					{
						$field_name = 'drug_name';//Medication: //Drug:	
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';						
						$field_val = trim(str_ireplace('PRESCRIBED DRUG:','',$line));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}					
					else if(stripos($line,'SIG:') !== false){
						$field_name = 'sig';//SIG:	
						//$elements = explode("\t",$line);
						$posQty = stripos($line,'QTY:');
						$strSig = substr($line,0,($posQty-1));
						$strQty = substr($line,$posQty);	//end of line				
						$field_val = trim(str_ireplace('SIG:','',$strSig));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						//////////////////////
						$field_name = 'qty_prescribed';//Qty. Prescribed:  //Prescribed Qty:						
						$field_val = trim(str_ireplace('QTY:','',$strQty));							
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
													
					}					
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}
			else if(stripos($reportContents,'Prescription Claim Rejection Report')!== false)
			{
				$faxType = 'Prescription Claim Rejection Report';
				$faxTypeMain = 'PA Request';				
				foreach($rpt_lines as $key => $line){
					
					/*if(stripos($line,'Fax Date:') !== false)
					{
						$field_name = 'request_date';//Date:   Requested Date:  Fax Date: 08/04/2019
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';	
						$field_val = trim(str_ireplace('Fax Date:','',$field_val));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}	//Store # 17611					
					else */
					if((stripos($line,'Patient') !== false || stripos($line,'Patlent') !== false) && stripos($line,'Prescriber') !== false){
						$field_name = 'Patient:';//Name:  (FirstName Lastname) 						
						//$field_val = trim(str_ireplace('Name:','',$item));
						$nextKey = $key+1;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}
												
						$elements = explode("\t",$field_val);
						/////////////////////////////////////////////
						$field_name = 'prescriber_name';//PRESCRIBER:						
						$field_val = isset($elements[1]) ? $elements[1]: '';												
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						/////////////////////////////////////////////////
						$field_val = isset($elements[0]) ? $elements[0]: '';//KRISTEN MIRONTSCHUK  (FirstName Lastname)						
						//$field_val = trim(str_ireplace('Patient:','',$field_val));
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}						

					}
					else if(stripos($line,'DOB:') !== false){						
						$field_name = 'dob';
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';
						/*$field_val = '';
						$dobPos = stripos($line,'DOB:');
						if($dobPos !== false){
							$field_val = substr($line,$dobPos);//to the end of line
						}							
						$field_val = trim(str_ireplace('DOB:','',$field_val));*/					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
					}	
					else if(stripos($line,'Written') !== false)
					{
						$field_name = 'drug_name';//Medication: //Drug:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';	
						//$field_val = trim(str_ireplace('Drug Dispensed:','',$field_val));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						$field_name = 'ndc';//Medication: //Drug:
						$field_val = isset($elements[1]) ? $elements[1]: '';	
						$field_val = trim(str_ireplace('NDC:','',$field_val));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}
					else if(stripos($line,'Dispensed') !== false)
					{
						$field_name = 'drug_name';//Medication: //Drug:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';	
						$field_val = trim(str_ireplace('Dispensing:','',$field_val));					
						
						//$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						//array_push($responseArray['rpt_detail'], $tempArr);
					}
					else if(stripos($line,'NDC:') !== false)
					{
						$field_name = 'ndc';//Medication: //Drug:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';	
						//$field_val = trim(str_ireplace('Drug Dispensed:','',$field_val));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
					}
					else if(stripos($line,'Qty Disp:') !== false)
					{
						/*$field_name = 'qty_prescribed';//Qty. Prescribed:  //Prescribed Qty:
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Original Quantity Ordered:','',$field_val));							
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	*/
						
						$field_name = 'qty_dispensed';//Qty. Prescribed:  //Prescribed Qty:						
						//$field_val = isset($elements[1]) ? $elements[1]: '';
						$field_val = trim(str_ireplace('Qty Disp:','',$line));							
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);					
					}//Original Quantity Ordered: 90	Dispensed Quantity: 90  					
					else if(stripos($line,'Instr:') !== false){
						$field_name = 'sig';//SIG:						
						$field_val = trim(str_ireplace('Instr:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);							
					}
					else if(stripos($line,'Rx #:') !== false){
						$field_name = 'rx_number';//Rx Number:
						//$elements = explode("\t",$line);
						//$field_val = isset($elements[0]) ? $elements[0]: '';
						$field_val = trim(str_ireplace('Rx #:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
						
					}					
																			
					/*else if(stripos($line,'Drug Prescribed:') !== false){
						$field_name = 'drug_name';//Medication: //Drug:						
						$field_val = trim(str_ireplace('Drug Prescribed:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}*/
					
					
								
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	return $responseArray;	
}

function freidericaPharmacyHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	$tempArr = array('testName' => 'pharmacy_name', 'value' => 'Freiderica Pharmacy','flag'=>'','Reference'=>'');						
	array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		/*print "<pre>";
		print_r($rpt_lines);
		print "</pre>";*/
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			//Prescription Refill Request for:
			//if(in_array('REQUEST FOR A REFILL OR NEW PRESCRIPTION',$rpt_lines))
			//Multiple requests in single fax????
			if(stripos($reportContents,'REQUEST FOR FILL AUTHORIZATION')!== false)
			{
				$faxType = 'REQUEST FOR FILL AUTHORIZATION';
				$faxTypeMain = 'Refill Request';				
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'REQUEST FOR FILL AUTHORIZATION') !== false)
					{
						$field_name = 'request_date';//Date:   Requested Date:  Fax Date: 08/04/2019
						$nextKey = $key+1;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}
						$elements = explode("\t",$field_val);
						$field_val = isset($elements[1]) ? $elements[1]: '';	
						//$field_val = trim(str_ireplace('Fax Date:','',$field_val));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}//Rx: 4007970					
					else if(stripos($line,'Rx:') !== false){
						$field_name = 'rx_number';//Rx Number:						
						$field_val = trim(str_ireplace('Rx:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					else if(stripos($line,'Patient:') !== false){
						$field_name = 'Patient:';//Name:  (Lastname, FirstName) 
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';//JOHNSON, TINA						
						$field_val = trim(str_ireplace('Patient:','',$field_val));
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(',',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'DOB:') !== false){						
						$field_name = 'dob';													
						$field_val = trim(str_ireplace('DOB:','',$line));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						
					}	
					else if(stripos($line,'Physician:') !== false){
						$field_name = 'prescriber_name';//PRESCRIBER:												
						$field_val = trim(str_ireplace('Physician:','',$line));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
							
					}										
					else if(stripos($line,'Drug:') !== false)
					{
						$field_name = 'drug_name';//Medication: //Drug:							
						$field_val = trim(str_ireplace('Drug:','',$line));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}					
					else if(stripos($line,'Directions:') !== false){
						$field_name = 'sig';//SIG:						
						$field_val = trim(str_ireplace('Directions:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);							
					}					
					else if(stripos($line,'Last Fill:') !== false){
						$field_name = 'date_last_filled';//Rx Number:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						//$field_val = trim(str_ireplace('Rx:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					else if(stripos($line,'Prev Fill:') !== false){
						$field_name = 'date_prev_filled';//Rx Number:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						//$field_val = trim(str_ireplace('Rx:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}// \nOrig Date: 12/20/2018 Quantity: 180 Cap\n
					else if(stripos($line,'Orig Date:') !== false && stripos($line,'Quantity:') !== false)
					{
						$field_name = 'date_written';
						$qtyPos = stripos($line,'Quantity:');
						$qtyStr = substr($line,$qtyPos);//to the end of line
						$field_val = substr($line,0,($qtyPos-1));
						$field_val = trim(str_ireplace('Orig Date:','',$field_val));
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
						
						$field_name = 'qty_prescribed';
						$field_name = 'qty_dispensed';//Qty. Prescribed:  //Prescribed Qty:
					}									
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}									
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	return $responseArray;	
}

function theDrugStoreHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	$tempArr = array('testName' => 'pharmacy_name', 'value' => 'THE DRUG STORE, INC','flag'=>'','Reference'=>'');						
	array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		/*print "<pre>";
		print_r($rpt_lines);
		print "</pre>";*/
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			
			//if(stripos($reportContents,'Refill Authorization Request')!== false) //OCR Not reading this line
			{
				$faxType = 'Refill Authorization Request';
				$faxTypeMain = 'Refill Request';				
				foreach($rpt_lines as $key => $line){
					
					/*if(stripos($line,'REQUEST FOR FILL AUTHORIZATION') !== false)
					{
						$field_name = 'request_date';//Date:   Requested Date:  Fax Date: 08/04/2019
						$nextKey = $key+1;
						$field_val = '';
						if (array_key_exists($nextKey,$rpt_lines)){
							$field_val = trim($rpt_lines[$nextKey]);
						}
						$elements = explode("\t",$field_val);
						$field_val = isset($elements[1]) ? $elements[1]: '';	
						//$field_val = trim(str_ireplace('Fax Date:','',$field_val));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
					}				
					else*/ 
					if(stripos($line,'Prescription#:') !== false){
						$field_name = 'rx_number';//Rx Number:		
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';				
						//$field_val = trim(str_ireplace('Rx:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					else if(stripos($line,'Patient:') !== false && stripos($line,'Date of Birth:') !== false){
						$field_name = 'Patient:';//Name:  (Lastname, FirstName) 
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';//RUSSELL, MARY						
						//$field_val = trim(str_ireplace('Patient:','',$field_val));
						//$tempArr = array('key' => $field_name, 'value' => $field_val);
						//array_push($responseArray['rpt_header'], $tempArr);
						
						$pat_name = explode(',',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						///////////////////////////////
						$field_name = 'dob';
						$field_val = isset($elements[3]) ? $elements[3]: '';													
						//$field_val = trim(str_ireplace('Date of Birth:','',$line));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
					}					
					else if(stripos($line,'Date Written:') !== false){
						$field_name = 'date_written';
						$elements = explode("\t",$line);
						$field_val = isset($elements[2]) ? $elements[2]: '';						
						//$field_val = trim(str_ireplace('Date Written:','',$field_val));
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					else if(stripos($line,'Last Dispensed:') !== false){
						$field_name = 'date_last_filled';//Rx Number:
						//$elements = explode("\t",$line);
						//$field_val = isset($elements[1]) ? $elements[1]: '';						
						$field_val = trim(str_ireplace('Last Dispensed:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					else if(stripos($line,'Physician:') !== false){
						$field_name = 'prescriber_name';//PRESCRIBER:												
						$field_val = trim(str_ireplace('Physician:','',$line));
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
							
					}										
					else if(stripos($line,'Product:') !== false)
					{
						$field_name = 'drug_name';//Medication: //Drug:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';								
						//$field_val = trim(str_ireplace('Product:','',$line));					
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}					
					else if(stripos($line,'Directions:') !== false){
						$field_name = 'sig';//SIG:	
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';					
						//$field_val = trim(str_ireplace('Directions:','',$line));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);							
					}	
					else if(stripos($line,'Quantity:') !== false)
					{
						$field_name = 'qty_prescribed';//Qty. Prescribed:  //Prescribed Qty:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						//$field_val = trim(str_ireplace('Quantity:','',$strQty));							
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}									
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
			}									
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	return $responseArray;	
}

function donsPharmacyHandler($fileName='',$reportContents='', $configurationName = '',$email_addr='')
{
	echo "<h2>DON'S</h2><br>";
	$responseArray = array('rpt_header' => array(), 'rpt_detail' => array());
	//$line_arr = array("testName" => "", "value" => "", "flag" => "", "Reference" => "");
	//$flagsArr = array('LOW', 'NORMAL', 'HIGH');
	$testName = '';
		
	array_push($responseArray['rpt_header'], array('key' => "Configuration Name", 'value' => $configurationName));
	//$tempArr = array('key' => 'TYPE:', 'value' => 'Pharmacy');
	//array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('testName' => 'pharmacy_name', 'value' => "DON'S PHARMACY",'flag'=>'','Reference'=>'');						
	array_push($responseArray['rpt_detail'], $tempArr);
	
	$responseArray['configurationName'] = $configurationName;
	$responseArray['pdf_name'] = $fileName;
	$responseArray['email_addr'] = $email_addr;	
	
	$original_file_name = pathinfo($fileName, PATHINFO_FILENAME);
	$tempArr = explode('_',$original_file_name);
	print "<pre>";print_r($tempArr);print "</pre>";
	$fax_date_time = date('Y-m-d H:i:s');
	$fax_data_id = 0;
	if(!empty($tempArr)){
		//$fax_date_time =substr($tempArr[1],0,4).'-'.substr($tempArr[1],4,2).'-'.substr($tempArr[1],6,2).' '.date('H:i:s',strtotime($tempArr[2]));
		$fax_date_time =date('Y-m-d H:i:s',$tempArr[2]); //date('H:i:s',strtotime($tempArr[2]));//date('Y-m-d H:i:s','1571654854');
		$fax_data_id = $tempArr[3];
	}
	$responseArray['fax_date_time'] = $fax_date_time;
	$responseArray['fax_data_id'] = $fax_data_id;
	
	array_push($responseArray['rpt_header'], array('key' => "text_contents", 'value' => $reportContents));
	
	$reportStart = 0;
	$headerStart = 0;
	$bodyStart = 0;
	$faxCategory = 'Pharmacy';
	$faxType = '';
	if(!empty($reportContents)){
		//$reportContents = trim(preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', $reportContents)); //Some time It removes sapces in between
		$rpt_lines = explode("\n",$reportContents);
		print "<pre>";
		print_r($rpt_lines);
		print "</pre>";
		if(!empty($rpt_lines)){
			$reportStart = 1;
			$headerStart = 1;
			
			/*foreach($rpt_lines as $key => $line){
				
				echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
				$elements = explode("\t",$line);
				
				print "<pre>";
				print_r($elements);
				print "</pre>";
			}*/
			
			//if(in_array('Request Refill Authorization From',$rpt_lines))
			if(stripos($reportContents,'Request Refill Authorization From:') !== false)
			{
				$faxType = 'Request Refill Authorization From';
				$faxTypeMain = 'Refill Request';
				foreach($rpt_lines as $key => $line){
					
					if(stripos($line,'Date:') !== false && stripos($line,'Medication:') !== false){
						$field_name = 'request_date';//Date:   Requested Date:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: ''; //mm/dd/yyyy	
						//$field_val = trim(str_ireplace('Date:','',$field_val));					
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);	
						
						$field_name = 'drug_name';//Medication: //Drug:						
						$field_val = isset($elements[2]) ? $elements[2]: '';
						$field_val = trim(str_ireplace('Medication:','',$field_val));						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
											
					}
					else if(stripos($line,'Qty Written:') !== false){
						$field_name = 'qty_prescribed';//Qty. Prescribed:  //Prescribed Qty:
						$elements = explode("\t",$line);
						$field_val = '';
						foreach($elements as $item){
							if(stripos($item,'Qty Written:') !== false){
								$field_val = trim(str_ireplace('Qty Written:','',$item));
							}
						}
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);
					}					
					else if(stripos($line,'Rx:') !== false && (stripos($line,'Date Wile:') !== false || stripos($line,'Date Written:') !== false) && stripos($line,'Last Filled:') !== false){
						$elements = explode("\t",$line);
						foreach($elements as $item){
							if(stripos($item,'Rx:') !== false){
								$field_name = 'rx_number';//Rx Number:
								$field_val = trim(str_ireplace('Rx:','',$item));						
								$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
								array_push($responseArray['rpt_detail'], $tempArr);
							}
							else if(stripos($item,'Date Wile:') !== false || stripos($item,'Date Written:') !== false){
								$field_name = 'date_written';//Date Written:
								$field_val = trim(str_ireplace('Date Wile:','',$item));	
								$field_val = trim(str_ireplace('Date Written:','',$field_val));					
								$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
								array_push($responseArray['rpt_detail'], $tempArr);	
							}
							
						}
					}
					else if(stripos($line,'Last Filled:') !== false && stripos($line,'Directions:') !== false){
						$elements = explode("\t",$line);
						$field_name = 'date_last_filled';//Date Last Filled:   //Last Filled:
						$field_val = '';
						if(isset($elements[1])){																					
							$field_val = trim($elements[1]);													
						}
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
					}
					//Dispensed 6\ttime(s) for a total Qty of 180.000
					else if(stripos($line,'Dispensed') !== false && stripos($line,'total Qty') !== false){
						$elements = explode("\t",$line);
						$field_name = 'sig';//SIG:
						$field_val = isset($elements[2]) ? $elements[2]: '';						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);	
					}
					else if(stripos($line,'Refills Originally Authorized:') !== false){
						$field_name = 'refills';//Prescribed Refills:						
						$field_val = trim(str_ireplace('Refills Originally Authorized:','',$line));												
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);						
					}
					/*else if(stripos($line,'Plus') !== false && stripos($line,'Refills') !== false && stripos($line,'Date:') !== false){
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';//Fname Lname
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					else if(stripos($line,'Change Directions:') !== false){
						$elements = explode("\t",$line);
						$field_val = isset($elements[0]) ? $elements[0]: '';//Fname Lname
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => isset($pat_name[1]) ? trim($pat_name[1]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}*/
					else if(stripos($line,'DOB:') !== false && stripos($line,'Phone:') !== false && stripos($line,'Phone:') > stripos($line,'DOB:')){
						//DOB: 10/07/1955 Phone: (501) 943-2165
						$posPh = stripos($line,'Phone:');
						$phNumber = substr($line,$posPh);//to the end of line
						$dobStr = '';
						if($posPh > 0){
							$dobStr = substr($line,0,$posPh-1);
						}
						$field_name = 'dob';
						$field_val = '';
						if(!empty($dobStr)){
							$field_val = trim(str_ireplace('DOB:','',$dobStr));	
						}												
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);
						///////////////////////////////////////						
						$prevKey = $key - 2;
						$field_val = isset($rpt_lines[$prevKey]) ? $rpt_lines[$prevKey] : '';
						$elements = explode("\t",$field_val); //KIMBERLY THROGMORTON
						$field_val = isset($elements[0]) ? $elements[0]: '';//Fname Lname
						$pat_name = explode(' ',$field_val);
						if(!empty($pat_name) && count($pat_name) > 0){
							$tempArr = array('key' => 'first_name', 'value' => isset($pat_name[0]) ? trim($pat_name[0]) : '');
							array_push($responseArray['rpt_header'], $tempArr);
							
							$lname = '';
							if(count($pat_name) > 1){
								$lname = isset($pat_name[2]) ? $pat_name[2]: $pat_name[1];
							}
							$tempArr = array('key' => 'last_name', 'value' => $lname);
							array_push($responseArray['rpt_header'], $tempArr);
						}
						else{
							$tempArr = array('key' => 'first_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
							$tempArr = array('key' => 'last_name', 'value' => '');
							array_push($responseArray['rpt_header'], $tempArr);
						}
					}
					/*
					else if(stripos($line,'PRESCRIBER:') !== false || stripos($line,'PRESCRIBER :') !== false){
						$field_name = 'PRESCRIBER:';
					}
					else if(stripos($line,'Name:') !== false && stripos($line,'From:') !== false){
						$field_name = 'prescriber_name';//PRESCRIBER:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						$tempArr = array('key' => $field_name, 'value' => $field_val);
						array_push($responseArray['rpt_header'], $tempArr);						
							
					} //Store # 17611
					else if(stripos($line,'FOR PATIENT:') !== false || stripos($line,'FOR PATIENT :') !== false){
						$field_name = 'FOR PATIENT:';
					}														
					else if(stripos($line,'Pharmacy Comments:') !== false){
						$field_name = 'pharmacy_comments';//Pharmacy Comments:
						$elements = explode("\t",$line);
						$field_val = isset($elements[1]) ? $elements[1]: '';						
						$tempArr = array('testName' => $field_name, 'value' => $field_val,'flag'=>'','Reference'=>'');						
						array_push($responseArray['rpt_detail'], $tempArr);							
					}*/
					
					echo "[$key]=>&nbsp;&nbsp;".$line.'<br>';
					$elements = explode("\t",$line);
					
					print "<pre>";
					print_r($elements);
					print "</pre>";
					
				}//end foreach $rpt_lines
				
			}
			else if(in_array('Prior Authorization Request',$rpt_lines)){
				$faxType = 'Prior Authorization Request';
				$faxTypeMain = 'PA Request';
			}			
		}
	}
	
	$responseArray['fax_category'] = $faxCategory;
	$responseArray['fax_type'] = $faxTypeMain;//Refill Request, 90 DAYS SUPPLY,Prior Authorization
	
	$tempArr = array('key' => 'fax_category:', 'value' => $faxCategory);
	array_push($responseArray['rpt_header'], $tempArr);
	
	$tempArr = array('key' => 'TYPE:', 'value' => $faxType);
	array_push($responseArray['rpt_header'], $tempArr);
	/*
	$line_arr = array();
	//$line_arr["rpt_contents"] = $reportContents;
	$line_arr["testName"] = 'text:';
	$line_arr["value"] = $reportContents;
	$line_arr["flag"] = '';
	$line_arr["Reference"] = '';					
	//$line_arr["site"] = "";
	array_push($responseArray['rpt_detail'], $line_arr);*/
	return $responseArray;
}

function array_search_partial($arr, $keyword) {
	foreach($arr as $index => $string) {
		if (stripos($string, $keyword) !== FALSE)
			return $index;
	}
}
?>