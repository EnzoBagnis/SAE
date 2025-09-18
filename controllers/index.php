<?php
require_once('/inscription.php');

if (isset($_GET['action']) && $_GET['action'] !== ''){
	if ($_GET['action'] === 'inscription'){
		if (isset($_GET['id']) && $_GET['id'] >0){
		$identifier = $_GET['id'];
		inscription($identifier);
		}else{bIndex();
		}}
		}
?>	
