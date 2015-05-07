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

	// select user for label print out
	if ( $_POST['mode'] == 'reprint' & isset($_POST['id']) ) { 
	
		// select user for label print out
		$sql = $db->prepare('SELECT * FROM attendees WHERE id = :id;');
		$sql -> bindValue(':id',$_POST['id']);	
		
		// query database
		$row = $sql->execute()->fetchArray();
		
	}
		
	if ( count($row) > 0 ) { 
		
		// require only if being called!
		require 'includes/NameTagPrinter.php';
	
		$render = new NameTagPrinter(); 
		$render->SetSaveDirectory($tmpdir);
		$render->SendToPrinter($row['firstname'] . ' ' . $row['lastname'], $row['company']); 
		
		$displayMsg = 'Reprinted <strong>'.$row['firstname'].' '.$row['lastname'].'</strong> label successfully!<br />Please proceed to take your name tag label.';
		
	} else { $displayMsg = 'Failed. Invalid row id!'; }
	
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
<div id="wrapper" style="text-align:center;">
	<h2>Attendees</h2>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="reprint-form">
		<span class="styled-select">
			<select name="id">
				 <?php 						
					// query table from db
					$preregistered = $db->query('SELECT * FROM attendees');
																				
					while ($row = $preregistered->fetchArray()) {
									
						echo '<option value="', $row["id"],'">', $row["lastname"], ', ', $row["firstname"], ' - ', $row["company"] ,'</option>';
						
					}
						
				?>
			</select>
		</span>
		<br/><br/>
		<input type="hidden" name="mode" value="reprint">
		<input type="submit" value="Reprint" class="checkin-button">
	</form>
</div>
</body>
</html>
<?php
	$db->close();
?>