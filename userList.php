<?php
	include("common.php");
	
	outHtml1("User List");
	outHtml2("User List:","index.php");
?>

		<ul class='list'>
		<?php
		
			$dir=dir("db/Users/");
			while ($filename = $dir->read())
			{
				if ($filename != "." && $filename != "..")
				{
					$temp = new User(file_get_contents("db/Users/".$filename));
					echo "<li><a href='viewUser.php?userId=".$temp->getUserId()."'>".$temp->getUserId()."</a></li>";
				}
			}
			$dir->close();
		?>
		</ul>

<?php
	outHtml3();
?>
