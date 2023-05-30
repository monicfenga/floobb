<?php
include("common.php");

outHtml1("View User - " . htmlentities($_GET['userId']));
?>

<?php
outHtml2("View User: " . htmlentities($_GET['userId']), $_SERVER['HTTP_REFERER']);

$temp = new User(file_get_contents("db/Users/" . $_GET['userId'] . ".dat"));
?>
<div class="grid">

	<div class="cell cell-2">
		<?php
		if ($temp->getAvatar() != "") {
			echo "<img src='" . $temp->getAvatar() . "' />";
		}
		?>


	</div>

	<div class="cell cell-10">
		<div class="grid">

			<label class="form-group cell cell-4">
				<span class="textboxes form-control"><?= $temp->getUserId() ?></span>
				<span class="form-label">Name</span>
			</label>
			<label class="form-group cell cell-4">
				<span class="textboxes form-control"><?= $temp->getLevel() ?></span>
				<span class="form-label">Level</span>
			</label>
			<label class="form-group cell cell-4">
				<span class="textboxes form-control"><?= ($temp->isBanned() == 'false' ? 'No' : 'Yes') ?></span>
				<span class="form-label">Banned</span>
			</label>
		</div>
	</div>

	<label class="form-group form-info cell cell-4">
		<span class="textboxes form-control"><?= $temp->getJoinDate() ?></span>
		<span class="form-label">Join Date</span>
	</label>
	<label class="form-group form-info cell cell-4">
		<span class="textboxes form-control"><?= $temp->getNoPosts() ?></span>
		<span class="form-label">No Of Posts</span>
	</label>
	<label class="form-group form-info cell cell-4">
		<span class="textboxes form-control"><?= $temp->getNoTopics() ?></span>
		<span class="form-label">No Of Topics</span>
	</label>
	<label class="form-group cell cell-12">
		<span class="textboxes form-control"><?= $temp->getSig() ?></span>
		<span class="form-label">Signature</span>
	</label>
</div>

<?php if (isUserLoggedIn()) : ?>
	<a href='pmCompose.php?userId=<?= htmlentities($temp->getUserId()) ?>' class="btn btn-primary ">PM User</a>
<?php endif; ?>


<?php outHtml3(); ?>