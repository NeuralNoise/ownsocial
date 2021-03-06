<?php

ini_set('display_errors', false);

date_default_timezone_set('Europe/Berlin');

require_once('../../vendor/autoload.php');

if (is_file('../../config.php')) {
	die('application already installed');
}

# current release db layout version
$dbLayoutVersion = require('../../db_layout_version.php');

if (@$_SERVER['REQUEST_METHOD'] === 'POST') {

	$dbHost = $_POST['db_host'];
	$dbName = $_POST['db_name'];
	$dbUser = $_POST['db_user'];
	$dbPass = $_POST['db_pass'];
	$useHttps = (@$_POST['https'] === 'yes');

	$firstName = trim($_POST['first_name']);
	$lastName = trim($_POST['last_name']);
	$email = trim($_POST['email']);
	$password = trim($_POST['password']);
	$password2 = trim($_POST['password2']);
	$siteTitle = trim($_POST['site_title']);
	$networkType = trim($_POST['network_type']);
	$defaultLanguage = trim($_POST['default_language']);

	# generate random update tool password
	$aChars = array_merge(range('0', '9'), range('a', 'z'), range('A', 'Z'));

	$aString = array();
	for ($i = 0; $i < 16; $i++)
	{
		$aString[] = $aChars[mt_rand(0, count($aChars) - 1)];
	}

	$updateToolPassword = join('', $aString);

	echo '<p>Your update tool password is: <strong>' . htmlspecialchars($updateToolPassword) . '<br />Copy this password, you will need it to update your OwnSocial installation.</strong></p>';

	if ($password !== $password2) {
		die('passwords are not equal');
	}

	try {
		$db = new PDO('mysql:host=' . $dbHost . ';charset=utf8mb4', $dbUser, $dbPass);
		$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
	} catch (PDOException $e) {
		die('database connect failed: ' . $e->getMessage());
	}

	if (!$firstName || !$lastName || !$email || !$password) {
		die('you have to enter all fields');
	}

	$stmt = $db->query('SHOW DATABASES LIKE ' . $db->quote($dbName));

	if ($stmt->fetch() === false) {
		$db->query('CREATE DATABASE IF NOT EXISTS `' . $dbName . '` /*!40100 DEFAULT CHARACTER SET utf8 */');
	}

	$db->query('USE ' . $dbName);

	$db->exec('set foreign_key_checks=0');

	$sql = file_get_contents('install.sql');
	$db->query($sql);

	$stmt = $db->prepare('
		INSERT INTO
			users
			(
				type,
				email,
				email_confirmed,
				email_confirmation_hash,
				account_confirmed,
				password,
				language,
				first_name,
				last_name,
				department,
				created
			)
			VALUES
			(
				\'admin\',
				:email,
				1,
				NULL,
				1,
				:password,
				:language,
				:first_name,
				:last_name,
				NULL,
				:created
			)'
	);

	$stmt->execute(array(
		':email' => $email,
		':password' => password_hash($password, PASSWORD_DEFAULT),
		':language' => $defaultLanguage,
		':first_name' => $firstName,
		':last_name' => $lastName,
		':created' => time()
	));

	$stmt = $db->prepare('INSERT INTO configs (`key`, `value`) VALUES (:key, :value)');

	$stmt->execute(array(
		':key' => 'db_layout_version',
		':value' => $dbLayoutVersion
	));

	$stmt->execute(array(
		':key' => 'site_title',
		':value' => $siteTitle
	));

	$stmt->execute(array(
		':key' => 'network_type',
		':value' => $networkType
	));

	$stmt->execute(array(
		':key' => 'default_language',
		':value' => $defaultLanguage
	));

	$db->exec('set foreign_key_checks=1');

	$config = array(
		'db_host' => $dbHost,
		'db_name' => $dbName,
		'db_user' => $dbUser,
		'db_pass' => $dbPass,
		'https' => $useHttps,
		'update_tool_password' => $updateToolPassword
	);

	$configFile = '<?php

return ' . var_export($config, true) . ';';

	if (!file_put_contents('../../config.php', $configFile)) {
		echo 'please create config.php in ownsocial root folder with the following contents:';
		echo '<pre>' . htmlspecialchars($configFile) . '</pre>';
	}


	die('finished. you can now login with your credentials.');

} else {
	?>

	<html>
	<head>
		<meta http-equiv="Content-Type" content="charset=utf-8" />
		<title>OwnSocial Installation</title>

		<link href="/bower_components/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
		<link href="/css/style.css" rel="stylesheet">

		<script src="/bower_components/jquery/dist/jquery.min.js"></script>
		<script src="/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
	</head>
	<body>
		<div class="container">

			<form method="post" action="" role="form">

			<div class="row">

				<div class="page-header">
					<h1>OwnSocial <small>Installation</small></h1>
				</div>

				<fieldset>
					<legend>General</legend>

					<div>
						<div class="form-group">
							<input type="text" class="form-control" name="site_title" placeholder="Title of the network">
						</div>
					</div>

					<div>
						<div class="form-group">
							<select class="form-control" name="network_type">
								<option value="private">Private network</option>
								<option value="public">Public network</option>
							</select>
						</div>
					</div>

					<div>
						<div class="form-group">
							<select class="form-control" name="default_language">
								<option value="en">Englisch</option>
								<option value="de">Deutsch</option>
							</select>
						</div>
					</div>

					<div>
						<div class="form-group">
							<label>
								<input class="form-control" type="checkbox" name="https" value="yes" />
								Enable https for this network (warning: your server must be configured to allow https connections!)
							</label>
						</div>
					</div>

				</fieldset>

				<fieldset>
					<legend>Database (MySQL)</legend>

					<div>
						<div class="form-group">
							<input type="text" class="form-control" name="db_host" placeholder="Database host" />
						</div>
					</div>

					<div>
						<div class="form-group">
							<input type="text" class="form-control" name="db_name" placeholder="Database name" />
						</div>
					</div>

					<div>
						<div class="form-group">
							<input type="text" class="form-control" name="db_user" placeholder="Database user" />
						</div>
					</div>

					<div>
						<div class="form-group">
							<input type="password" class="form-control" name="db_pass" placeholder="Database password" />
						</div>
					</div>

				</fieldset>

				<fieldset>

					<legend>Your user account</legend>

					<div>
						<div class="form-group">
							<input type="text" class="form-control" name="first_name" placeholder="First name" />
						</div>
					</div>

					<div>
						<div class="form-group">
							<input type="text" class="form-control" name="last_name" placeholder="Last name" />
						</div>
					</div>

					<div>
						<div class="form-group">
							<input type="text" class="form-control" name="email" placeholder="Your e-mail-address" />
						</div>
					</div>

					<div>
						<div class="form-group">
							<input type="password" class="form-control" name="password" placeholder="Your new password" />
						</div>
					</div>

					<div>
						<div class="form-group">
							<input type="password" class="form-control" name="password2" placeholder="Confirm your new password" />
						</div>
					</div>

				</fieldset>

				<div>
					<input type="submit" class="btn btn-primary btn-lg" value="Install..." />
				</div>

			</div>
			</form>
		</div>
	</body>
	</html>

	<?php
}
