<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://www.mind.uci.edu/
 * @since      1.0.0
 *
 * @package    Stemcells
 * @subpackage Stemcells/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<!-- parse csv using php -->
<!-- <div>
	<h1>CSV IMPORTER</h1>
	<form enctype="multipart/form-data" action="parse-csv.php" method="POST">
		<span>Select a CSV file to upload</span>
		<br>
		<input type="file" id="csvmind_post-button" name="upload" value="import">
		<br>
		<input type="submit" value="import" id="submit">
	</form>
</div> -->


<!-- parse csv using javascript -->
<!-- i decided not to use a form since we are sending the POST request through ajax -->
<div>
	<h1>CSV IMPORTER</h1>
	<span>Select a CSV file to upload</span>
	<br>
	<input type="file" id="csvmind_post-button" name="upload" value="import">
</div>