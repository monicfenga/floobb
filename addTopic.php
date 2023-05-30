<?php
	include("common.php");
	
	outHtml1("Add Topic");
?>

<?php call_editor(); ?>

<?php
	outHtml2("Add Topic:","viewTopics.php?forumId=".$_GET['forumId']);
?>
		
		<form action="addTopicExecute.php?forumId=<?php echo $_GET['forumId']?>" method="post">
			<label class="form-group">
				<input type="text" name="topicName" class="form-control"/>
			    <span class="form-label">Topic Name</span>
			</label><label class="form-group">
				<textarea name="message" id="message" class="form-control"></textarea>
			    <span class="form-label">Message</span>
				<div class="help-block">Images may be no bigger than 600 x 600 and 200kB.</div>
			</label>
			<?php
				if (isset($_GET['error']) && $_GET['error'] == 1)
				{
					echo "<div class='error'>Please enter a topic name and a post!</div>";
				}
			?>
			<div id="submitDiv"><input class="btn btn-primary" type="submit" value="Add Topic" /></div>
		</form>
		
<?php
	outHtml3();
?>
