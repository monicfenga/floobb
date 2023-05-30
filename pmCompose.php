<?php
include("common.php");
outHtml1("Compose");
?>
<?php call_editor(); ?>

<?php
outHtml2("Compose:", "index.php");
?>

<form action="pmComposeExecute.php?userId=<?php echo $_GET['userId'] ?>" method="post">

	<label class="form-group form-info">
		<span class="textboxes form-control"><?php echo $_GET['userId'] ?></span>
		<span class="form-label">To</span>
	</label>
	<label class="form-group">
		<input type="text" name="subject" class="textboxes form-control" />
		<span class="form-label">Subject</span>
	</label>
	<label class="form-group">
		<textarea name="message" class="textboxes form-control"></textarea>
		<span class="form-label">Message</span>
	</label>

	<?php
	if (isset($_GET['error'])) {
		switch ($_GET['error']) {
			case 1:
				echo "<div class='alert alert-error'>Please enter a subject!</div>";
				break;
			case 2:
				echo "<div class='alert alert-error'>Please enter a message!</div>";
				break;
			default:
				// do nothing
				break;
		}
	}
	?>
	<div id="submitDiv"><input type="submit" value="Send" class="btn btn-primary full-width" /></div>
</form>

<?php
outHtml3();
?>