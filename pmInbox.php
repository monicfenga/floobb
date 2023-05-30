<?php
include("common.php");

outHtml1("Inbox");
outHtml2("Inbox", "index.php");
?>

<div class='grid'>
	<?php

	$fileC = file("db/PMs/" . $_SESSION['user']->getUserId() . ".dat");
	$messageArr = array();
	foreach ($fileC as $line) {
		if (trim($line) != "") {
			array_push($messageArr, new PM($line));
		}
	}
	if (count($messageArr) > 0) {
		$messageArr = array_reverse($messageArr);
		foreach ($messageArr as $item) {

			echo '<div class="cell cell-6">'
				.'<div class="alert ' . ($item->isRead() == 'false' ? 'alert-info' : '') . '">'
				. "<h2><a href='pmView.php?messageId=" . $item->getMessageId() . "'>" . $item->getSubject() . "</a></h2>"
				.'<div class="card-body">'
				. '<ul>'
				. "<li>From <a href='viewUser.php?userId=".$item->getSender()->getUserId()."'>".$item->getSender()->getUserId()."</a></li>"
				. '<li><date>' . $item->getDate() . '</date></li>'
				. '</ul>'
				. '</div>'
				. '</div>'
				. '</div>';
		}
	} else {
		echo "<div class='cell'>No messages.</div>";
	}
	?>
</div>


<div><a href="userList.php" id="regUsers" class="btn btn-link">User List</a></div>

<?php
outHtml3();
?>