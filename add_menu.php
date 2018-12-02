<?php #

include ('includes/header.html');
require_once ('mysqli_connect.php');

if (isset($_POST['submitted'])) { // Handle the form.
	
	// Validate the incoming data...
	$errors = array();

	// Check for a menu name:
	if (!empty($_POST['menu_name'])) {
		$pn = trim($_POST['menu_name']);
	} else {
		$errors[] = 'Please enter the menu name!';
	}
	
	// Check for an image:
	if (is_uploaded_file ($_FILES['image']['tmp_name'])) {
	
		// Create a temporary file name:
		$temp = 'uploads/' . md5($_FILES['image']['name']);
	
		// Move the file over:
		if (move_uploaded_file($_FILES['image']['tmp_name'], $temp)) {
	
			echo '<p>The file has been uploaded!</p>';
			
			// Set the $i variable to the image's name:
			$i = $_FILES['image']['name'];
	
		} else { // Couldn't move the file over.
			$errors[] = 'The file could not be moved.';
			$temp = $_FILES['image']['tmp_name'];
		}

	} else { // No uploaded file.
		$errors[] = 'No file was uploaded.';
		$temp = NULL;
	}
	
	// Check for a size (not required):
	$s = (!empty($_POST['size'])) ? trim($_POST['size']) : NULL;
	
	// Check for a price:
	if (is_numeric($_POST['price'])) {
		$p = (float) $_POST['price'];
	} else {
		$errors[] = 'Please enter the menu price!';
	}
	
	// Check for a description (not required):
	$d = (!empty($_POST['description'])) ? trim($_POST['description']) : NULL;
	
	// Validate the food...
	if (isset($_POST['food']) && ($_POST['food'] == 'new') ) {
		// If it's a new food, add the food to the database...
		
		// Validate the first and middle names (neither required):
		$fn = (!empty($_POST['food_name'])) ? trim($_POST['food_name']) : NULL;
		$mn = (!empty($_POST['middle_name'])) ? trim($_POST['middle_name']) : NULL;

		// Check for a last_name...
		if (!empty($_POST['food_name'])) {
			
			$ln = trim($_POST['food_name']);
			
			// Add the food to the database:
			$q = 'INSERT INTO food (food_name, food_type) VALUES (?, ?)';
			$stmt = mysqli_prepare($dbc, $q);
			mysqli_stmt_bind_param($stmt, 'sss', $fn, $mn, $ln);
			mysqli_stmt_execute($stmt);
			
			// Check the results....
			if (mysqli_stmt_affected_rows($stmt) == 1) {
				echo '<p>The food has been added.</p>';
				$a = mysqli_stmt_insert_id($stmt); // Get the food ID.
			} else { // Error!
				$errors[] = 'The new food could not be added to the database!';
			}
			
			// Close this prepared statement:
			mysqli_stmt_close($stmt);
			
		} else { // No last name value.
			$errors[] = 'Please enter the food name!';
		}
		
	} elseif ( isset($_POST['food']) && ($_POST['food'] == 'existing') && ($_POST['existing'] > 0) ) { // Existing food.
		$a = (int) $_POST['existing'];
	} else { // No food selected.food
		$errors[] = 'Please enter or select the menu food!';
	}
	
	if (empty($errors)) { // If everything's OK.
	
		// Add the menu to the database:
		$q = 'INSERT INTO menu (food_id, menu_name, price, size, description, image_name) VALUES (?, ?, ?, ?, ?, ?)';
		$stmt = mysqli_prepare($dbc, $q);
		mysqli_stmt_bind_param($stmt, 'isdsss', $a, $pn, $p, $s, $d, $i);
		mysqli_stmt_execute($stmt);
		
		// Check the results...
		if (mysqli_stmt_affected_rows($stmt) == 1) {
		
			// menu a message:
			
			echo '<p>The menu has been added.</p>';
			
			// Rename the image:
			$id = mysqli_stmt_insert_id($stmt); // Get the menu ID.
			rename ($temp, "../uploads/$id");
			
			// Clear $_POST:
			$_POST = array();
			
		} else { // Error!
			echo '<p style="font-weight: bold; color: #C00">Your submission could not be processed due to a system error.</p>'; 
		}
		
		mysqli_stmt_close($stmt);
		
	} // End of $errors IF.
	
	// Delete the uploaded file if it still exists:
	if ( isset($temp) && file_exists ($temp) && is_file($temp) ) {
		unlink ($temp);
	}
	
} // End of the submission IF.

