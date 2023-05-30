<?php
include("common.php");
include_once("class.Poll.php");

$topic = new Topic(file_get_contents("db/Topics/" . $_GET['topicId'] . "/topic.dat"));

outHtml1($topic->getTopicName());
?>

<?php call_editor(); ?>
<script type="text/javascript">
	function addQuote(row) {
		/*
		var str = document.getElementById("tbl").rows[row].cells[2].innerHTML;
		if (str.lastIndexOf("<div class=") == -1) {
			str = str.substring(str.indexOf("Quote</a") + 9);
		} else {
			str = str.substring(str.indexOf("Quote</a") + 9, str.lastIndexOf("<div class="));
		}
		return "<blockquote class='quote'><span style='font-size: 12px; font-weight: bold;'>Quote: " + document.getElementById("tbl").rows[row].cells[0].firstChild.innerHTML + "</span><hr />" + str + "</blockquote>";
		*/
	}
</script>

<?php
outHtml2(/*$_SESSION['forum']->getForumName() . '' . */$topic->getTopicName(), "viewTopics.php?forumId=" . $_SESSION['forum']->getForumId());

if (isUserLoggedIn()) {
	if ($_SESSION['user']->getLevel() > 1) {
		echo "<div id='adminControls' class='m-t-1 m-b-1'>Moderator Controls: ";
		if ($topic->isLocked() == "false") {
			echo "<a class='btn btn-warning' href='lockExecute.php?mode=lock&topicId=" . htmlentities($_GET["topicId"]) . "'>Lock</a> ";
		} else {
			echo "<a class='btn btn-warning btn-ghost' href='lockExecute.php?mode=unlock&topicId=" . htmlentities($_GET["topicId"]) . "'>Unlock</a> ";
		}
		if ($topic->isSticky() == "false") {
			echo "<a class='btn btn-info' href='stickyExecute.php?mode=sticky&forumId=" . $_SESSION['forum']->getForumId() . "&topicId=" . htmlentities($_GET["topicId"]) . "'>Sticky</a> ";
		} else {
			echo "<a class='btn btn-info btn-ghost' href='stickyExecute.php?mode=unsticky&forumId=" . $_SESSION['forum']->getForumId() . "&topicId=" . htmlentities($_GET["topicId"]) . "'>Unsticky</a> ";
		}
		echo "</div>";
	}
}
?>

<?php

$tpId = $_GET["topicId"];

$postArr = array();

$fileC = file("db/Topics/" . $tpId . "/posts.dat");

foreach ($fileC as $line) {
	array_push($postArr, new Post($line));
}

if (file_exists("db/Topics/" . $tpId . "/poll.dat")) {
	$poll = new Poll(file_get_contents("db/Topics/" . $tpId . "/poll.dat"));
	echo "<div id='pollDiv' class='card grid'>";
	$sum = 0;
	$str = "";
	$optionArr = $poll->getOptions();
	foreach ($optionArr as $option) {
		$sum += $option[1];
		$str .= $option[1] . ",";
	}
	$str = substr($str, 0, strlen($str) - 1);

	if ($sum == 0) {
		echo "<div class='card-header cell'>No votes yet.</div>";
	} else {
		echo "<div class='card-header cell cell-2'><img src='createImage.php?values=" . $str . "' /></div>";
		$color = array(
			"#5B8FF9",
			"#61DDAA",
			"#65789B",
			"#F6BD16",
			"#7262fd",
			"#78D3F8",
			"#9661BC",
			"#F6903D",
			"#008685",
			"#F08BB4",
		);
		echo '<div class="card-body cell"><ol>';
		for ($i = 0; $i < count($optionArr); $i++) {
			echo "<li><span class='tag' style='--bg-color: " . $color[$i] . "'>" . $optionArr[$i][0] . ": " . $optionArr[$i][1] . "</span></li>";
		}
		echo '</ol></div>';
	}

	if ((isset($_SESSION['loggedIn']) && $_SESSION['loggedIn']) && (time() < strtotime($poll->getEndDate()) || $poll->getEndDate() == "") && $topic->isLocked() == 'false') {
		if (!in_array($_SESSION['user']->getUserId(), $poll->getUsers())) {
			echo "<div id='options' class='card-body cell'><form name='poll' action='voteExecute.php?topicId=" . $tpId . "' method='post'>";
			for ($i = 0; $i < count($optionArr); $i++) {
				echo "<label><input type='radio' name='option' value='$i' /> " . $optionArr[$i][0] . "</label><br>";
			}
			echo "<input class='btn btn-primary' type='submit' value='Vote' />";
			echo "</form></div>";
		}
	}
	echo "</div>";
}

echo "<div id='tbl' class='list'>";
$count = 0;
foreach ($postArr as $key => $item) {
	$count++;
	$sig = "";
	if (trim($item->getUser()->getSig()) != "") {
		$sig = "<div class='sig dashed-top'>" . trim($item->getUser()->getSig()) . "</div>";
	}
	$avatarStr = "";
	if ($item->getUser()->getAvatar() != "") {
		$avatarStr = "<img src='" . $item->getUser()->getAvatar() . "' />";
	}
	$deleteStr = "";
	if (isUserLoggedIn() && $_SESSION['user']->getLevel() > 1 && count($postArr) > 1) {
		$deleteStr = " <a class='tag tag-error' href='moderate.php?flag=post&postId=" . $item->getPostId() . "&topicId=" . htmlentities($_GET['topicId']) . "'>Delete</a>";
	}

	if (isUserLoggedIn() && $_SESSION['user']->getLevel() > 1) {
		$deleteStr .= " <a class='tag tag-info' href='editPost.php?postId=" . $key . "&topicId=" . htmlentities($_GET['topicId']) . "'>Edit</a>";
	}
	if (isUserLoggedIn()) {
		$deleteStr .= ' <a class="tag tag-primary" href="javascript:;" onClick="tinyMCE.execCommand(\'mceInsertContent\',false,addQuote(' . $key . '));">Quote</a>';
	}
	echo "<div class='form-group'>"
		. "<div class='listmessage form-control'>"
		. '<div class="grid-inline grid-between">'
		. '<time>'
		. $item->getDateTime()
		. '</time>'
		. '<div>'
		. $deleteStr
		. "</div>"
		. "</div>"
		. '<div>'
		. $item->getMessage()
		. $sig
		. "</div>"
		. "</div>"
		. "<div class='listuser form-label'>"
		. "<a href='viewUser.php?userId=" . $item->getUser()->getUserId() . "'>" . $item->getUser()->getUserId() . "</a>"
		. $avatarStr
		. "</div>"
		. "</div>";

	$count = $item->getPostId() + 1;
}
echo "</div>";
?>

<?php
if (isUserLoggedIn() && $topic->isLocked() == "false") {
?>
	<form action="postExecute.php?topicId=<?php echo htmlentities($_GET['topicId']) . "&postId=" . $count; ?>" method="post" onsubmit="return verify(this);">
		<div id='replyId'>
			<label class="form-group">
				<textarea id="reply" name="reply" class="textboxes form-control"></textarea>
				<span class="form-label">Post Reply:</span>
				<div id="imageInfo" class="help-block">Images may be no bigger than 600 x 600 and 200kB.</div>
			</label>
			<?php
			if (isset($_GET['error']) && $_GET['error'] == 1) {
				echo "<div class='alert alert-error'>Please enter a post!</div>";
			}
			?>
			<input type='submit' value='Reply' id="submitId" class="btn btn-primary full-width" />
		</div>
	</form>
<?php
}
outHtml3();
?>