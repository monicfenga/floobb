<?php
include("common.php");

if (isUserLoggedIn()) {
	header("location: index.php");
	exit();
}

outHtml1("Login");
?>

<?php
outHtml2("Login:", "index.php", false);
?>

<form method="POST" action="loginExecute.php">
	<label class="form-group">
		<input type="text" name="username" class="textboxes form-control" />
		<span class="form-label">Username</span>
	</label>
	<label class="form-group">
		<input type="password" name="password" class="textboxes form-control" />
		<span class="form-label">Password</span>
	</label>
	<label class="form-group">
		<span class="textboxes form-control"><input type="checkbox" name="remember" value="checked" /></span>
		<span class="form-label">Remember Me</span>
	</label>
	<?php
	if (isset($_GET['badLogin']) && $_GET['badLogin'] == '1') {
		echo '<div class="alert alert-warning">Incorrect Username and/or password!</div>';
	} else if (isset($_GET['badLogin']) && $_GET['badLogin'] == '2') {
		echo '<div class="alert alert-error">Username is banned.</div>';
	}
	?>
	<div id="submitDiv">
		<input type="submit" name="submit" value="Login" class="btn btn-primary full-width">
	</div>
</form>

<?php
outHtml3();
?>