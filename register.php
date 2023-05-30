<?php
include("common.php");

if (isUserLoggedIn()) {
	header("location: index.php");
	exit();
}

outHtml1("Register");
outHtml2("Register", "index.php", false);
?>

<form method="POST" action="registerExecute.php">

	<label class="form-group">
		<input type="text" name="username" class="textboxes form-control"  />
		<span class="form-label">Username</span>
	</label>
	<label class="form-group">
		<input type="password" name="password" class="textboxes form-control"  />
		<span class="form-label">Password</span>
	</label>
	<div class="grid grid-narrow">
		<label class="form-group cell">
			<input type="email" name="email" class="textboxes form-control" />
			<span class="form-label">Email Address</span>
		</label>
		<label class="form-group cell">
			<span class="form-control"><input type='checkbox' name='hideEmail' value='yes' checked /> Hide Email</span>
		</label>
	</div>

	<?php
	if (isset($_GET['badRegister'])) {
		switch ($_GET['badRegister']) {
			case 1:
				echo "<div class='alert alert-warning'>Username already exists!</div>";
				break;
			case 2:
				echo "<div class='alert alert-warning'>Please fill in username and password!</div>";
				break;
			case 3:
				echo "<div class='alert alert-warning'>Username or password too short!</div>";
				break;
			case 4:
				echo "<div class='alert alert-warning'>Bad email address!</div>";
				break;
			case 5:
				echo "<div class='alert alert-warning'>Username must contain only letters, numbers and the underscore!</div>";
				break;
			default:
				// do nothing
				break;
		}
	}

	?>
	<div id='submitDiv'>
		<input type="submit" name="submit" value="Register" class="btn btn-primary full-width" />
	</div>
</form>

<?php
outHtml3();
?>