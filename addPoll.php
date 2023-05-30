<?php
	include("common.php");
	outHtml1("Add Poll");
?>

<script>
	function add()
	{
		if (document.getElementById("table").rows.length < 12)
		{
			//alert("asdgsdg");
			var row = document.getElementById("table").insertRow(-1);
			var cell = row.insertCell(-1);
			cell.className = "tblleft";
			var text = document.createElement("span");
			text.appendChild(document.createTextNode("Option "));
			cell.appendChild(text);
			var delButton = document.createElement("input");
			delButton.type = "button";
			delButton.name = "delButton";
			delButton.setAttribute("value","Delete");
			delButton.setAttribute("onClick","del("+(document.getElementById("table").rows.length-1)+");");
			delButton.className = "btn btn-error btn-ghost"
			delButton.id = document.getElementById("table").rows.length-1;
			cell.appendChild(delButton);
			var cell = row.insertCell(-1);
			cell.className = "tblright";
			var inputBox = document.createElement("input");
			inputBox.type = "text";
			inputBox.className = 'form-control';
			inputBox.name = "option[]";
			cell.appendChild(inputBox);
		}
	}
	
	function del(num)
	{
		document.getElementById("table").deleteRow(num);
		for (i = 2; i < document.getElementById("table").rows.length; i++)
		{
			document.getElementById("table").rows[i].cells[0].lastChild.setAttribute("onClick","del("+i+");");
		}
	}
</script>
<?php
	outHtml2("Add Poll:", "viewTopics.php?forumId=".$_GET['forumId']);
?>
		
		<form action="addPollExecute.php?forumId=<?php echo $_GET['forumId']?>" method="post">
			<table class="tbl" id="table">
				<tr>
					<td class="tblleft" style="width:30%">Poll Question</td>
					<td class="tblright" style="width:70%"><input class="form-control" type="text" name="pollQuestion"/></td>
				</tr>
				<tr>
					<td class="tblleft">Number Of Days (0 is forever)</td>
					<td class="tblright"><input class="form-control" type="number" min="0" max="3650" step="1" name="pollLength" value="0" /></td>
				</tr>
				<tr>
					<td class="tblleft">Option <input type="button" class="btn btn-error btn-ghost" name="delButton" id="2" value="Delete" onClick="del(2)" /></td>
					<td class="tblright"><input class="form-control" type="text" name="option[]" /></td>
				</tr>
				<tr>
					<td class="tblleft">Option <input type="button" class="btn btn-error btn-ghost" name="delButton" id="3" value="Delete" onClick="del(3)" /></td>
					<td class="tblright"><input class="form-control" type="text" name="option[]" /></td>
				</tr>
			</table>
			<?php
				if (isset($_GET['error']) && $_GET['error'] == 1)
				{
					echo "<div class='error'>Please enter a poll question!</div>";
				}
				else if (isset($_GET['error']) && $_GET['error'] == 2)
				{
					echo "<div class='error'>Please fill in all the visible options.</div>";
				}
			?>
			<div class="buttons">
				<input class="btn btn-success" type="button" name="addOption" value="Add Option" onClick="add();" />
				<input class="btn btn-primary" type="submit" name="submit" value="Add Poll" />
			</div>
		</form>
<?php
	outHtml3();
?>
