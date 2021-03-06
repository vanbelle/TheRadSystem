<!DOCTYPE html>
<html>
	<head>
		<title>Request an OLAP Report</title>
	</head>
	<body>
		<form id="olapRequest" action="OLAP_report.php" method="post">
			<p><a href="search.php">Home</a></p>
			<div>
				<p class="header">
					<b>Data Analysis Request</b>
					<br/>
				</p>
			</div>
			<table>
				<tr>
					<td class="prompt_field">Patient:</td>
					<td class="field">
					<input type="text" name="patient" value="All"/>
					</td>
				</tr>
				<tr>
					<td class="prompt_field">Test Type:</td>
					<td class="field">
					<input type="text" name="test" value="All" />
					</td>
				</tr>
			</table>
			<p class="prompt_field">Time Period: 
			<select name="time_period">
				<option value="All">All</option>
				<option value="Year">Year</option>
				<option value="Month">Month</option>
				<option value="Week">Week</option>
			</select></p>
			<p class="prompt_field">(Year: 
			<select name="year">
				<option value="0">All</option>
				<option value="2000">2000</option><option value="2001">2001</option><option value="2002">2002</option>
				<option value="2003">2003</option><option value="2004">2004</option><option value="2005">2005</option><option value="2006">2006</option>
				<option value="2007">2007</option><option value="2008">2008</option><option value="2009">2009</option>
				<option value="2010">2010</option><option value="2011">2011</option><option value="2012">2012</option>
				<option value="2013">2013</option><option value="2014">2014</option><option value="2015">2015</option>
			</select>
			Month: 
			<select name="month">
				<option value="0">All</option>
				<option value="1">Jan</option><option value="2">Feb</option><option value="3">Mar</option>
				<option value="4">Apr</option><option value="5">May</option><option value="6">Jun</option>
				<option value="7">Jul</option><option value="8">Aug</option><option value="9">Sept</option>
				<option value="10">Oct</option><option value="11">Nov</option><option value="12">Dec</option>
			</select>
			<?php echo ')'; ?></p>
			<div id="submit">
				<input type="submit" name="orequest" value="Submit"/>
			</div>
		</form>


		<?php
		/**
		 * This program prints a data analyis for any combination of patient, test type and time period
		 * It prints an error if the user is not an admin and has a link back to the home page for 
		 * when the user is finished.
		 */
			require ('session.php');
			//user needs to be an admin
			if (getUserClass() != "a") {
				echo 'No Access To This Page';?>
				<p>
					<a href="search.php">Home</a>
				</p>
		<?php
			} else {
				//initialize all the posted variables
				if (isset($_POST['orequest'])) {
					$patient = $_POST['patient'];
					$test = $_POST['test'];
					$time = $_POST['time_period'];
					$year = $_POST['year'];
					$month = (int) $_POST['month'];
					$AllTests = array();
					$AllPatients = array();
					$PatientIds = array();
					$months = array('all','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sept','Oct','Nov','Dec');
					$weeks = array('Week 1','Week 2','Week 3','Week 4','Week 5');
					//check that the variables needed to drill down were filled in
					if ($time == "Month" && $year == 0) {
						echo 'Input error: must provide a year to search by month';
					} else if ($time == "Week" && ($year == 0 || $month == 0)) {
						echo 'Input error: must provide a year and a month to search by week';
					} else {
						//get a database connection
						require_once ('_database.php');				
						//get all possible tests
						$query = "SELECT distinct test_type FROM radiology_record";
						//get a database response
						$statement = oci_parse($connection, $query);
						$results = oci_execute($statement);
						//put the tests in an array
						if ($results) {
							oci_fetch_array($statement, OCI_BOTH);
							while (($row = oci_fetch_array($statement, OCI_BOTH)) != false) {
								$AllTests[] = $row[0];
							}
						} else {
							echo "Could not get database query 1<br />";
						}
						//get all possible patients
						$query = "SELECT distinct first_name, last_name, person_id FROM persons";
						//get a database response
						$statement = oci_parse($connection, $query);
						$results = oci_execute($statement);
						//put the patients and ids in an array
						if ($results) {
							while (($row = oci_fetch_array($statement, OCI_BOTH)) != false) {
								$AllPatients[] = $row[0].' '.$row[1];
								$PatientIds[] = $row[2];
							}
						} else {
							echo "Could not get database query 2<br />";
						}
						//create a table with the attributes being searched and being searched by
						$query = "CREATE TABLE joined AS (SELECT p.person_id AS pid, r.test_type AS test, i.image_id AS image, r.test_date AS tdate FROM persons p, radiology_record r, pacs_images i 
						WHERE p.person_id = r.patient_id AND r.record_id = i.record_id)";	
						//get a database response
						$statement = oci_parse($connection, $query);
						$results = oci_execute($statement);
						if(!$results) {
							echo "Could not get database query 3<br />";
						}
						//if the user provided no specifications => print all recorded image numbers
						if($time == 'All'&& $patient == 'All' && $test == 'All'){
							//Print the table headers
							echo '<table align="left"
								border = "1" cellspace="5" cellpadding="8">
								<tr><td align="left"><b>Patient       </b></td>';
							foreach($AllTests as $test_type){
								echo '<td align="left"><b>' . $test_type . '</b></td>';
							}
							echo '</tr>';
							//for each patient
							for ($int = 0; $int < count($AllPatients); $int++){
								echo '<td align="left">'.$AllPatients[$int].'</td>';
								//and for each test_type
								 foreach($AllTests as $test_type){
									//create the query to get the corresponding image counts
									$query = "SELECT COUNT(DISTINCT image) FROM joined WHERE pid = '$PatientIds[$int]' AND test = '$test_type'";
									//get a database response
									$statement = oci_parse($connection, $query);
									$results = oci_execute($statement);
									//put the count in the table
									$row = oci_fetch_array($statement, OCI_BOTH);
									if ($results) {
										if($row == false){
											echo '<td align="left"> 0 </td>';
										} else {
											echo '<td align="left">' . $row[0] . '</td>';
										}
									} else {
										echo "Could not get database query 2<br />";
									}
								}echo '</tr>';
							}echo '<table/>';
						//If the user provided a time period to search by
						} else if($time != 'All'&& $patient  == 'All' && $test == 'All'){
							foreach($AllTests as $test){
								echo '<b>For Test Type '.$test.'<b><br/>';
								//if that time period was year
								if ($time == 'Year'){
									//print table headers "Year" and each patient name
									echo '<table align="left"
									border = "1" cellspace="5" cellpadding="8">
									<tr><td align="left"><b>Year</b></td>';
									for ($int = 0;$int < count($AllPatients);$int++){
										echo '<td align="left"><b>'.$AllPatients[$int].'</b></td>';
									}
									echo'</tr>';
									//for each year/patient combination find and print the number of images
									for ($y = 2000; $y < 2016; $y++){
										echo '<tr><td align="left"><b>'.$y.'</b></td>';
										for ($int = 0;$int < count($AllPatients);$int++){
											//create the query to get the corresponding image counts
											$query = "SELECT COUNT(DISTINCT image) FROM joined WHERE pid = '$PatientIds[$int]' AND test = '$test' AND EXTRACT(YEAR FROM tdate) = '$y'";
											//get a database response
											$statement = oci_parse($connection, $query);
											$results = oci_execute($statement);
											//put the count in the table
											$row = oci_fetch_array($statement, OCI_BOTH);
											if ($results) {
												if($row == false){
													echo '<td align="left"> 0 </td>';
												} else {
													echo '<td align="left">' . $row[0] . '</td>';
												}
											} else {
												echo "Could not get database query 2<br />";
											}
										}
										echo '</tr>';
									}
									echo '<table/>';
								//if that time period was months
								} else if ($time == 'Month') {
									//print table headers month of year and each patient name
									echo '<table align="left"
									border = "1"
									cellspace="5" cellpadding="8">
									<tr><td align="left"><b>Month of '.$year.'</b></td>';
									//print patient names
									for ($int = 0;$int < count($AllPatients);$int++){
										echo '<td align="left"><b>'.$AllPatients[$int].'</b></td>';
									}
									//print the months
									for ($m = 1; $m < 13; $m++){
										echo '<tr><td align="left"><b>'.$months[$m].'</b></td>';
										for ($int = 0; $int < count($AllPatients);$int++){	
											//create the query to get the corresponding image counts
											$query = "SELECT COUNT(DISTINCT image) FROM joined WHERE pid = '$PatientIds[$int]' AND test = '$test' AND EXTRACT(MONTH FROM tdate) = '$m' AND EXTRACT (YEAR FROM tdate) = '$year'";
											//get a database response
											$statement = oci_parse($connection, $query);
											$results = oci_execute($statement);
											//put the count in the table
											$row = oci_fetch_array($statement, OCI_BOTH);
											if ($results) {
												if($row == false){
													echo '<td align="left"> 0 </td>';
												} else {
													echo '<td align="left">' . $row[0] . '</td>';
												}
												
											} else {
												echo "Could not get database query 2<br />";
											}	
										}echo '</tr>';
									}echo '<table/>';
									//if the user asked for weeks
								} else {
									//print the table headers
									echo '<table align="left"
									border = "1"
									cellspace="5" cellpadding="8">
									<tr><td align="left"><b>Week of '.$months[$month].' '.$year.'</b></td>';
									//print the patient names
									for ($int = 0;$int < count($AllPatients);$int++){
										echo '<td align="left"><b>'.$AllPatients[$int].'</b></td>';
									}
									for ($w = 0; $w < 5; $w++){
										echo '<tr><td align="left"><b>'.$weeks[$w].'</b></td>';
										//create the query to get the corresponding image counts
										for($int = 0; $int < count($AllPatients);$int++){
											$query = "SELECT COUNT(DISTINCT image) FROM joined WHERE pid = '$PatientIds[$int]' AND test = '$test' AND EXTRACT (DAY FROM tdate) <= ('$w' + 1)*7 AND EXTRACT(DAY FROM tdate) > '$w'*7 
											AND EXTRACT(MONTH FROM tdate) = '$month' AND EXTRACT (YEAR FROM tdate) = '$year'";
											//get a database response
											$statement = oci_parse($connection, $query);
											$results = oci_execute($statement);
											//put the count in the table
											$row = oci_fetch_array($statement, OCI_BOTH);
											if ($results) {
												if($row == false){
													echo '<td align="left"> 0 </td>';
												} else {
													echo '<td align="left">' . $row[0] . '</td>';
												}
											} else {
												echo "Could not get database query 2<br />";
											}	
										}echo '</tr>';
									}
									echo '<table/>';
								}
							}
						//if the user wants to see the counts for a particular patient
						} else if($time == 'All'&& $patient  != 'All' && $test == 'All'){
							//print the table headers 1 patient and all the tests
							echo '<table align="left"
								border = "1"
								cellspace="5" cellpadding="8">
								<tr><td align="left"><b>Test Type</b></td>';
							//find the patient id for the given patient
							$int = 0;
							for ($int; $int < count($AllPatients); $int++){
								if ($patient == $AllPatients[$int]) {
									echo '<td align="left"><b>'.$AllPatients[$int].'<b></td></tr>';
									break;
								}
							}
							//for each test type for the given patient find the count
							foreach($AllTests as $test_type){
								echo '<tr><td align="left">'.$test_type.'</td>';
								//the corresponding counts
								$query = "SELECT COUNT(DISTINCT image) FROM joined WHERE pid = '$PatientIds[$int]' AND test = '$test_type'";
								//get a database response
								$statement = oci_parse($connection, $query);
								$results = oci_execute($statement);
								//put the count in the table
								$row = oci_fetch_array($statement, OCI_BOTH);
								if ($results) {
									if($row == false){
										echo '<td align="left"> 0 </td>';
									} else {
										echo '<td align="left">' . $row[0] . '</td>';
									}
								} else {
									echo "Could not get database query 2<br />";
								}	
								echo '<tr/>';
							}
							echo '<table/>';
							//if the user wants the counts for a particular test
						} else if($time == 'All'&& $patient == 'All' && $test != 'All'){
							//print the table headers
							echo '<table align="left"
								border = "1"
								cellspace="5" cellpadding="8">
								<tr><td align="left"><b>Patient       </b></td>';
							echo '<td align="left"><b>' . $test . '</b></td></tr>';
							//for each patient get the counts for the corresponding test
							for ($int = 0; $int < count($AllPatients); $int++){
								echo '<td align="left">'.$AllPatients[$int].'</td>';
								//the corresponding counts
								$query = "SELECT COUNT(DISTINCT image) FROM joined WHERE pid = '$PatientIds[$int]' AND test = '$test'";
								//get a database response
								$statement = oci_parse($connection, $query);
								$results = oci_execute($statement);
								//put the count in the table
								$row = oci_fetch_array($statement, OCI_BOTH);
								if ($results) {
									if($row == false){
										echo '<td align="left"> 0 </td>';
									} else {
										echo '<td align="left">' . $row[0] . '</td>';
									}
								} else {
									echo "Could not get database query 2<br />";
								}	
								echo '</tr>';
							}
							echo '<table/>';
							//if the user wants the counts for a particulat time period and patient
						} else if($time != 'All'&& $patient  != 'All' && $test == 'All') {
							echo '<b>For Patient '.$patient.' <b></br>';
							$int = 0;
							//find the patient id
							for ($int; $int < count($AllPatients); $int++){
								if ($patient == $AllPatients[$int]) {
									break;
								}
							}
							//if the user wanted to see a yearly time period 
							//create the table headers "year" and each test type
							if ($time == 'Year'){
								echo '<table align="left"
								border = "1"
								cellspace="5" cellpadding="8">
								<tr><td align="left"><b>Year</b></td>';
								foreach ($AllTests as $test){
									echo '<td align="left"><b>'.$test.'</b></td>';
								}
								echo '</tr>';
								//find the counts for each year and test type
								for ($y = 2000; $y < 2016; $y++){
									echo '<tr><td align="left"><b>'.$y.'</b></td>';
									foreach ($AllTests as $test){
										//create the query to get the corresponding image counts
										$query = "SELECT COUNT(DISTINCT image) FROM joined WHERE pid = '$PatientIds[$int]' AND test = '$test' AND EXTRACT(YEAR FROM tdate) = '$y'";
										//get a database response
										$statement = oci_parse($connection, $query);
										$results = oci_execute($statement);
										//put the count in the table
										$row = oci_fetch_array($statement, OCI_BOTH);
										if ($results) {
											if($row == false){
												echo '<td align="left"> 0 </td>';
											} else {
												echo '<td align="left">' . $row[0] . '</td>';
											}
											
										} else {
											echo "Could not get database query 2<br />";
										}
									}
									echo '</tr>';
								}
								echo '<table/>';
								//if the user wanted to see a monthly time period
							} else if ($time == 'Month') {
								echo '<table align="left"
								border = "1" cellspace="5" cellpadding="8">
								<tr><td align="left"><b>Month of '.$year.'</b></td>';
								foreach ($AllTests as $test){
									echo '<td align="left"><b>'.$test.'</b></td>';
								}
								echo '</tr>';
								//for each month and test find the count of images
								for ($m = 1; $m < 13; $m++){
									echo '<tr><td align="left"><b>'.$months[$m].'</b></td>';
									foreach ($AllTests as $test){
										//create the query to get the corresponding image counts
										$query = "SELECT COUNT(DISTINCT image) FROM joined WHERE pid = '$PatientIds[$int]' AND test = '$test' AND EXTRACT(MONTH FROM tdate) = '$m' AND EXTRACT (YEAR FROM tdate) = '$year'";
										//get a database response
										$statement = oci_parse($connection, $query);
										$results = oci_execute($statement);
										//put the count in the table
										$row = oci_fetch_array($statement, OCI_BOTH);
										if ($results) {
											if($row == false){
												echo '<td align="left"> 0 </td>';
											} else {
												echo '<td align="left">' . $row[0] . '</td>';
											}
										} else {
											echo "Could not get database query 2<br />";
										}
									}	
									echo '</tr>';
								}
								echo '<table/>';
							//if the user wanted to see a weekly time period
							} else {
								//intialize table headers
								echo '<table align="left"
								border = "1" cellspace="5" cellpadding="8">
								<tr><td align="left"><b>Week of '.$months[$month].' '.$year.'</b></td>';
								foreach ($AllTests as $test){
									echo '<td align="left"><b>'.$test.'</b></td>';
								}
								echo '</tr>';
								//for each week and and test type get the image count
								for ($w = 0; $w < 5; $w++){
									echo '<tr><td align="left"><b>'.$weeks[$w].'</b></td>';
									foreach ($AllTests as $test){
										//create the query to get the corresponding image counts
										$query = "SELECT COUNT(DISTINCT image) FROM joined WHERE pid = '$PatientIds[$int]' AND test = '$test' AND EXTRACT (DAY FROM tdate) <= ('$w' + 1)*7 AND EXTRACT(DAY FROM tdate) > '$w'*7 
										AND EXTRACT(MONTH FROM tdate) = '$month' AND EXTRACT (YEAR FROM tdate) = '$year'";
										//get a database response
										$statement = oci_parse($connection, $query);
										$results = oci_execute($statement);		
										//put the count in the table
										$row = oci_fetch_array($statement, OCI_BOTH);
										if ($results) {
											if($row == false){
												echo '<td align="left"> 0 </td>';
											} else {
												echo '<td align="left">' . $row[0] . '</td>';
											}
										} else {
											echo "Could not get database query 2<br />";
										}	
									}
									echo '</tr>';
								}
								echo '<table/>';
							}
						//if the user wanted to see a specific time period and test
						} else if($time != 'All'&& $patient  == 'All' && $test != 'All'){
							//create table headers
							echo '<b>For Test Type '.$test.'<b>';
								//for a yearly time period
								if ($time == 'Year'){
									echo '<table align="left"
									border = "1"
									cellspace="5" cellpadding="8">
									<tr><td align="left"><b>Year</b></td>';
									for ($int = 0;$int < count($AllPatients);$int++){
										echo '<td align="left"><b>'.$AllPatients[$int].' </b></td>';
									}
									echo '</tr>';
									//for each year and patient find the count
									for ($y = 2000; $y < 2016; $y++){
										echo '<tr><td align="left"><b>'.$y.'</b></td>';
										for ($int = 0; $int < count($AllPatients);$int++) {
											//create the query to get the corresponding image counts
											$query = "SELECT COUNT(DISTINCT image) FROM joined WHERE pid = '$PatientIds[$int]' AND test = '$test' AND EXTRACT(YEAR FROM tdate) = '$y'";
											//get a database response
											$statement = oci_parse($connection, $query);
											$results = oci_execute($statement);
											//put the count in the table
											$row = oci_fetch_array($statement, OCI_BOTH);
											if ($results) {
												if($row == false){
													echo '<td align="left"> 0 </td>';
												} else {
													echo '<td align="left">' . $row[0] . '</td>';
												}
												
											} else {
												echo "Could not get database query 2<br />";
											}
										}
										echo '</tr>';
									} echo '<table/>';
									//for a monthly time period
								} else if ($time == 'Month') {
									//print table headers
									echo '<table align="left"
									border = "1"
									cellspace="5" cellpadding="8">
									<tr><td align="left"><b>Month of '.$year.'</b></td>';
									for ($int = 0;$int < count($AllPatients);$int++){
										echo '<td align="left"><b>'.$AllPatients[$int].'</b></td>';
									}
									echo '</tr>';
									//for each month and patient find the image count
									for ($m = 1; $m < 13; $m++){
										echo '<tr><td align="left"><b>'.$months[$m].'</b></td>';
										for ($int = 0; $int < count($AllPatients);$int++) {
											//create the query to get the corresponding image counts
											$query = "SELECT COUNT(DISTINCT image) FROM joined WHERE pid = '$PatientIds[$int]' AND test = '$test' AND EXTRACT(MONTH FROM tdate) = '$m' AND EXTRACT (YEAR FROM tdate) = '$year'";
											//get a database response
											$statement = oci_parse($connection, $query);
											$results = oci_execute($statement);
											//put the count in the table
											$row = oci_fetch_array($statement, OCI_BOTH);
											if ($results) {
												if($row == false){
													echo '<td align="left"> 0 </td>';
												} else {
													echo '<td align="left">' . $row[0] . '</td>';
												}
											} else {
												echo "Could not get database query 2<br />";
											}	
										}
										echo '</tr>';
									} echo '<table/>';
									//for a weekly time period
								} else {
									//intialize table headers
									echo '<table align="left"
									border = "1" cellspace="5" cellpadding="8">
									<tr><td align="left"><b>Week of '.$months[$month].' '.$year.'</b></td>';
									for ($int = 0;$int < count($AllPatients);$int++){
										echo '<td align="left"><b>'.$AllPatients[$int].'</b></td>';
									}
									echo '</tr>';
									//for each week and patient find the image count
									for ($w = 0; $w < 5; $w++){
										echo '<tr><td align="left"><b>'.$weeks[$w].'</b></td>';
										for ($int = 0; $int < count($AllPatients);$int++) {
											//create the query to get the corresponding image counts
											$query = "SELECT COUNT(DISTINCT image) FROM joined WHERE pid = '$PatientIds[$int]' AND test = '$test' AND EXTRACT (DAY FROM tdate) <= ('$w' + 1)*7 
											AND EXTRACT(DAY FROM tdate) > '$w'*7 AND EXTRACT(MONTH FROM tdate) = '$month' AND EXTRACT (YEAR FROM tdate) = '$year'";
											//get a database response
											$statement = oci_parse($connection, $query);
											$results = oci_execute($statement);		
											//put the count in the table
											$row = oci_fetch_array($statement, OCI_BOTH);
											if ($results) {
												if($row == false){
													echo '<td align="left"> 0 </td>';
												} else {
													echo '<td align="left">' . $row[0] . '</td>';
												}
											} else {
												echo "Could not get database query 2<br />";
											}	
										} echo '</tr>';
									} echo '<table/>';
								}
							//if the user wants to see a specific patient and test
						} else if($time == 'All'&& $patient  != 'All' && $test != 'All'){
							//print the table headers
							echo '<table align="left" border = "1"
							cellspace="5" cellpadding="8">
							<tr><td align="left"><b>Patient</b></td>';
							echo '<td align="left">'.$test.'</td></tr>';
							$int = 0;
							//find the patient id
							for ($int; $int < count($AllPatients); $int++){
								if ($patient == $AllPatients[$int]) {
									echo '<td align="left">'.$AllPatients[$int].'</td>';
									break;
								}
							}					
							//find the corresponding image count 
							$query = "SELECT COUNT(DISTINCT image) FROM joined WHERE pid = '$PatientIds[$int]' AND test = '$test'";			
							//get a database response
							$statement = oci_parse($connection, $query);
							$results = oci_execute($statement);		
							//put the count in the table
							$row = oci_fetch_array($statement, OCI_BOTH);
							if ($results) {
								if($row == false){
									echo '<td align="left"> 0 </td>';
								} else {
									echo '<td align="left">' . $row[0] . '</td>';
								}
							} else {
								echo "Could not get database query 2<br />";
							}	
							echo '</tr>';
							echo '<table/>';
							//if the user wanted to see a specific time period, patient, and test
						} else if($time != 'All'&& $patient != 'All' && $test != 'All'){
							//tell the user whic test the patient/period table is for
							echo '<b>For Test Type '.$test.' <b></br>';
							//initialize table headers for yearly time period
								if ($time == 'Year'){
									echo '<table align="left"
									border = "1" cellspace="5" cellpadding="8">
									<tr><td align="left"><b>Year</b></td>';
									//get the patient id
									$int = 0;
									for ($int;$int < count($AllPatients);$int++){
										if($patient == $AllPatients[$int]) {
											echo '<td align="left"><b>'.$AllPatients[$int].'</b></td></tr>';
											break;
										}
									}
									//for the patient and time period get the image count 
									for ($y = 2000; $y < 2016; $y++){
										echo '<tr><td align="left"><b>'.$y.'</b></td>';
										//create the query to get the corresponding image counts
										$query = "SELECT COUNT(DISTINCT image) FROM joined WHERE pid = '$PatientIds[$int]' AND test = '$test' AND EXTRACT(YEAR FROM tdate) = '$y'";
										//get a database response
										$statement = oci_parse($connection, $query);
										$results = oci_execute($statement);
										//put the count in the table
										$row = oci_fetch_array($statement, OCI_BOTH);
										if ($results) {
											if($row == false){
												echo '<td align="left"> 0 </td>';
											} else {
												echo '<td align="left">' . $row[0] . '</td>';
											}
										} else {
											echo "Could not get database query 2<br />";
										}
										echo '</tr>';
									} echo '<table/>';
									//for the monthly time period
								} else if ($time == 'Month') {
									//print the table headers
									echo '<table align="left"
									border = "1" cellspace="5" cellpadding="8">
									<tr><td align="left"><b>Month of '.$year.'</b></td>';
									$int = 0;
									//get the patient id
									for ($int;$int < count($AllPatients);$int++){
										if($patient == $AllPatients[$int]) {
											echo '<td align="left"><b>'.$AllPatients[$int].'</b></td></tr>';
											break;
										}
									}
									//for each month find the image count for the specified patient and test type
									for ($m = 1; $m < 13; $m++){
										echo '<tr><td align="left"><b>'.$months[$m].'</b></td>';
										//create the query to get the corresponding image counts
										$query = "SELECT COUNT(DISTINCT image) FROM joined WHERE pid = '$PatientIds[$int]' AND test = '$test' AND EXTRACT(MONTH FROM tdate) = '$m' AND EXTRACT (YEAR FROM tdate) = '$year'";
										//get a database response
										$statement = oci_parse($connection, $query);
										$results = oci_execute($statement);
										//put the count in the table
										$row = oci_fetch_array($statement, OCI_BOTH);
										if ($results) {
											if($row == false){
												echo '<td align="left"> 0 </td>';
											} else {
												echo '<td align="left">' . $row[0] . '</td>';
											}
										} else {
											echo "Could not get database query 2<br />";
										}	
										echo '</tr>';
									} echo '<table/>';
									//for the weekly time period
								} else {
									//intialize table headers
									echo '<table align="left"
									border = "1" cellspace="5" cellpadding="8">
									<tr><td align="left"><b>Weeks of '.$months[$month].' '.$year.'</b></td>';
									$int = 0;
									//get the patient id
									for ($int;$int < count($AllPatients);$int++){
										if($patient == $AllPatients[$int]) {
											echo '<td align="left"><b>'.$AllPatients[$int].'</b></td></tr>';
											break;
										}
									}
									//for each week find the image count for the specified patient and test type
									for ($w = 0; $w < 5; $w++){
										echo '<tr><td align="left">'.$weeks[$w].'</td>';
										//create the query to get the corresponding image counts
										$query = "SELECT COUNT(DISTINCT image) FROM joined WHERE pid = '$PatientIds[$int]' AND test = '$test' AND EXTRACT (DAY FROM tdate) <= ('$w' + 1)*7 AND EXTRACT(DAY FROM tdate) > '$w'*7 AND EXTRACT(MONTH FROM tdate) = '$month' AND EXTRACT (YEAR FROM tdate) = '$year'";
										//get a database response
										$statement = oci_parse($connection, $query);
										$results = oci_execute($statement);		
										//put the count in the table
										$row = oci_fetch_array($statement, OCI_BOTH);
										if ($results) {
											if($row == false){
												echo '<td align="left"> 0 </td>';
											} else {
												echo '<td align="left">' . $row[0] . '</td>';
											}
										} else {
											echo "Could not get database query 2<br />";
										}	
										echo '</tr>';
									} echo '<table/>';
								}
							}
							//drop the joined table
							$query = "DROP TABLE joined";				
							//get a database response
							$statement = oci_parse($connection, $query);
							$results = oci_execute($statement);
							//close database connection?
							oci_free_statement($statement);
							oci_close($connection);
						}
					}	
				}
	?>
	</body>
	</html>
