<?php

	###
	#
	#  Symphony web publishing system
	# 
	#  Copyright 2004 - 2006 Twenty One Degrees Pty. Ltd. This code cannot be
	#  modified or redistributed without permission.
	#
	#  For terms of use please visit http://21degrees.com.au/products/symphony/terms/
	#
	###

	if($Admin->authorIsLoggedIn()) General::redirect(URL . "/symphony/");
	
	switch ($_GET['_f']){
		case "error":
			$error = "Login invalid. <a href=\"".URL."/symphony/?page=/login/&amp;forgot\">Forgot your password?</a>";
			break;
	}
		
	

if((isset($_REQUEST['forgot']) || $_f == "forgot") && $_f != "newpass"): ?>

	<form action="<?php print $Admin->getCurrentPageURL(); ?>" method="post">
  	<h2><span>Symphony &ndash; Email Login Details</span></h2>
		<fieldset>
			<?php if(isset($error)) : ?>
				<div><?php print $error; ?></div>
			<?php endif; ?>			
			<p>Please enter your email address and your username and a new password will be sent to you.</p>
			<label>Email address <input name="email" type="text" /></label>
			<input name="action[reset]" type="submit" value="Send" />
		</fieldset>
	</form>		
		
<?php else: ?>
	<form action="<?php print $Admin->getCurrentPageURL(); ?>" method="post">
		<fieldset>
			<legend>Login</legend>
			<?php if(isset($error)) : ?>
				<div><?php print $error; ?></div>
			<?php endif; ?>			
			<label>Username <input name="username" /></label>
			<label>Password <input name="password" type="password" /></label>
			<input name="action[login]" type="submit" value="Login" />
		</fieldset>
	</form>
<?php endif; ?>
