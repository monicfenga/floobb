<?php
include("common.php");
outHtml1("Edit Board Settings");
?>
<?php call_editor(); ?>
<?php
outHtml2("Edit Board Settings", "index.php");
?>

<form method="post" action="editBoardSettingsExecute.php">

	<label class="form-group">
		<input type="text" name="name" class="textboxes form-control" value="<?= $boardname ?>" />
		<span class="form-label">Board Name</span>
	</label>
	<label class="form-group">
		<input type="text" name="description" class="textboxes form-control" value="<?= trim(file_get_contents("db/boarddescription.dat")) ?>" />
		<span class="form-label">Board Description</span>
	</label>
	<label class="form-group">
		<input type="number" name="topics" class="textboxes form-control" min="1" step="1" value="<?= trim(file_get_contents("db/topicsperpage.dat")) ?>" />
		<span class="form-label">No. Of Topics Per Page</span>
	</label>
	<label class="form-group">
		<span class="textboxes form-control"><input type='checkbox' class='inputBox' name='smiley' value='yes' /></span>
		<span class="form-label">Recalculate Smilies</span>
	</label>
	<?php
	if (isset($_GET['error'])) {
		switch ($_GET['error']) {
			case 1:
				echo "<div class='alert alert-error'>Please enter a board name!</div>";

				break;
			case 2:
				echo "<div class='alert alert-error'>Please enter a board description!</div>";

				break;
			case 3:
				echo "<div class='alert alert-error'>Please enter a valid number of topics per page!</div>";

				break;
			default:
				// do nothing
				break;
		}
	}
	?>
	<div id="submitDiv"><input type="submit" value="Update" class="btn btn-primary full-width" /></div>
</form>
<?php
outHtml3();
?>