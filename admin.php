<?php

	// reset display message
	unset($displayMsg);

	if (!empty($_POST)) {
	
		require 'settings.php';

		// basic password auth, unencrypted
		// wpa2 on wireless will keep all of this secured and only 
		// authorized users should have physical access to the non-routed network
		if ( isset($_POST['password']) & $_POST['password'] == $password  ) {	
		
			if (isset($_POST['import'])) {
			
				// the upload section
				// handle the file to the server
				try {
			
					// undefined | multiple files | $_FILES corruption attack
					// if this request falls under any of them, treat it invalid.
					if (
						!isset($_FILES['import-file']['error']) ||
						is_array($_FILES['import-file']['error'])
					) {
						// throw new RuntimeException('Invalid parameters.');
						$displayMsg = 'Failed. Invalid parameters on file upload.';
					}

					// check $_FILES['import-file']['error'] value
					switch ($_FILES['import-file']['error']) {
						case UPLOAD_ERR_OK:
							break;
						case UPLOAD_ERR_NO_FILE:
							$displayMsg = 'Failed. No file sent on import.';
							throw new RuntimeException();
						case UPLOAD_ERR_INI_SIZE:
						case UPLOAD_ERR_FORM_SIZE:
							$displayMsg = 'Failed. Exceeded filesize limit on upload.';
							throw new RuntimeException();		
						default:
							$displayMsg = 'Failed. Unknown errors during file upload.';
							throw new RuntimeException();
					}

					// check filesize
					if ($_FILES['import-file']['size'] > 1000000) {
						$displayMsg = 'Failed. Exceeded filesize limit during upload.';
						throw new RuntimeException();
					}

					// check MIME type
					$finfo = new finfo(FILEINFO_MIME_TYPE);
					if (false === $ext = array_search(
						$finfo->file($_FILES['import-file']['tmp_name']),
						array(
							'xls' => 'application/vnd.ms-excel',
							'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
						),
						true
					)) {
						$displayMsg = 'Failed. Invalid file format on upload.';
						throw new RuntimeException();
					}

					// name it uniquely, obtain safe unique name from its binary data
					$uniqueUpload = sprintf($tmpdir . '%s.%s', sha1_file($_FILES['import-file']['tmp_name']), $ext);
					
					if (!move_uploaded_file($_FILES['import-file']['tmp_name'],$uniqueUpload)) {
						$displayMsg = 'Failed. Invalid file format on upload.';
						throw new RuntimeException();
					}
					
					$uploadSuccessful = true;			

				} catch (RuntimeException $e) {

					echo $e->getMessage();

				}
				
				// if upload is successful
				if ( isset($uploadSuccessful) ) {
				
					require 'includes/PHPExcel.php';
				
					// create PHPExcel readonly object
					$objReader = PHPExcel_IOFactory::createReader('Excel2007');
					$objReader->setReadDataOnly(true);
					
					// load file
					$objPHPExcel = $objReader->load($uniqueUpload);
					
					// set active sheet, 0 = first sheet
					$objPHPExcel->setActiveSheetIndex(0); 
					
					// field range to grab
					$focusRange = 'A1:E' . $objPHPExcel->getActiveSheet()->getHighestRow();
					
					// get sheet data, sheet 0 are preregistered users
					// see documentation http://bit.ly/1qKWJnF
					$sheetData = $objPHPExcel->getActiveSheet()->rangeToArray($focusRange,null,false,false,true);
					
					// put SQLite3 connection in a try/catch
					try { 	
										
						// open db connection
						$db  = new SQLite3($dbfile); 	
						
						// do a large insert, performance is better on one large 
						// insert than individual on rasberry pi
						// see http://bit.ly/1rmtYNx and http://bit.ly/1DrbGj5
						
						if ( count($sheetData) > 0 ) {
							$db->exec('BEGIN;');
							
							// go through each row in excel spreadsheet
							foreach ( $sheetData as $row ) {
		
								// grab data from cells
								$lastname = $row['A'];
								$firstname = $row['B'];
								$company = $row['C'];
								
								// create insert statement
								$sql = $db->prepare('INSERT INTO preregistered (lastname,firstname,company) VALUES (:lastname,:firstname,:company)');
								$sql -> bindValue(':lastname',$lastname,SQLITE3_TEXT);
								$sql -> bindValue(':firstname',$firstname,SQLITE3_TEXT);
								$sql -> bindValue(':company',$company,SQLITE3_TEXT);

								// execute insert statement				
								$response = $sql->execute();
								
							}
							
							$db->exec('COMMIT;');
								
						}	
						
						$db->close();
						
						$displayMsg = 'Successfully inserted records into the preregistered table!';	
						
					} catch (Exception $e) { 				
						$displayMsg = 'Failed. Unable to insert records into the preregistered table.';				
					}
					
					// ensure we are disconnected from any worksheets
					$objPHPExcel->disconnectWorksheets();
					
					// unlink from memory for garbage collection
					unset($objPHPExcel);	
					unset($objReader);		
										
				}
			
			} elseif (isset($_POST['export'])) {
			
				require 'includes/PHPExcel.php';
				
				try {
				
					// create phpexcel object
					$objPHPExcel = new PHPExcel();
					
					// create 2 additional sheets for a total of 3 sheets
					$objPHPExcel->createSheet(1);
					$objPHPExcel->createSheet(2);
					
					// open up the database
					$db  = new SQLite3($dbfile);
					
					//
					// preregistered table logic
					//
					
					// query table from db
					$preregistered = $db->query('SELECT * FROM preregistered;');
					
					// set sheet 0 active, rename sheet
					$objPHPExcel->setActiveSheetIndex(0);
					$objPHPExcel->getActiveSheet()->setTitle('Preregistered');
					
					// populate header
					$objPHPExcel->getActiveSheet()->SetCellValue('A1', 'LAST NAME');
					$objPHPExcel->getActiveSheet()->SetCellValue('B1', 'FIRST NAME');
					$objPHPExcel->getActiveSheet()->SetCellValue('C1', 'COMPANY');
					$objPHPExcel->getActiveSheet()->SetCellValue('D1', 'DATETIME CHECKIN (UTC)');
					
					// set boldness
					$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
					$objPHPExcel->getActiveSheet()->getStyle('B1')->getFont()->setBold(true);
					$objPHPExcel->getActiveSheet()->getStyle('C1')->getFont()->setBold(true);
					$objPHPExcel->getActiveSheet()->getStyle('D1')->getFont()->setBold(true);
				
					// iterate through database dumping to excel
					while ($row = $preregistered->fetchArray()) {
						$rownum = $objPHPExcel->getActiveSheet()->getHighestRow() + 1;
						$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rownum, $row['lastname']);
						$objPHPExcel->getActiveSheet()->SetCellValue('B'.$rownum, $row['firstname']);
						$objPHPExcel->getActiveSheet()->SetCellValue('C'.$rownum, $row['company']);
						$objPHPExcel->getActiveSheet()->SetCellValue('D'.$rownum, $row['datetime']);
						
						// set datetime format
						$objPHPExcel->getActiveSheet()->getStyle('D'.$rownum)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_DATETIME);
					}
									
					// autosize columns
					foreach(range('A','D') as $cid) {
						$objPHPExcel->getActiveSheet()->getColumnDimension($cid)->setAutoSize(true);
					}
					
					//
					// walkon table logic
					//

					// query table from db
					$walkons = $db->query('SELECT * FROM walkon;');
					
					// set sheet 1 active, rename
					$objPHPExcel->setActiveSheetIndex(1);
					$objPHPExcel->getActiveSheet()->setTitle('Walkons');
					
					// populate header
					$objPHPExcel->getActiveSheet()->SetCellValue('A1', 'LAST NAME');
					$objPHPExcel->getActiveSheet()->SetCellValue('B1', 'FIRST NAME');
					$objPHPExcel->getActiveSheet()->SetCellValue('C1', 'COMPANY');
					$objPHPExcel->getActiveSheet()->SetCellValue('D1', 'EMAIL ADDRESS');
					$objPHPExcel->getActiveSheet()->SetCellValue('E1', 'DATETIME CHECKIN (UTC)');
					
					// set boldness
					$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
					$objPHPExcel->getActiveSheet()->getStyle('B1')->getFont()->setBold(true);
					$objPHPExcel->getActiveSheet()->getStyle('C1')->getFont()->setBold(true);
					$objPHPExcel->getActiveSheet()->getStyle('D1')->getFont()->setBold(true);
					$objPHPExcel->getActiveSheet()->getStyle('E1')->getFont()->setBold(true);
					
					// iterate through database dumping to excel
					while ($row = $walkons->fetchArray()) {
						$rownum = $objPHPExcel->getActiveSheet()->getHighestRow() + 1;
						$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rownum, $row['lastname']);
						$objPHPExcel->getActiveSheet()->SetCellValue('B'.$rownum, $row['firstname']);
						$objPHPExcel->getActiveSheet()->SetCellValue('C'.$rownum, $row['company']);
						$objPHPExcel->getActiveSheet()->SetCellValue('D'.$rownum, $row['email']);
						$objPHPExcel->getActiveSheet()->SetCellValue('E'.$rownum, $row['datetime']);
						
						// set datetime format
						$objPHPExcel->getActiveSheet()->getStyle('E'.$rownum)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_DATETIME);
					}
					
					// set datetime format
					$objPHPExcel->getActiveSheet()->getStyle('E')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_DATETIME);
					
					// autosize columns
					foreach(range('A','E') as $cid) {
						$objPHPExcel->getActiveSheet()->getColumnDimension($cid)->setAutoSize(true);
					}
					
					//				
					// set and rename sheet for ---KEY---
					//
					
					// set sheet 2 active, rename
					$objPHPExcel->setActiveSheetIndex(2);
					$objPHPExcel->getActiveSheet()->setTitle('---KEY---');

					// add key data, static data
					$objPHPExcel->getActiveSheet()->SetCellValue('A1', 'Preregistered Sheet');
					$objPHPExcel->getActiveSheet()->SetCellValue('A2', 'Column A = LAST NAME');
					$objPHPExcel->getActiveSheet()->SetCellValue('A3', 'Column B = FIRST NAME');
					$objPHPExcel->getActiveSheet()->SetCellValue('A4', 'Column C = COMPANY');
					$objPHPExcel->getActiveSheet()->SetCellValue('A5', 'Column D = DATETIME CHECKIN (UTC)');
					$objPHPExcel->getActiveSheet()->SetCellValue('B1', 'Walk-Ons Sheet');
					$objPHPExcel->getActiveSheet()->SetCellValue('B2', 'Column A = LAST NAME');
					$objPHPExcel->getActiveSheet()->SetCellValue('B3', 'Column B = FIRST NAME');
					$objPHPExcel->getActiveSheet()->SetCellValue('B4', 'Column C = COMPANY');
					$objPHPExcel->getActiveSheet()->SetCellValue('B5', 'Column D = EMAIL ADDRESS');
					$objPHPExcel->getActiveSheet()->SetCellValue('B5', 'Column E = DATETIME CHECKIN (UTC)');
					$objPHPExcel->getActiveSheet()->SetCellValue('C1', 'Credits');
					$objPHPExcel->getActiveSheet()->SetCellValue('C2', 'Author: Patrick Stasko');
					$objPHPExcel->getActiveSheet()->SetCellValue('C3', 'Website: http://lvlnrd.com');
					$objPHPExcel->getActiveSheet()->SetCellValue('C4', 'Instructions: http://bit.ly/');
					$objPHPExcel->getActiveSheet()->SetCellValue('C5', 'Notes: Developed by and for the Cleveland VMUG');
					$objPHPExcel->getActiveSheet()->SetCellValue('C6', 'http://vmug.com/cleveland For use by all!');

					// set boldness
					$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
					$objPHPExcel->getActiveSheet()->getStyle('B1')->getFont()->setBold(true);
					$objPHPExcel->getActiveSheet()->getStyle('C1')->getFont()->setBold(true);
					
					// autosize columns
					foreach(range('A','C') as $cid) {
						$objPHPExcel->getActiveSheet()->getColumnDimension($cid)->setAutoSize(true);
					}
					
					// redirect output to a client web browser (Excel5)
					header('Content-Type: application/vnd.ms-excel');
					header('Content-Disposition: attachment;filename="Checkin-Export.xls"');
					header('Cache-Control: max-age=0');

					// save the file in phpexcel object
					$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
					$objWriter->save('php://output');
					
					// ensure we are disconnected from any worksheets
					$objPHPExcel->disconnectWorksheets();
					
					// unlink from memory for garbage collection
					unset($objPHPExcel);	
					unset($objReader);	
					
					$displayMsg = 'Successfully exported data!';
				
				} catch (Exception $e) {
					$displayMsg = 'Failed. To export data! Check database connection.';
				}
					
			
			} elseif (isset($_POST['optimize'])) {
			
				try {
					$db  = new SQLite3($dbfile);
					$response = $db->exec('VACUUM;');
									
					if ( $response == 1 ) { 
						$displayMsg = 'Optimized the database successfully!';
					} else {
						$displayMsg = 'Failed to optimize the database!';
					}
					
					$db->close();
					
				} catch (Exception $e) {
					$displayMsg = 'Failed to optimize the database!';
				}
				
			} elseif (isset($_POST['truncate-preregistered'])) {
			
				try {
					$db  = new SQLite3($dbfile);
					
					$response = $db->exec('DELETE FROM preregistered;');
					
					if ( $response == 1 ) {
						$displayMsg = 'Truncated preregistered table successfully!';
					} else {
						$displayMsg = 'Failed to truncate the preregistered table!';
					}
					
					$db->close();
					
				} catch (Exception $e)  {
					$displayMsg = 'Failed to truncate the preregistered table!';
				}
				
			} elseif (isset($_POST['truncate-walkon'])) {
			
				try {
					$db  = new SQLite3($dbfile);
					
					$response = $db->exec('DELETE FROM walkon;');
					
					if ( $response == 1 ) {
						$displayMsg = 'Truncated walkon table successfully!';
					} else {
						$displayMsg = 'Failed to truncate the walkon table!';
					}
					
					$db->close();
					
				} catch (Exception $e) {
					$displayMsg = 'Failed to truncate the walkon table!';
				}
			
			} elseif (isset($_POST['truncate-both'])) {
				
				try {
					$db  = new SQLite3($dbfile);
					
					$response = $db->exec('DELETE FROM preregistered; DELETE FROM walkon;');
					
					if ( $response == 1 ) {
						$displayMsg = 'Truncated both tables successfully!';
					} else {
						$displayMsg = 'Failed to truncate both tables!';
					}
					
					$db->close();
					
				} catch (Exception $e) {
					$displayMsg = 'Failed to truncate both tables!';
				}
			} elseif (isset($_POST['time'])) {
				
				try {
					$month = $_POST['month'];
					$day = $_POST['day'];
					$year = $_POST['year'];
					$hour = $_POST['hour'];
					$minute = $_POST['minute'];
					
					// construct datetime string for ubuntu
					$cmd = 'date ' . $month . $day . $hour . $minute . $year . '.00';
					
					// execute shell cmd
					$cmdout = shell_exec($cmd);
					$displayMsg = 'System time set successfully!';
					
				} catch (Exception $e) {
					$displayMsg = 'Failed to set system time!';
				}
			}
			elseif (isset($_POST['test-print'])) {
				
				try {
					// need library to print
					require 'includes/NameTagPrinter.php';

					// create object
					$render = new NameTagPrinter(); 

					// needed for printer
					$render->SetSaveDirectory($labelPath);

					// send to printer
					$fullPdfPath = $render->SendToPrinter("Test Attendee", "Test Attendee Company"); 
					
					$displayMsg = 'Successfully sent command to print label!';
				} catch (Exception $e) {
					$displayMsg = 'Failed to send the print command to the label printer!<br/>Check CUPS configuration. Ensure default printer is selected.';
				}
			}
		
		} else {
			$displayMsg = 'Failed! Invalid master password!';
		}
		
		// cleanup
		//unset($_POST);
		
		// debug info
		// var_dump($_POST);
		// echo $displayMsg;
	}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<title>Administrative Functions</title>
	<style type="text/css">
	.main-div {
		text-align: center;
	}
	input[type="submit"] {
		height: 50px;
	}
	
	input[type="password"] {
		height: 25px;
	}
	</style>
	<script type="text/javascript" src="includes/js/jquery.min.js"></script>
	<script type="text/javascript" src="includes/js/noty/packaged/jquery.noty.packaged.min.js"></script>
	<?php
		if (isset($displayMsg)) {	
			if (stristr($displayMsg,'fail')) {
				echo "<script type=\"text/javascript\">";
				echo "$(document).ready(function () { noty({ layout: 'top', type: 'error',";
				echo "text: '<strong>$displayMsg</strong>', dismissQueue: true, animation: { open: {height: 'toggle'},";
				echo "close: {height: 'toggle'}, easing: 'swing', speed: 500 }, timeout: 10000 }); }); </script>";	
			} elseif (stristr($displayMsg,'success')) {
				echo "<script type=\"text/javascript\">";
				echo "$(document).ready(function () { noty({ layout: 'top', type: 'success',";
				echo "text: '<strong>$displayMsg</strong>', dismissQueue: true, animation: { open: {height: 'toggle'},";
				echo "close: {height: 'toggle'}, easing: 'swing', speed: 500 }, timeout: 10000 }); }); </script>";	
			}
		}
	?>
