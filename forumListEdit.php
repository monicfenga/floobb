<?php
	include("common.php");
	$fileC = file("db/forumList.dat");
	foreach ($fileC as $item)
	{
		$temp = new Forum($item);
		if ($temp->getForumId() == $_GET['forumId'])
		{
			$forum = $temp;
		}
	}
	
	outHtml1("Edit Forum");
?>

<?php call_editor(); ?>

<?php
	outHtml2("Edit Forum:","index.php");
?>	
		<form action="forumListEditExecute.php?forumId=<?php echo $forum->getForumId(); ?>" method="post">
			<div id="forumDiv">
			<label class="form-group">
				<input type="text" name="name" class="textboxes form-control" value="<?php echo $forum->getForumName() ?>" />
				<span class="form-label">Forum Name:</span>
			</label>
			<label class="form-group">
			<textarea name="description" class="textboxes form-control"><?php echo $forum->getDescription() ?></textarea>
				<span class="form-label">Forum Description:</span>
			</label>
				<?php
					if (isset($_GET['error']) && $_GET['error'] == 1)
					{
						echo "<div class='error'>Please enter a forum name!</div>";
					}
					else if (isset($_GET['error']) && $_GET['error'] == 2)
					{
						echo "<div class='error'>Please enter a forum description!</div>";
					}
				?>
				<input class="btn btn-primary" type="submit" value="Update" />
			</div>
		</form>
	
<?php
	outHtml3();
?>
