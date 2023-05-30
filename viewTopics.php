<?php
include("common.php");

$fileC = file("db/forumList.dat");

foreach ($fileC as $line) {
	$temp = new Forum($line);
	if ($temp->getForumId() == $_GET['forumId']) {
		$forum = $temp;
	}
}
$_SESSION['forum'] = $forum;

outHtml1($forum->getForumName());
outHtml2($forum->getForumName(), "index.php");
?>

<div class="card">
	<div class="card-body">
		<?= $forum->getDescription(); ?>
	</div>
</div>


<?php if (isUserLoggedIn()) : ?>
	<div class=" m-t-1 m-b-1">
		<a class="btn btn-primary" href="addTopic.php?forumId=<?php echo htmlentities($_GET['forumId']) ?>">Add Topic</a>
		<a class="btn btn-info" href="addPoll.php?forumId=<?php echo htmlentities($_GET['forumId']) ?>">Add Poll</a>
	</div>
<?php endif; ?>
<?php

$frId = $_GET['forumId'];
$topicsperpage = trim(file_get_contents("db/topicsperpage.dat"));

if (!isset($_GET['page'])) {
	$pageNo = 0;
} else {
	$pageNo = $_GET['page'];
}

$topicArr = array();

$fh = fopen("db/Topics/" . $frId . ".dat", "r");

for ($i = 0; $i < $pageNo * $topicsperpage; $i++) {
	fgets($fh);
	fgets($fh);
}
$count = 0;
while ($count < $topicsperpage && !feof($fh)) {
	$count++;
	$a = trim(fgets($fh));
	$b = trim(fgets($fh));
	if ($a != "" && $b != "") {
		array_push($topicArr, new Topic(file_get_contents("db/Topics/" . $a . "/topic.dat")));
	}
}

fclose($fh);

$stickyTopicArr = array();
$fh = fopen("db/Topics/" . $frId . "sticky.dat", "r");

while (!feof($fh)) {
	$a = trim(fgets($fh));
	$b = trim(fgets($fh));
	if ($a != "" && $b != "") {
		array_push($stickyTopicArr, new Topic(file_get_contents("db/Topics/" . $a . "/topic.dat")));
	}
}

fclose($fh);
if (!empty($stickyTopicArr)) {

	echo "<div class='grid'>";
	foreach ($stickyTopicArr as $item) {
		$deleteStr = "";
		if (isUserLoggedIn() && $_SESSION['user']->getLevel() > 1) {
			$deleteStr = "<a class='btn btn-primary' href='editTopic.php?topicId=" . $item->getTopicId() . "'>edit</a>"
				. "<a class='btn btn-info' href='moveTopic.php?topicId=" . $item->getTopicId() . "&forumId=" . htmlentities($_GET['forumId']) . "'>move</a>"
				. "<a class='btn btn-error' href='moderate.php?flag=topic&forumId=" . htmlentities($_GET['forumId']) . "&topicId=" . $item->getTopicId() . "'>delete</a>";
		}
		echo '<div class="cell cell-6">'
			. '<div class="card">'
			. '<div class="card-body">'
			. '<h2>'
			. "<a href='viewPosts.php?topicId=" . $item->getTopicId() . "'>Sticky: " . $item->getTopicName() . "</a>"
			. '</h2>'
			. '<ul>'
			. "<li> By <a href='viewUser.php?userId=" . $item->getUser()->getUserId() . "'>" . $item->getUser()->getUserId() . "</a>" . '</li>'
			. '<li> Posts: ' . $item->getTotalPosts() . '</li>'
			. '<li> Last Post: ' . $item->getLatestPost()->getDateTime() . '</li>'
			. "<li> Created: " . $item->getDateTime() . '</li>'
			. '</ul>'
			. $deleteStr
			. '</div>'
			. '</div>'
			. '</div>';

		/*
				echo "<tr><td class='stickylistname'><a href='viewPosts.php?topicId=".$item->getTopicId()."'>Sticky: ".$item->getTopicName()."</a>".$deleteStr."</td>
					<td class='stickylistcreator'><a href='viewUser.php?userId=".$item->getUser()->getUserId()."'>".$item->getUser()->getUserId()."</a>
					<td class='stickylistposts'>".$item->getTotalPosts()."</td>
					<td class='stickylistdate'>".$item->getLatestPost()->getDateTime()."<br />".$item->getDateTime()."</td>
					</tr>";
					*/
	}
	echo "</div>";
	echo "<hr>";
}

echo "<div class='grid'>";

