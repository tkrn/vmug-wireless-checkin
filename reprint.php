<?php
require 'settings.php';

try {
	// open up the database
	$db  = new SQLite3($dbfile);
} catch (Exception $e) {
	throw new Exception('Failed to load database. Check SQLite database file.');
}

// is post set?
if ( isset($_POST['mode']) ) { 

	// require only if being called!
	require 'includes/NameTagPrinter.php';

	// walkon or preregistered + select user for label print out
	if ( $_POST['mode'] == 'preregister-reprint' & isset($_POST['preregister-rowid']) ) { 
		$id = $_POST['preregister-rowid'];
		$sql = "SELECT * FROM preregistered WHERE id = $id;";
	} elseif ( $_POST['mode'] == 'walkon-reprint' & isset($_POST['walkon-rowid']) ) {
		$id = $_POST['walkon-rowid'];
		$sql = "SELECT * FROM walkon WHERE id = $id;";
		
	}
	
	// query database
	$reponse = $db->query($sql);
	
	if ( $response ) {
		$row = $reponse->fetchArray();
			
		if ( count($row) > 0 ) { 
			$render = new NameTagPrinter(); 
			$render->SetSaveDirectory($tmpdir);
			$render->SendToPrinter($row['firstname'] . ' ' . $row['lastname'], $row['company']); 
			
			$displayMsg = 'Reprinted <strong>'.$row['firstname'].' '.$row['lastname'].'</strong> label successfully!<br />Please proceed to take your name tag label.';
		} else {
			$displayMsg = 'Failed. Invalid row id!';
		}
	}
}
?>
<html>
<head>
	<title>Manual Tag Reprint</title>
	<link rel="stylesheet" type="text/css" href="style.css">
	<script type="text/javascript" src="includes/js/jquery.min.js"></script>
	<script type="text/javascript" src="includes/js/jquery.validate.min.js"></script>
	<script type="text/javascript" src="includes/js/noty/packaged/jquery.noty.packaged.min.js"></script>    
</head>
<body>
<div id="wrapper">
	<div id="left-container-reprint">
		<h2>Pre-Registered</h2>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="preregister-reprint-form">
			<span class="styled-select">
				<select name="preregister-rowid">
					 <?php 						
						// query table from db
						$preregistered = $db->query('SELECT * FROM preregistered;');
																					
						while ($row = $preregistered->fetchArray()) {
							//clean up long company titles.
							if ( strlen(trim($row["company"])) > 15 ) { 
								$companyText = trim(substr(trim($row["company"]), 0, 15)) . '..';
							} else {
								$companyText = trim($row["company"]);								
							}
							
							//display html to show as listbox
							if ( strlen($companyText) == 0 ) {								
								echo '<option value="', $row["id"],'">', $row["lastname"], ', ', $row["firstname"],'</option>';									
							} else {								
								echo '<option value="', $row["id"],'">', $row["lastname"], ', ', $row["firstname"], ' - ', $companyText ,'</option>';										
							}
						}
							
					?>
				</select>
			</span>
			<br/><br/>
			<input type="hidden" name="mode" value="preregister-reprint">
			<input type="submit" value="Reprint Pre-Registered" class="checkin-button">
		</form>
	</div>
	
	<div id="right-container-reprint">
		<h2>Walk-on Registration</h2>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="walkon-reprint-form">
			<span class="styled-select">
				<select name="walkon-rowid">
					 <?php 
						// query table from db
						$preregistered = $db->query('SELECT * FROM walkon;');
																					
						while ($row = $preregistered->fetchArray()) {
							//clean up long company titles.
							if ( strlen(trim($row["company"])) > 15 ) { 
								$companyText = trim(substr(trim($row["company"]), 0, 15)) . '..';
							} else {
								$companyText = trim($row["company"]);								
							}
							
							//display html to show as listbox
							if ( strlen($companyText) == 0 ) {								
								echo '<option value="', $row["id"],'">', $row["lastname"], ', ', $row["firstname"],'</option>';									
							} else {								
								echo '<option value="', $row["id"],'">', $row["lastname"], ', ', $row["firstname"], ' - ', $companyText ,'</option>';										
							}
						}
							
					?>
				</select>
			</span>
			<br/><br/>
			<input type="hidden" name="mode" value="walkon-reprint">
			<input type="submit" value="Reprint Walk-on" class="checkin-button">
		</form>
	
	</div>

</div>
</body>
</html>
<?php
	$db->close();
?>