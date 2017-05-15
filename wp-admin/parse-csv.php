<?php
	// this file is saved under wp-admin


	// function handles POST Request
	// converts json string -> json_array
	function handleRequest() {
		$data = $_POST['data'];
		$json_array = json_decode($data, true);
		return $json_array;
	}

	function importProducts($json_array) {
		foreach($json_array as $item) {
			echo 'Record ID: ' . $item['Record ID'] . ',';
		}
	}

	function createCategories() {
		
	}

	function createCategory() {

	}

	function createProduct($item) {

	}

	$json_array = handleRequest();
	importProducts($json_array);
?>