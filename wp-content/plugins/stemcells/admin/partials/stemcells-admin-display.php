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

<div>
	<h1>CSV IMPORTER</h1>
	<form>
		<span>Select a CSV file to upload</span>
		<br>
		<input type="file" id="csvmind_post-button" value="import">
		<br>
		<input type="submit" value="import" id="submit">
	</form>
</div>