// Check for any errors and menu them:
if ( !empty($errors) && is_array($errors) ) {
	echo '<h1>Error!</h1>
	<p style="font-weight: bold; color: #C00">The following error(s) occurred:<br />';
	foreach ($errors as $msg) {
		echo " - $msg<br />\n";
	}
	echo 'Please reselect the menu image and try again.</p>';
}

// Display the form...
?>
   <div id="content">
      <div class="container">
         <div class="inside">
            <!-- box begin -->
            <div class="box alt">
            	<div class="left-top-corner">
               	<div class="right-top-corner">
                  	<div class="border-top"></div>
                  </div>
               </div>
               <div class="border-left">
               	<div class="border-right">
                  	<div class="inner">
<h4>Add an Item to Menu</h4> <br />
<form enctype="multipart/form-data" action="add_menu.php" method="post">

	<input type="hidden" name="MAX_FILE_SIZE" value="524288" />
	
	<fieldset><legend>Fill out the form to add an item to to the catalog:</legend>
	
	<p><b>Menu Name:</b> <input type="text" name="menu_name" size="30" maxlength="60" value="<?php if (isset($_POST['menu_name'])) echo htmlspecialchars($_POST['menu_name']); ?>" /></p>
	
	<p><b>Image:</b> <input type="file" name="image" /></p>
	
	<div><b>Food:</b>
	<p><input type="radio" name="food" value="existing" <?php if (isset($_POST['food']) && ($_POST['food'] == 'existing') ) echo ' checked="checked"'; ?>/> Existing =>
	<select name="existing"><option>Select One</option>
	<?php // Retrieve all the foods and add to the pull-down menu.
	$q = "SELECT food_id, CONCAT_WS(' ', food_name, food_type) FROM food ORDER BY food_name, food_type ASC";
	$r = mysqli_query ($dbc, $q);
	if (mysqli_num_rows($r) > 0) {
		while ($row = mysqli_fetch_array ($r, MYSQLI_NUM)) {
			echo "<option value=\"$row[0]\"";
			// Check for stickyness:
			if (isset($_POST['existing']) && ($_POST['existing'] == $row[0]) ) echo ' selected="selected"';
			echo ">$row[1]</option>\n";
		}
	} else {
		echo '<option>Please add a new food.</option>';
	}
	mysqli_close($dbc); // Close the database connection.
	?>
	</select></p>
	
	<p><input type="radio" name="food" value="new" <?php if (isset($_POST['food']) && ($_POST['food'] == 'new') ) echo ' checked="checked"'; ?>/> New =>
	Food Name: <input type="text" name="first_name" size="10" maxlength="20" value="<?php if (isset($_POST['food_name'])) echo $_POST['food_name']; ?>" />
	Food Type: <input type="text" name="middle_name" size="10" maxlength="20" value="<?php if (isset($_POST['food_type'])) echo $_POST['food_type']; ?>" />
	</div>
	
	<p><b>Price:</b> <input type="text" name="price" size="10" maxlength="10" value="<?php if (isset($_POST['price'])) echo $_POST['price']; ?>" /> <small>Do not include the dollar sign or commas.</small></p>
	
	<p><b>Pic Size:</b> <input type="text" name="size" size="30" maxlength="60" value="<?php if (isset($_POST['size'])) echo htmlspecialchars($_POST['size']); ?>" /> (optional)</p>
	
	<p><b>Description:</b> <textarea name="description" cols="40" rows="5"><?php if (isset($_POST['description'])) echo $_POST['description']; ?></textarea> (optional)</p>
	
	</fieldset>
		
	<div align="center"><input type="submit" name="submit" value="Submit" /></div>
	<input type="hidden" name="submitted" value="TRUE" />

</form>
</div>
                     </div>
                  </div>
               </div>
               <div class="left-bot-corner">
               	<div class="right-bot-corner">
                  	<div class="border-bot"></div>
                  </div>
               </div>
            </div>
            </div></div>
</body>
</html>
<?php include ('includes/footer.html');
?>