foreach ($topicArr as $item) {
	$deleteStr = "";
	if (isUserLoggedIn() && $_SESSION['user']->getLevel() > 1) {
		$deleteStr = "<a class='btn btn-primary' href='editTopic.php?topicId=" . $item->getTopicId() . "'>edit</a>"
			. "<a class='btn btn-info' href='moveTopic.php?topicId=" . $item->getTopicId() . "&forumId=" . htmlentities($_GET['forumId']) . "'>move</a>"
			. "<a class='btn btn-error' href='moderate.php?flag=topic&forumId=" . htmlentities($_GET['forumId']) . "&topicId=" . $item->getTopicId() . "'>delete</a>";
	}
	echo '<div class="cell cell-6">'
		. '<div class="card">'
		. '<div class="card-body">'
		. '<h2>'
		. "<a href='viewPosts.php?topicId=" . $item->getTopicId() . "'>" . $item->getTopicName() . "</a>"
		. '</h2>'
		. '<ul>'
		. "<li> By <a href='viewUser.php?userId=" . $item->getUser()->getUserId() . "'>" . $item->getUser()->getUserId() . "</a>" . '</li>'
		. '<li> Posts: ' . $item->getTotalPosts() . '</li>'
		. '<li> Last Post: ' . $item->getLatestPost()->getDateTime() . '</li>'
		. "<li> Created: " . $item->getDateTime() . '</li>'
		. '</ul>'
		. $deleteStr
		. '</div>'
		. '</div>'
		. '</div>';
	/*
				echo "<tr><td class='listtopicname'><a href='viewPosts.php?topicId=".$item->getTopicId()."'>".$item->getTopicName()."</a>".$deleteStr."</td>
					<td class='listtopiccreator'><a href='viewUser.php?userId=".$item->getUser()->getUserId()."'>".$item->getUser()->getUserId()."</a>
					<td class='listtopicposts'>".$item->getTotalPosts()."</td>
					<td class='listtopicdate'>".$item->getLatestPost()->getDateTime()."<br />".$item->getDateTime()."</td>
					</tr>";
					*/
}
echo "</div>";


?>

<div class="text-center m-t-1 m-b-1">

	Page:
	<?php
	if ($forum->getTotalTopics() <= $topicsperpage) {
		echo "1";
		$controlsStr = "";
	} else if ($forum->getTotalTopics() <= 2 * $topicsperpage) {
		if ($pageNo == 0) {
			echo "1 <a href='viewTopics.php?forumId=" . htmlentities($_GET['forumId']) . "&page=1'>2</a>";
			$controlsStr = "<a href='viewTopics.php?forumId=" . htmlentities($_GET['forumId']) . "&page=1'>Next</a>";
		} else {
			echo "<a href='viewTopics.php?forumId=" . htmlentities($_GET['forumId']) . "&page=0'>1</a> 2";
			$controlsStr = "<a href='viewTopics.php?forumId=" . htmlentities($_GET['forumId']) . "&page=0'>Prev</a>";
		}
	} else {
		$maxPage = ceil($forum->getTotalTopics() / $topicsperpage) - 1;
		if ($pageNo == 0) {
			echo "1 ... <a href='viewTopics.php?forumId=" . htmlentities($_GET['forumId']) . "&page=" . $maxPage . "'>" . ($maxPage + 1) . "</a>";
			$controlsStr = "<a href='viewTopics.php?forumId=" . htmlentities($_GET['forumId']) . "&page=" . ($pageNo + 1) . "'>Next</a>";
		} else if ($pageNo == $maxPage) {
			echo "<a href='viewTopics.php?forumId=" . htmlentities($_GET['forumId']) . "&page=0'>1</a> ... " . ($maxPage + 1);
			$controlsStr = "<a href='viewTopics.php?forumId=" . htmlentities($_GET['forumId']) . "&page=" . ($pageNo - 1) . "'>Prev</a>";
		} else {
			echo "<a href='viewTopics.php?forumId=" . htmlentities($_GET['forumId']) . "&page=0'>1</a> ... " . ($pageNo + 1) . " ... <a href='viewTopics.php?forumId=" . htmlentities($_GET['forumId']) . "&page=" . $maxPage . "'>" . ($maxPage + 1) . "</a>";
			$controlsStr = "<a href='viewTopics.php?forumId=" . htmlentities($_GET['forumId']) . "&page=" . ($pageNo - 1) . "'>Prev</a> <a href='viewTopics.php?forumId=" . htmlentities($_GET['forumId']) . "&page=" . ($pageNo + 1) . "'>Next</a>";
		}
	}

	echo $controlsStr;

	?>
</div>


<?php
outHtml3();
?>