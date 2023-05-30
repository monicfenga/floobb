<?php

	if (isUserLoggedIn())
	{
		$loginHeader = "<li><span class='sr-only'>
		Logged in as </span>
		<a href='editUser.php?userId=".$_SESSION['user']->getUserId()."'>".$_SESSION['user']->getUserId()."</a></li>";
		$fileC = file("db/PMs/".$_SESSION['user']->getUserId().".dat");
			
		$messageNo = 0;
		foreach ($fileC as $line)
		{
			$temp = new PM($line);
			if ($temp->isRead() == 'false')
			{
				$messageNo++;
			}
		}
		if ($messageNo > 0)
		{
			$inboxStr = " (".$messageNo.")";
		}
		else
		{
			$inboxStr = "";
		}
		$loginHeader .= "<li><a href='pmInbox.php'>Inbox".$inboxStr."</a></li>";
		$loginHeader .= '<li><a href="logout.php">Logout</a></li>';
	}
	else
	{
		$loginHeader = '<li><a href="login.php">Login</a> or <a href="register.php">Register</a></li>';
	}
?>
