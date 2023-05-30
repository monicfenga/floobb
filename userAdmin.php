<?php
include("common.php");

outHtml1("User Admin");
outHtml2("User Admin:", "editUser.php?userId=" . $_SESSION['user']->getUserId());
?>

<table class='list' style="margin-top: 10px; margin-bottom: 10px; font-family: Trebuchet MS;">

</table>

<form action="userAdminExecute.php" method="post">
	<table class='list'>
		<thead>
			<tr>
				<th class='listname'>Username</th>
				<th class='listlevel'>Level</th>
				<th class='listbanned'>Banned</th>
			</tr>
		</thead>
		<tbody>

			<?php

			$dir = dir("db/Users/");
			$count = 0;
			while ($filename = $dir->read()) {
				if ($filename != "." && $filename != "..") {
					$temp = new User(file_get_contents("db/Users/" . $filename));
					echo "<tr><td class='listname'>" . $temp->getUserId() . "</td>";
					echo "<td class='listlevel'><input type='text' name='" . $temp->getUserId() . "[]' value='" . $temp->getLevel() . "' /></td>";
					if ($temp->isBanned() == 'false') {
						$checked = "";
					} else {
						$checked = " checked";
					}
					echo "<td class='listlevel'><input type='checkbox' name='" . $temp->getUserId() . "[]' value='yes'" . $checked . " /></td></tr>";
					$count++;
				}
			}
			$dir->close();
			?>
		</tbody>

	</table>

	<div id="submitDiv">
		<input type="submit" value="Update" class="btn btn-primary full-width"/>
	</div>
</form>
<?php
outHtml3();
?>