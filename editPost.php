<?php
include("common.php");

$fileC = file("db/Topics/" . $_GET['topicId'] . "/posts.dat", FILE_IGNORE_NEW_LINES);
foreach ($fileC as $line) {
	$temp = new Post($line);
	if ($temp->getPostId() == $_GET['postId']) {
		$actual = $temp;
	}
}

outHtml1("Edit Post");
?>

<?php call_editor(); ?>

<?php
outHtml2("Edit Post:", "viewPosts.php?topicId=" . $actual->getTopicId());
?>

<form action="editPostExecute.php?topicId=<?php echo htmlentities($_GET['topicId']) ?>&postId=<?php echo htmlentities($_GET['postId']) ?>" method="post">
	<div id="messageDiv">
		<label class="form-group">
			<textarea name="message" class="textboxes form-control"><?php echo trim($actual->getMessage()); ?></textarea>
			<span class="form-label">Post Message:</span>
		</label>
		<?php
		if (isset($_GET['error']) && $_GET['error'] == 1) {
			echo "<div class='alert alert-error'>Please enter a post!</div>";
		}
		?>
		<input type="submit" value="Change" class="btn btn-primary full-width" />
	</div>
</form>
<?php
outHtml3();
?>