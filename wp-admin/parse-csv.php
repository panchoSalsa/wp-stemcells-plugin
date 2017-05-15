<?php
	// this file is saved under wp-admin


	// source=http://php.net/manual/en/features.file-upload.post-method.php
	// $_FILES['this keyword must match input name attribute']
	// example: <input type="file" id="csvmind_post-button" name="uploaded_file" value="import">
	// to access file contents use $_FILES['uploaded_file']

	// echo $_FILES['upload']['name'] . '<br>';
	// echo $_FILES['upload']['type'] . '<br>';
	// echo $_FILES['upload']['size'] . '<br>';
	// echo $_FILES['upload']['tmp_name'] . '<br>';
	// print_r($_FILES);

	// echo '<br>';
	// echo '<br>';

	// if ($_FILES['upload']['error'] == UPLOAD_ERR_OK               //checks for errors
	// 		&& is_uploaded_file($_FILES['upload']['tmp_name'])) { //checks that file is uploaded
	// 	// echo file_get_contents($_FILES['upload']['tmp_name']); 

	// 	$fh = fopen($_FILES['upload']['tmp_name'], 'r+');
	// 	$lines = array();
	// 	$count = 0; 
	// 	while( ($row = fgetcsv($fh)) !== FALSE ) {
	// 		$lines[] = $row;
	// 		$count++;
	// 		echo "count is " . $count . "<br>";
	// 	}
	// 	var_dump($lines);
		// $row = 0;
		// if (($handle = fopen($_FILES['upload']['tmp_name'], "r+")) !== FALSE) {
		//     while (($data = fgetcsv($handle, 8192, ",")) !== FALSE) {
		//         $num = count($data);
		//         echo "<p>ppppppppppppppppppppppppppppppfffffffffff<p>\n";
		//         echo "<p> $num fields in line $row: <br /></p>\n";
		//         $row++;
		//         for ($c=0; $c < $num; $c++) {
		//             echo $data[$c] . "<br />\n";
		//         }
		//     }
		//     fclose($handle);
		// }

	// }

	// $json = file_get_contents('php://input');
	// echo $json;
	// $data = $_POST['data'];
	// $k= $_POST['data'];
	// $k=preg_replace('/\s+/', '',$k);
	// $a = json_decode($k, true);
	// echo $a;

	// echo $data[49]['Record ID'];
	// $obj = json_decode($json,true);
	// echo $obj;
	// handle from ajax request
	// $_POST['data'] where 'data' is keyword in data object 
	// data object -> {'data': data}
	// echo $_POST['data'];

	// $data = $_POST['data'];
	// if (is_null($obj)) {
	// 	echo 'nullll';
	// }
	// $array =json_decode($data, true);
	// echo $data[1]['Record ID'];
	// echo 'hello'
	// $json = json_decode($data, true);
	// print_r($json);
	// if (is_null($json)) {
	// 	echo 'nullll';
	// } else {
	// 	echo 'continue';
	// }
	// foreach($array as $item) {
	// 	echo $item['Record ID'];
	// }

	// echo $json;
	// echo 'hello';

	// foreach($json as $item) {
	// 	echo 'Record ID: ' . $item['Record ID'] . '<br />';
	// 	// echo 'Brand: ' . $item['product']['brand'] . '<br />';
	// 	// echo 'Description: ' . $item['product']['description'] . '<br />';
	// }
	echo 'hello from process-csv.php';

?>