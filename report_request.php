<?php 	
/**
 * This file accepts a diagnosis and a year and passes them on to 
 * report_generated.php so that a report can be generated 
 * It also provides a link back to the home page and produces
 * an error if the user is not an admin 
 **/

require('session.php'); 
if (getUserClass() != "a") {
	echo 'No Access To This Page';
	?><p>	<a href="search.php">Home</a></p> <?php
} else {
?>

<!DOCTYPE html>
<html>
	<head>
		<title>Request a Report</title>
	</head>
	<body>
		<form id="reportRequest" action="report_generated.php" method="post">
			<p><a href="search.php">Home</a></p>
			<div>
				<p class="header">
					Report Request
				</p>
				<?php echo "<p id=\"message\" class=\"$msg_class\">$message</p>"
				?>
			</div>
			<table>
				<tr>
					<td class="prompt_field">Diagnosis:</td>
					<td class="field">
					<input type="text" name="diagnosis" />
					</td>
				</tr>
				<tr>
					<td class="prompt_field">Year:</td>
					<td class="field">
					<input type="year" name="year" />
					</td>
				</tr>
			</table>
			<div id="submit">
				<input type="submit" name="request" value="Submit"/>
			</div>
		</form>
		</div>
	</body>
</html>
<?php
}
?>