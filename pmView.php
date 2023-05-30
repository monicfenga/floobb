<?php
include("common.php");

$fileC = file("db/PMs/" . $_SESSION['user']->getUserId() . ".dat", FILE_IGNORE_NEW_LINES);

$str = "";
$statusChange = false;
foreach ($fileC as $line) {
	$temp = new PM($line);
	if ($temp->getMessageId() == $_GET['messageId']) {
		$pm = $temp;
		if ($pm->isRead() == 'false') {
			$statusChange = true;
			$lineArr = explode("~", $line);
			$lineArr[4] = 'true';
			$str .= implode("~", $lineArr) . "\n";
		}
	} else {
		$str .= $line . "\n";
	}
}

if ($statusChange) {
	file_put_contents("db/PMs/" . $_SESSION['user']->getUserId() . ".dat", $str);
}

outHtml1("View Message");
outHtml2("View Message:", "pmInbox.php");
?>
<div class="grid">

	<label class="form-group cell cell-6">
		<span class="textboxes form-control"><?php echo "<a href='viewUser.php?userId=" . $pm->getSender()->getUserId() . "'>" . $pm->getSender()->getUserId() . "</a>" ?></span>
		<span class="form-label">Sender</span>
	</label>
	<label class="form-group cell cell-6">
		<span class="textboxes form-control"><?php echo $pm->getDate() ?></span>
		<span class="form-label">Date</span>
	</label>
	<label class="form-group cell cell-12">
		<span class="textboxes form-control"><?php echo $pm->getSubject() ?></span>
		<span class="form-label">Subject</span>
	</label>
	<label class="form-group cell cell-12">
		<span class="textboxes form-control"><?php echo $pm->getMessage() ?></span>
		<span class="form-label">Message</span>
	</label>
</div>

<div id="controlDiv">
	<a href="pmDelete.php?&messageId=<?php echo htmlentities($_GET['messageId']) ?>" class="btn btn-error">Delete</a>
</div>
<?php
outHtml3();
?>