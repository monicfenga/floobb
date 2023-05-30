<?php
	if (file_exists("install.php"))
	{
		header("Location: install.php");
	}
	include("common.php");
	
	outHtml1("Forum Index");

	outHtml2("Forum Index");
?>

	<div class="grid">
		<?php
			
			$forumArr = array();
			$fileC = file("db/forumList.dat",FILE_IGNORE_NEW_LINES);
			

			foreach ($fileC as $line)
			{
				array_push($forumArr, new Forum($line));
			}
			
			foreach ($forumArr as $count => $item)
			{
				$insertStr = "";
				if (isUserLoggedIn() && $_SESSION['user']->getLevel() == 3)
				{
					$insertStr .= "<span class='controls'>";
					if ($count == 0)
					{
						$insertStr = "<a class='btn btn-primary' href='forumListChange.php?mode=down&forumId=".$item->getForumId()."'>&darr;</a>";
					}
					else if ($count == count($forumArr)-1)
					{
						$insertStr = "<a class='btn btn-primary' href='forumListChange.php?mode=up&forumId=".$item->getForumId()."'>&uarr;</a>";
					}
					else
					{
						$insertStr = "<a class='btn btn-primary' href='forumListChange.php?mode=up&forumId=".$item->getForumId()."'>&uarr;</a>"
						."<a class='btn btn-primary' href='forumListChange.php?mode=down&forumId=".$item->getForumId()."'>&darr;</a> ";
					}
					$insertStr .= "<a class='btn btn-info' href='forumListEdit.php?forumId=".$item->getForumId()."'>edit</a>"
					."<a class='btn btn-error' href='forumListChange.php?mode=delete&forumId=".$item->getForumId()."'>delete</a>";
					$insertStr .= "</span>";

				}
				echo "<div class='cell cell-6'><div class='card'>"
				. "<div class='card-body'>"
				. "<h2>"
				."<a href='viewTopics.php?forumId=".$item->getForumId()."'>"
				.$item->getForumName()
				."</a>"
				."</h2>"
				."<p>"
				. $item->getDescription()
				. "</p>"
				. "<p>No. Posts: "
				. $item->getTotalPosts()
				. "</p>"
				. $insertStr
				. "</div>"
				. "</div>"
				. "</div>";
			}
			?>
			</div>
			<?php if (isUserLoggedIn() && $_SESSION['user']->getLevel() == 3): ?>
				<div class="text-center m-t-1 m-b-1">
					<a class="btn btn-primary" href="addForum.php">Add Forum</a>
				</div>
			
			<?php endif; ?>

		
		<?php
			$fileC = file("db/forumStatistics.dat",FILE_IGNORE_NEW_LINES);
			$fileD = file("db/forumOnlineUsers.dat",FILE_IGNORE_NEW_LINES);
			$fileE = file("db/forumOnlineGuests.dat",FILE_IGNORE_NEW_LINES);
		?>
		<div id="statistics" class="grid">
			<ul class="cell">
				<li>Forums: <?php echo $fileC[0]; ?></li>
				<li>Topics: <?php echo $fileC[1]; ?></li>
				<li>Posts: <?php echo $fileC[2]; ?></li>
				<li>Registered Users: <?php echo $fileC[3]; ?></li>
				<li>Newest User: <?php echo $fileC[4]; ?></li>
			</ul>
			<?php purge(); ?>
			<ul class="cell">
				<li>Online Users: <?= count($fileD)/2 ?></li>
				<li>Online Guests: <?= count($fileE)/2 ?></li>

				</ul>
		</div>
<?php
	outHtml3();
?>