</head>

<body>
<div class="main-div">
	<form method="post" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<h2>Administrative Functions</h2>
		<hr /><br />
		<strong>1. Enter master password :</strong><br /><br />
		<input name="password" type="password" style="width: 235px" />
		<br /><br /><br />
		<strong>2. Select function :</strong>
		<br /><br />
		<input name="import-file" type="file" />
		<br /><br />
		<input name="import" type="submit" value="Import Data" style="width: 235px" /> &nbsp;&nbsp;&nbsp; <input name="truncate-preregistered" type="submit" value="Truncate 'preregistered' Table" style="width: 235px" />
		<br /><br />
		<input name="export" type="submit" value="Export All Data" style="width: 235px" /> &nbsp;&nbsp;&nbsp; <input name="truncate-walkon" type="submit" value="Truncate 'walkon' Table" style="width: 235px" />
		<br /><br />
		<input name="optimize" type="submit" value="Optimize the Database" style="width: 235px" /> &nbsp;&nbsp;&nbsp; <input name="truncate-both" type="submit" value="Truncate Both Tables" style="width: 235px" />
		<br /><br />
		<input name="test-print" type="submit" value="Test Print Label" style="width: 235px" />
		<br /><br /><br />
		Month:
		<select name="month" id="month">
			<option value="01">01</option>
			<option value="02">02</option>
			<option value="03">03</option>
			<option value="04">04</option>
			<option value="05">05</option>
			<option value="06">06</option>
			<option value="07">07</option>
			<option value="08">08</option>
			<option value="09">09</option>
			<option value="10">10</option>
			<option value="11">11</option>
			<option value="12">12</option>
		</select>
		Day:  
		<select name="day" id="day">
			<option value="01">01</option>
			<option value="02">02</option>
			<option value="03">03</option>
			<option value="04">04</option>
			<option value="05">05</option>
			<option value="06">06</option>
			<option value="07">07</option>
			<option value="08">08</option>
			<option value="09">09</option>
			<option value="10">10</option>
			<option value="11">11</option>
			<option value="12">12</option>
			<option value="13">13</option>
			<option value="14">14</option>
			<option value="15">15</option>
			<option value="16">16</option>
			<option value="17">17</option>
			<option value="18">18</option>
			<option value="19">19</option>
			<option value="20">20</option>
			<option value="21">21</option>
			<option value="22">22</option>
			<option value="23">23</option>
			<option value="24">24</option>
			<option value="25">25</option>
			<option value="26">26</option>
			<option value="27">27</option>
			<option value="28">28</option>
			<option value="29">29</option>
			<option value="30">30</option>
			<option value="31">31</option>
		</select>
		Year:  
		<select name="year" id="year">
			<option value="2014">2014</option>
			<option value="2015">2015</option>
			<option value="2016">2016</option>
			<option value="2017">2017</option>
			<option value="2018">2018</option>
			<option value="2019">2019</option>
			<option value="2020">2020</option>
		</select>
		<br /><br />
		Time :
		<select name="hour" id="hour">
			<option value="01">01</option>
			<option value="02">02</option>
			<option value="03">03</option>
			<option value="04">04</option>
			<option value="05">05</option>
			<option value="06">06</option>
			<option value="07">07</option>
			<option value="08">08</option>
			<option value="09">09</option>
			<option value="10">10</option>
			<option value="11">11</option>
			<option value="12">12</option>
			<option value="13">13</option>
			<option value="14">14</option>
			<option value="15">15</option>
			<option value="16">16</option>
			<option value="17">17</option>
			<option value="18">18</option>
			<option value="19">19</option>
			<option value="20">20</option>
			<option value="21">21</option>
			<option value="22">22</option>
			<option value="23">23</option>
			<option value="24">24</option>
		</select>
		:
		<select name="minute" id="minute">
			<option value="01">01</option>
			<option value="02">02</option>
			<option value="03">03</option>
			<option value="04">04</option>
			<option value="05">05</option>
			<option value="06">06</option>
			<option value="07">07</option>
			<option value="08">08</option>
			<option value="09">09</option>
			<option value="10">10</option>
			<option value="11">11</option>
			<option value="12">12</option>
			<option value="13">13</option>
			<option value="14">14</option>
			<option value="15">15</option>
			<option value="16">16</option>
			<option value="17">17</option>
			<option value="18">18</option>
			<option value="19">19</option>
			<option value="20">20</option>
			<option value="21">21</option>
			<option value="22">22</option>
			<option value="23">23</option>
			<option value="24">24</option>
			<option value="25">25</option>
			<option value="26">26</option>
			<option value="27">27</option>
			<option value="28">28</option>
			<option value="29">29</option>
			<option value="30">30</option>
			<option value="31">31</option>
			<option value="32">32</option>
			<option value="33">33</option>
			<option value="34">34</option>
			<option value="35">35</option>
			<option value="36">36</option>
			<option value="37">37</option>
			<option value="38">38</option>
			<option value="39">39</option>
			<option value="40">40</option>
			<option value="41">41</option>
			<option value="42">42</option>
			<option value="43">43</option>
			<option value="44">44</option>
			<option value="45">45</option>
			<option value="46">46</option>
			<option value="47">47</option>
			<option value="48">48</option>
			<option value="49">49</option>
			<option value="50">50</option>
			<option value="51">51</option>
			<option value="52">52</option>
			<option value="53">53</option>
			<option value="54">54</option>
			<option value="55">55</option>
			<option value="56">56</option>
			<option value="57">57</option>
			<option value="58">58</option>
			<option value="59">59</option>
			<option value="60">60</option>
		</select>
		<br /><br />
		<input name="time" type="submit" value="Set System Date/Time" style="width: 235px" />	
	</form>
	<br />
	<hr />
	<h2>Notes</h2>
	Raspberry Pi does not have an internal clock, if it is not connected to the internet with ntpd running,<br />
	it is required to set date/time at <strong>each</strong> boot.<br /><br />
	<strong>Import Data Format</strong>: Excel 97-2003 Workbook (xls) or Excel Workbook (xlsx)<br /><br />
	<strong>Import Data</strong>: Excel must match the expected schema input with <u>no headings</u>: <br/>Column A (Last Name), Column B (First Name), Column C (Company)
	<br /><br />
	<hr />
	<h2>Credits</h2>
	<strong>Author</strong>: Patrick Stasko<br /><br />
	<strong>Website</strong>: <a href="http://lvlnrd.com" target="_blank">lvlnrd.com</a>, <a href="http://vmug.com/cleveland" target="_blank">vmug.com/cleveland</a><br /><br />
	<strong>Instructions</strong>: <a href="http://lvlnrd.com/raspberry-pi-dymo-printer-checkin-process-tutorial-cleveland-vmug" target="_blank">lvlnrd.com/raspberry-pi-dymo-printer-checkin-process-tutorial-cleveland-vmug</a><br /><br />
	<strong>Notes</strong>: This was developed for and by the Cleveland VMware User Group chapter.<br />Thank you Jason Sehlmeyer for testing and providing the DYMO printer. For use by all! 
	<br />
</div>
</body>
</html>
