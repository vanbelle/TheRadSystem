<?php
if (isset($_POST['register'])) {
	header("Location: users.php");
	exit();
}
?>
<html>
	<body>
		<style>
			p.error {
				color: red;
			}
		</style>
		<script src="validate_form.js"> </script>
		<script>
		function checkPasswords() {
			var pwd1, pwd2;
			pwd = document.forms['registration']['pwd'].value;
			pwd2 = document.forms['registration']['pwd2'].value;
			
			if (pwd == pwd2) {
				return true;
			} else {
				show_message("Password fields don't match!");
				return false;
			}
		}
		</script>
	</head>
	<body>
		<form id="registration" method="post" action="register.php"
				onsubmit="return checkPasswords() && validateInput('registration')">
			<p>
				<p class="error" id="error_msg" hidden>Error: not hidden</p>
				<h2>Account Info</h2>
				<table>
				<tr>
					<td align="right">Username:</td><td>
					<input assertion="not_blank" type="text" name="user_name" accept-charset="UTF-8" maxlength="24" />
					</td>
				</tr>
				<tr>
					<td align="right">Password:</td><td><input assertion="not_blank" id="pwd" type="password" name="password" accept-charset="UTF-8" maxlength="24" /><td>
				</tr>
				<tr>
					<td align="right">Confirm Password:</td><td><input id="pwd2" type="password" name="confirm_password" accept-charset="UTF-8" maxlength="24" /><td>
				</tr>
			</table>
			</p>
			<p>
				<h2>Personal Details</h2>
				<table>
				<tr>
					<td align="right">First Name:</td><td>
					<input assertion="not_blank" type="text" name="first_name" accept-charset="UTF-8" maxlength="24" />
					</td>
				</tr>
				<tr>
					<td align="right">Last Name:</td><td>
					<input assertion="not_blank" type="text" name="last_name" accept-charset="UTF-8" maxlength="24"/>
					</td>
				</tr>
				<tr>
					<td align="right">Address:</td><td>
					<input assertion="not_blank" type="text" name="address" accept-charset="UTF-8" maxlength="128" />
					</td>
				</tr>
				<tr>
					<td align="right">Email:</td><td>
					<input assertion="not_blank" type="text" name="email" accept-charset="UTF-8" maxlength="128" />
					</td>
				</tr>
				<tr>
					<td align="right">Phone:</td><td>
					<input assertion="not_blank" type="text" name="phone" accept-charset="UTF-8" maxlength="10" />
					</td>
				</tr>
			</table>
			</p>
			<p>
				<input type="submit" name="register" value="Create" />
			</p>
		</form>
	</body>
</html>
