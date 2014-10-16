<?php
// debug
// var_dump($_POST);

/** Standard required includes **/
require 'settings.php';

// reset display message
unset($displayMsg);

/** post logic **/
if ( isset($_POST['mode']) == true ) { 

	require 'includes/NameTagPrinter.php';

	if ( $_POST['mode'] == 'checkin' ) {
	
		if ( $_POST['id'] > 0 ) {
			
			try { 
				// create db connection
				$db  = new SQLite3($dbfile); 	
				
				// clean id + create Excel format of time		
				$datetime = 25569 + time() / 86400;
				
				if ( isset($_POST['id']) & intval($_POST['id']) > 0 ) {
					$id = $_POST['id']; 
				} else {
					$id = 0;
				}	
						
				// create update statement
				$sql = "UPDATE preregistered SET datetime = '$datetime' WHERE id = $id;";
				
				// execute update statement			
				$response = $db->exec($sql);
				
			} catch (Exception $e) {
				// close the db connection
				$db->close();
				
				$displayMsg = 'Failed. Check connection to database. Failed during update.';
			}
			
			if ( isset($response) & $response == true & $id != 0 ) {
				
				// select user for label print out
				$sql = "SELECT * FROM preregistered WHERE id = '$id';";
				
				// query database
				$reponse = $db->query($sql);
				$row = $reponse->fetchArray();
				
				if ( count($row) > 0 ) { 
				
					session_start();
					$_SESSION['firstname'] = $row['firstname'];
					$_SESSION['lastname'] = $row['lastname'];
					$_SESSION['company'] = $row['company'];
					
					$displayMsg = "<strong>".$_SESSION['firstname']." ".$_SESSION['lastname']."</strong>, you\'ve successfully checked in! Thank you!<br />Please proceed to take your name tag label.";
				} else {
					$displayMsg = 'Failed. Invalid row id!';
				}
				
				// close the db connection
				$db->close();
				
			} else {
				$displayMsg = 'Failed to check in! See volunteer!';
			}
								
		}
		
		// cleanup post variables
		unset($_POST['id']);
		unset($_POST['mode']);
		
	}
	elseif ( $_POST['mode'] == 'register' ) {
		
		try {
			// create db connection
			$db  = new SQLite3($dbfile); 	
						
			// clean variables + create Excel format of time
			$lastname = $_POST['lastname'];
			$firstname = $_POST['firstname'];
			$company = $_POST['company'];
			$email = strtolower($_POST['email']);
			$datetime = 25569 + time() / 86400;
			
			// create insert statement
			$sql = $db->prepare('INSERT INTO walkon (lastname,firstname,company,email,datetime) VALUES (:lastname,:firstname,:company,:email,:datetime)');
			$sql -> bindValue(':lastname',$lastname,SQLITE3_TEXT);
			$sql -> bindValue(':firstname',$firstname,SQLITE3_TEXT);
			$sql -> bindValue(':company',$company,SQLITE3_TEXT);
			$sql -> bindValue(':email',$email,SQLITE3_TEXT);
			$sql -> bindValue(':datetime',$datetime,SQLITE3_TEXT);

			// execute insert statement				
			$response = $sql->execute();
			
		} catch (Exception $e) {
		
			// close the db connection
			$db->close();
			
			$displayMsg = 'Failed. Check connection to database. Failed during update.';
		}
		
		// deal with the response, display msg accordingly 	
		if ( isset($response) & $response == true ) { 
			// print the nametag label
			$render = new NameTagPrinter(); 
			$render->SetSaveDirectory($tmpdir);
			$render->SendToPrinter("$firstname $lastname", $company); 
			
			$displayMsg = "<strong>$firstname $lastname</strong>, you\'ve successfully checked in! Thank you!<br/>Please proceed to take your name tag label.";
			
			// close the db connection
			$db->close();
		
		} else {
			$displayMsg = 'Failed to check in! See volunteer!';
		}
		
		// cleanup post variables
		unset($_POST['firstname']);
		unset($_POST['lastname']);
		unset($_POST['company']);
		unset($_POST['email']);
		unset($_POST['mode']);
		
	}
}

?>
<html>
<head>
	<title>Checkin/Registration</title>    
	<link rel="stylesheet" type="text/css" href="style.css">
	<script type="text/javascript" src="includes/js/jquery.min.js"></script>
	<script type="text/javascript" src="includes/js/jquery.validate.min.js"></script>
	<script type="text/javascript" src="includes/js/noty/packaged/jquery.noty.packaged.min.js"></script>
	<script type="text/javascript">
		(function($,W,D)
		{
			var JQUERY4U = {};
		
			JQUERY4U.UTIL =
			{
				setupFormValidation: function()
				{
					//Form validation rules
					$("#register-form").validate({
						rules: {
							firstname: {
								required: true,
								minlength: 2
							},
							lastname: {
								required: true,
								minlength: 2
							},
							email: {
								required: true,
								email: true
							},
							company: {
								required: true,
								minlength: 2
							}
						},
						messages: {
							firstname: "Please enter your firstname",
							lastname: "Please enter your lastname",
							email: "Please enter a valid email address",
							company: "Please enter your company"
						},
						submitHandler: function(form) {
							form.submit();
						}
					});
				}
			}
		
			$(D).ready(function($) {
				JQUERY4U.UTIL.setupFormValidation();
			});
		
		})(jQuery, window, document);
	</script>
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
    <div id="wrapper">
        <div id="left-container">
            <span class="span-top"><h2>Pre-Registered</h2></span>
            <div class="float-middle">
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="check-in-form">
                <span class="styled-select">
                    <select name="id">
                        <?php 
							
							try {
							
								// open up the database
								$db  = new SQLite3($dbfile);
														
								// query table from db
								$preregistered = $db->query('SELECT * FROM preregistered WHERE datetime IS NULL;');
																							
								while ($row = $preregistered->fetchArray()) {
									//check if user is not already checked in
									if ( strlen(trim($row['datetime'])) == 0 ) {																						
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
								}
								
								// close the db connection
								$db->close();
							
							} catch (Exception $e) {
								echo '<option value="null">Cannot open database!</option>';	
							}
                                
                        ?>
                    </select>
                </span>
                <br/><br/>
                <input type="hidden" name="mode" value="checkin">
                <input type="submit" value="Checkin" class="checkin-button">
                </form>
            </div>
        </div>
        <div id="right-container">
            <h2>Walk-on Registration</h2>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="register-form">
                <span class="form-tag">First Name:</span><br/><input type="text" name="firstname" size="35"><br/><br/>
                <span class="form-tag">Last Name:</span><br/><input type="text" name="lastname" size="35"><br/><br/>
                <span class="form-tag">Email Address:</span><br/><input type="text" name="email" size="35"><br/><br/>
                <span class="form-tag">Company:</span><br/><input name="company" type="text" size="35"><br/><br/>
                <input type="hidden" name="mode" value="register">
                <input type="submit" value="Register" class="register-button">
            </form>
        </div>
	</div>
</body>
</html>
<?php

//print after page load, end user experience is quicker
if ( isset($_SESSION) ) {

	$render = new NameTagPrinter(); 
	$render->SetSaveDirectory($tmpdir);
	$render->SendToPrinter($_SESSION['firstname'] . ' ' . $_SESSION['lastname'], $_SESSION['company']); 

	//cleanup
	unset($render);
	session_unset();
}
?>
					
