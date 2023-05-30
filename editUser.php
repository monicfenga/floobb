<?php
include("common.php");

if ($_SESSION['user']->getUserId() != $_GET['userId']) {
	header("Location: index.php");
}

outHtml1("Edit Your Profile");
?>
<?php call_editor(); ?>

<?php
outHtml2("Edit Your Profile", "index.php");
?>

<?php
$hideEmail = '';
$imgAvatar = "";
$temp = new User(file_get_contents("db/Users/" . $_GET['userId'] . ".dat"));

if ($temp->ishideEmail()) {
	$hideEmail = " checked";
}

if ($temp->getAvatar() != "") {
	$imgAvatar = "<img src='" . $temp->getAvatar() . "' />";
}
?>
<div class="grid">
	<label class="form-group cell cell-8">
		<span class="textboxes form-control"><?= $temp->getUserId() ?></span>
		<span class="form-label">Name</span>
	</label>
	<label class="form-group cell cell-4">
		<span class="textboxes form-control"><?= $temp->getLevel() ?></span>
		<span class="form-label">Level</span>
	</label>
	<label class="form-group form-info cell">
		<span class="textboxes form-control"><?= $temp->getJoinDate() ?></span>
		<span class="form-label">Join Date</span>
	</label>
	<label class="form-group form-info cell">
		<span class="textboxes form-control"><?= $temp->getNoPosts() ?></span>
		<span class="form-label">No Of Posts</span>
	</label>
	<label class="form-group form-info cell">
		<span class="textboxes form-control"><?= $temp->getNoTopics() ?></span>
		<span class="form-label">No Of Topics</span>
	</label>
</div>

<?php
	if (isset($_GET['error']) && $_GET['error'] == 1) {
		echo "<div class='alert alert-error'>Please enter a valid email address!</div>";
	}
	if (isset($_GET['error']) && $_GET['error'] == 2) {
		echo "<div class='alert alert-error'>Please enter a password longer than 2 characters!</div>";
	}
?>

<form method="post" action="editExecute.php">

	<label class="form-group">
		<input type="password" name="password" class="textboxes form-control" value="<?= $temp->getPassword() ?>" />
		<span class="form-label">Password</span>
	</label>
	<div class="grid grid-narrow">

		<label class="form-group cell">
			<input type="email" name="email" class="textboxes form-control" value="<?= $temp->getEmail() ?>" />
			<span class="form-label">Email Address</span>
		</label>
		<label class="form-group cell">
			<span class="form-control"><input type='checkbox' name='hideEmail' value='yes' <?= $hideEmail ?> /> Hide Email</span>
		</label>
	</div>


	<label class="form-group">
		<textarea name="sig" class="textboxes form-control"><?= $temp->getSig() ?></textarea>
		<span class="form-label">Signature</span>
	</label>

	<div class="grid grid-narrow">

	<div class="cell cell-8">

		<label class="form-group">
			<input type="text" name="avatar" class="textboxes form-control" value="<?= $temp->getAvatar() ?>" />
			<span class="form-label">Avatar</span>
		</label>
	</div>
		<div class="cell cell-4"><?= $imgAvatar ?></div>
	</div>

	<?php
	if ($_SESSION['user']->getLevel() == 3) {
		echo "<div class='form-group'><a href='userAdmin.php' class='btn btn-default'>User Admin</a> <a href='editBoardSettings.php' class='btn btn-default'>Edit Board Settings</a></div>";
	}
	?>

	<div id="submitDiv"><input type="submit" value="Update" class="btn btn-primary full-width" /></div>
</form>

<?php
outHtml3();
?>