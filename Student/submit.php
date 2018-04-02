<?php
	require_once("support.php");
	require_once('applicant.php');

	// Starts session. We need to keep track to see if they are a valid user.
	session_start();

	// Basic form for entering applicant info. Makes a new applicant() object.
	$message = "";
	$body = <<<BODY
		<form action="{$_SERVER["PHP_SELF"]}" method="post">
		<b>Name: </b>
			<input type="text" name="name" required><br><br>
		<b>Email: </b>
			<input type="email" name="email" required><br><br>
		<b>GPA: </b>
			<input type="number" name="gpa" step="0.01" required><br><br>
		<b>Year: </b>
		<br>
			<input type="radio" name="year" value="10" required> 10
			<br>
			<input type="radio" name="year" value="11" required> 11
			<br>
			<input type="radio" name="year" value="12" required> 12<br><br>
		<b>Gender: </b>
		<br>
			<input type="radio" name="gender" value="M" required> M
			<br>
			<input type="radio" name="gender" value="F" required> F<br><br>
		<b>Password: </b>
				<input type="password" name="password" required><br><br>
		<b>Verify Password: </b>
				<input required type="password" name="verifypass" required>
		<br><br>

		<input type="submit" name="submitInfoButton" value="Submit Data">
		<br><br>

		</form>

		<form action = "main.html" method = 'post'>
			<input type="submit" name="mainMenuButton" value="Return to main menu">
		</form>
BODY;

	if(isset($_POST["submitInfoButton"])){

		$name = trim($_POST["name"]);
		$email = trim($_POST["email"]);
		$gpa = trim($_POST["gpa"]);
		$year = trim($_POST["year"]);
		$gender = trim($_POST["gender"]);
		$password = trim($_POST["password"]);
		$verifypass = trim($_POST["verifypass"]);

		if ($password !== $verifypass) {
			$message = "<h2>Passwords do not match</h2>";
		} else {
			$safePass = password_hash($password, PASSWORD_DEFAULT);
			$password = "";
			$userID = new applicant($name,$email,$gpa,$year,$gender,$safePass);
			$_SESSION["userID"] = serialize($userID);
		}

		if(isset($_SESSION['userID'])){
			$userID = unserialize($_SESSION["userID"]);

			$host = "localhost";
		    $user = "dbuser";
		    $dbpassword = "goodbyeWorld";
		    $database = "applicationdb";
		    $table = "applicants";
		    $db = connectToDB($host, $user, $dbpassword, $database);

		    $name = $userID->getName();
		    $email = $userID->getEmail();
		 	$gpa = $userID->getGpa();
			$year = $userID->getYear();
			$gender = $userID->getGender();
			$password = $userID->getPassword();

			$sqlQuery  = "insert into $table (name,email,gpa,year,gender,password) values (\"$name\",\"$email\",$gpa,$year,\"$gender\",\"$password\")";
			$result = mysqli_query($db, $sqlQuery);

			if ($result) {
				$body = <<<EOBODY
					<form action="{$_SERVER["PHP_SELF"]}" method="post">

					<h3>The following entry has been added to the database</h3>

					<b>Name: </b> $name<br>
					<b>Email: </b> $email<br>
					<b>Gpa: </b> $gpa<br>
					<b>Year: </b> $year<br>
					<b>Gender: </b> $gender<br>
					<br>

					<input type="submit" name="mainMenuButton" value="Return to main menu">
					<br>

					</form>
EOBODY;
				session_destroy();
		        unset($_SESSION["userID"]);

			} else {
				$body = "Inserting records failed.".mysqli_error($db);
			}
			mysqli_close($db);
		}
	}

	if(isset($_POST["mainMenuButton"])){
  	 	header("Location: main.html");
	}

	$page = generatePage($body.$message, "Submit");
	echo $page;

	function connectToDB($host, $user, $password, $database) {
		$db = mysqli_connect($host, $user, $password, $database);
		if (mysqli_connect_errno()) {
			echo "Connect failed.\n".mysqli_connect_error();
			exit();
		}
		return $db;
	}
?>
