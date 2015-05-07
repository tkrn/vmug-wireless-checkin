<?php
// debug
// var_dump($_POST);

/** Standard required includes **/
require 'settings.php';

// reset display message
unset($displayMsg);

/** post logic **/
if ( isset($_POST['mode']) == true ) { 

	// create db connection
	$db = new SQLite3($dbfile); 
	
	// clean id + create Excel format of time		
	$now_datetime = 25569 + time() / 86400;
				
	if ( $_POST['mode'] == 'checkin' ) {
		
		$id = $_POST['id'];
		
		if ( $id > 0 ) {
						
			// prepare statement to update checkin_stamp
			$sql = $db->prepare('UPDATE attendees SET checkin_stamp = :checkin_stamp WHERE id = :id');
			$sql -> bindParam(':checkin_stamp',$now_datetime);
			$sql -> bindParam(':id',$id);

			// execute update statement			
			$response = $sql->execute();

			// re-query the user  data because all we have is an id!
			if ( isset($response) & $response == true & $id > 0 ) {
				
				// select user for label print out
				$sql = $db->prepare('SELECT * FROM attendees WHERE id = :id;');
				$sql -> bindValue(':id',$id);
				
				// query database + fetch rows
				$row = $sql->execute()->fetchArray();
				
				if ( count($row) > 0 ) { 
				
					session_start();
					$_SESSION['firstname'] = $row['firstname'];
					$_SESSION['lastname'] = $row['lastname'];
					$_SESSION['company'] = $row['company'];
					$_SESSION['company_type'] = $row['company_type'];
					
					$displayMsg = "<strong>".$_SESSION['firstname']." ".$_SESSION['lastname']."</strong> you\'ve successfully checked in! Thank you!<br />Please proceed to take your name tag label.";
					
				} else { $displayMsg = 'Failed. Invalid row id!'; }
				
			} else { $displayMsg = 'Failed to check in! See a volunteer!'; }
								
		}
		
	}
	elseif ( $_POST['mode'] == 'register' ) {
		
		try {
								
			// clean variables + create Excel format of time
			$lastname = $_POST['lastname'];
			$firstname = $_POST['firstname'];
			$company = $_POST['company'];
			$company_type = $_POST['company_type'];
			$email = strtolower($_POST['email']);
					
			// create insert statement
			$sql = $db->prepare('INSERT INTO "attendees" ("lastname","firstname","company","company_type","email","pre_registered","checkin_stamp") VALUES (:lastname,:firstname,:company,:company_type,:email,:pre_registered,:checkin_stamp)');
			
			$sql -> bindValue(':lastname',$lastname);
			$sql -> bindValue(':firstname',$firstname);
			$sql -> bindValue(':company',$company);
			$sql -> bindValue(':company_type',$company_type);
			$sql -> bindValue(':email',$email);
			$sql -> bindValue(':pre_registered',0);
			$sql -> bindValue(':checkin_stamp',$now_datetime);

			// execute insert statement				
			$response = $sql->execute();
			
		} catch (Exception $e) { $displayMsg = 'Failed. Check connection to database. Failed during update.'; }
		
		// deal with the response, display msg accordingly 	
		if ( isset($response) & $response == true ) { 
		
			session_start();
			$_SESSION['firstname'] = $firstname;
			$_SESSION['lastname'] = $lastname;
			$_SESSION['company'] = $company;
			$_SESSION['company_type'] = $company_type;
			
			$displayMsg = "<strong>$firstname $lastname</strong>, you\'ve successfully checked in! Thank you!<br/>Please proceed to take your name tag label.";
			
		} else { $displayMsg = 'Failed to check in! See a volunteer!'; }
			
	}
	
	// close the db connection
	$db->close();
	
	// cleanup post variables
	unset($_POST);
	
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
							firstname: " !!!",
							lastname: " !!!",
							email: " !!!",
							company: " !!!",
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
								$pre_registered_list = $db->query('SELECT * FROM pre_registered_list');
																							
								while ($row = $pre_registered_list->fetchArray()) {
									
									$companyText = trim($row["company"]);
																		
									//display html to show as listbox
									if ( strlen($companyText) == 0 ) {								
										echo '<option value="', $row["id"],'">', $row["lastname"], ', ', $row["firstname"],'</option>';									
									} else {								
										echo '<option value="', $row["id"],'">', $row["lastname"], ', ', $row["firstname"], ' - ', $companyText ,'</option>';										
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
				<label for="firstname">First Name:</label><input type="text" name="firstname" size="35"><br/><br/>
                <label for="lastname">Last Name:</label><input type="text" name="lastname" size="35"><br/><br/>
                <label for="email">Email Address:</label><input type="text" name="email" size="35"><br/><br/>
                <label for="company">Company:</label><input name="company" type="text" size="35"><br/><br/>
				<input type="radio" name="company_type" value="1" checked> Customer &nbsp;
				<input type="radio" name="company_type" value="2"> Partner &nbsp;
				<input type="radio" name="company_type" value="3"> Vendor &nbsp;
				<br/><br/>
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

	require 'includes/NameTagPrinter.php';
	
	$render = new NameTagPrinter(); 
	$render->SetSaveDirectory($tmpdir);
	$render->SendToPrinter($_SESSION['firstname'] . ' ' . $_SESSION['lastname'], $_SESSION['company']); 

	//cleanup
	unset($render);
	session_unset();
}
?>
					
