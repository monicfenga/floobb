<?php
include("common.php");

outHtml1("Add Forum");
?>

<?php call_editor(); ?>

<?php

outHtml2("Add Forum", "index.php");
?>
<form action="addForumExecute.php" method="post">
	<label class="form-group">
		<input type="text" name="name" class="textboxes form-control"  />
		<span class="form-label">Forum Name</span>
	</label>
	<label class="form-group">
		<textarea name="description" id="description" class="textboxes form-control"></textarea>
		<span class="form-label">Forum Description</span>
	</label>

	<?php
	if (isset($_GET['error']) && $_GET['error'] == 1) {
		echo "<div class='alert alert-error'>Please enter a forum name!</div>";
	} else if (isset($_GET['error']) && $_GET['error'] == 2) {
		echo "<div class='alert alert-error'>Please enter a forum description!</div>";
	}
	?>
	<div id="submitDiv"><input type="submit" value="Add Forum" class="btn btn-primary full-width" /></div>
</form>
<?php
outHtml3();
?>