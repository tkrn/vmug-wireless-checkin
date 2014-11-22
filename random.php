<?php
// debug
// var_dump($_POST);

/** Standard required includes **/
require 'settings.php';

// reset display message
unset($displayMsg);

/** post logic **/
if ( isset($_POST['mode']) == true ) { 

	if ( $_POST['mode'] == 'random' ) {
	
		try { 
			// create db connection
			$db  = new SQLite3($dbfile); 	
							
			// query view, ONLY ever returns 1 user
			$sql = "SELECT * FROM randomregistered";
			
			// execute query		
			$response = $db->query($sql);
			
			// assign variable for later use
			while ($row = $response->fetchArray()) {
				$firstname = $row["firstname"];
				$lastname = $row["lastname"];
				$company = $row["company"];
			}
			
		} catch (Exception $e) {
			// close the db connection
			$db->close();
			
			$displayMsg = 'Failed. Check connection to database. Failed during update.';
		}
				
	}
}

?>
<html>
<head>
	<title>Random Registered User</title>    
	<link rel="stylesheet" type="text/css" href="style.css">
	<script type="text/javascript" src="includes/js/jquery.min.js"></script>
	<script type="text/javascript" src="includes/js/noty/packaged/jquery.noty.packaged.min.js"></script>
	<?php
		if (isset($displayMsg)) {	
			if (stristr($displayMsg,'fail')) {
				echo "<script type=\"text/javascript\">";
				echo "$(document).ready(function () { noty({ layout: 'top', type: 'error',";
				echo "text: '$displayMsg', dismissQueue: true, animation: { open: {height: 'toggle'},";
				echo "close: {height: 'toggle'}, easing: 'swing', speed: 500 }, timeout: 10000 }); }); </script>";	
			} elseif (stristr($displayMsg,'success')) {
				echo "<script type=\"text/javascript\">";
				echo "$(document).ready(function () { noty({ layout: 'top', type: 'success',";
				echo "text: '$displayMsg', dismissQueue: true, animation: { open: {height: 'toggle'},";
				echo "close: {height: 'toggle'}, easing: 'swing', speed: 500 }, timeout: 5000 }); }); </script>";	
			}
		}
	?>
</head>
<body>
<div id="wrapper" style="text-align:center;">
	<!-- <span class="span-top"><h2>Pre-Registered</h2></span> -->
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="random-form">
	<input type="hidden" name="mode" value="random">
	<input type="submit" value="Random Registered User" class="random-button">
	</form>
	<?php 		
		if ( isset($_POST['mode']) == true ) { 
			if ( $_POST['mode'] == 'random' ) {
				echo '<strong>' . $firstname . ' ' . $lastname . ' - ' . $company . '</strong>'; 
			} else {
				echo '<strong>No one registered!</strong>';
			}
		}
	?>
</div>
</body>
</html>
<?php
	// cleanup post variables
	unset($firstname);
	unset($lastname);
	unset($company);
	unset($_POST['mode']);
?>