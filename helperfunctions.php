<?php
class HTML {
	public function __construct($title) {
		echo "<!DOCTYPE html>\n<html>\n<head>\n\t<meta charset=\"utf-8\">\n\t<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n\t<title>$title</title>\n";
	}

	public function addLink($rel, $href) {
		echo "\t<link rel=\"$rel\" href=\"$href\">\n";
	}

	public function addScript($src, $defer=false) {
		echo "\t<script src=\"$src\"" . ($defer ? ' defer' : '') . "></script>\n";
	}

	public function startBody() {
		echo "</head>\n<body>\n";
	}

	public function endBody() {
		echo "</body>\n</html>\n";
	}
}

function verifyAdminLoggedIn($conn) {
	if (isset($_SESSION['cit-student-id'])) {
	    $sql = "SELECT `type` FROM `accounts` WHERE `student_id` = ?";
	    $stmt = $conn->prepare($sql);
	    $stmt->execute([$_SESSION['cit-student-id']]);
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if (count($result) > 0) {
		    $row = $result[0];
	        $type = $row['type'];
	        if ($type === 'user') { # If account type is user, redirect to user page
	            header("location: ../user/");
	        }
	    } else { # If account is not found, return to login page
	        header("location: ../");
	    }
	} else { # If session is not found, return to login page
	    header("location: ../");
	}
}

function verifyUserLoggedIn($conn) {
	if (isset($_SESSION['cit-student-id'])) {
	    $sql = "SELECT `type` FROM `accounts` WHERE `student_id` = ?";
	    $stmt = $conn->prepare($sql);
	    $stmt->execute([$_SESSION['cit-student-id']]);
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if (count($result) > 0) {
		    $row = $result[0];
	        $type = $row['type'];
	        if ($type === 'admin') { # If account type is admin, redirect to admin page
	            header("location: ../admin/");
	        }
	    } else { # If account is not found, return to login page
	        header("location: ../");
	    }
	} else { # If session is not found, return to login page
	    header("location: ../");
	}
}

function generateEmail($firstname, $lastname, $domain = "cbsua.edu.ph") {
	$specialCharsMap = [
		'ñ' => 'n', 'Ñ' => 'N',
		'á' => 'a', 'Á' => 'A',
		'é' => 'e', 'É' => 'E',
		'í' => 'i', 'Í' => 'I',
		'ó' => 'o', 'Ó' => 'O',
		'ú' => 'u', 'Ú' => 'U',
		'ü' => 'u', 'Ü' => 'U'
    ];
	$cleanFirstName = strtr(trim($firstname), $specialCharsMap);
	$cleanLastName = strtr(trim($lastname), $specialCharsMap);
	$email = $cleanFirstName . "." . $cleanLastName;
	$email .= "@" . $domain;
	$generatedEmail = strtolower(str_replace(" ", "", $email));
	return $generatedEmail;
}
?>