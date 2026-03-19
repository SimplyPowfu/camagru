<?php
	function sendEmail($email, $subject, $message) {
		$headers = "From: no-reply@camagru.it";
		if (mail($email, $subject, $message, $headers))
			return true;
		else 
			return false;
		}
?>