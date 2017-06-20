(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
	$(document).ready(function(){
		$('#csvmind_post-button').change(function(e) {

			$('#info').css('visibility', 'visible');

			redcapCall();

			// source=http://www.joyofdata.de/blog/parsing-local-csv-file-with-javascript-papa-parse/
			// parse uploaded file content
			// processCSV(e);

			// show submit button 

			// console.log('show submit button');
			// $('#submit').css('visibility','visible');

		});
	});


	function redcapCall() {
		$.ajax({
			// this hardcoded url will probably break
			// url: '/wp-admin/admin-ajax.php',
			url: 'http://' + window.location.host+'/stemcells/wp-admin/admin-ajax.php',
			type: 'POST',
			data: { action : 'redcap_handler'},
			success: function(result){
				console.log('redcapCall() success');

				// maybe redirect to shop or output any error messages ...
				console.log(result);
				processCSV(result);
			}});
	}

	function ajaxCall(data) {
		// send data to php file through ajax
		// source=http://stackoverflow.com/questions/6616539/post-ajax-data-to-php-and-return-data
		// $.ajax({
		// 	curl: 'parse-csv.php',
		// 	type: 'POST',

		// 	// must stringify(data) because php does not understand
		// 	// [Object, Object, Object]
		// 	// it only understands
		// 	// [{"Record ID":"92",...}, {},... {}]
		// 	data: {data: JSON.stringify(data)},
		// 	success: function(result){
		// 		console.log('ajaxCall() success');

		// 		// maybe redirect to shop or output any error messages ...
		// 		console.log(result);
		// 	}});
		// data.action = 'csv_handler';

		//console.log(data);
		console.log({data: JSON.stringify(data), action : 'csv_handler'});
		console.log('http://'+window.location.host+'/wp-admin/admin-ajax.php');
		$.ajax({
			// this hardcoded url will probably break
			// url: '/wp-admin/admin-ajax.php',
			url: 'http://' + window.location.host+'/stemcells/wp-admin/admin-ajax.php',
			type: 'POST',

			// must stringify(data) because php does not understand
			// [Object, Object, Object]
			// it only understands
			// [{"Record ID":"92",...}, {},... {}]
			data: {data: JSON.stringify(data), action : 'csv_handler'},
			success: function(result){
				//console.log('ajaxCall() success');
				$('#info').html('csv import complete');

				// maybe redirect to shop or output any error messages ...
				console.log(result);
			}});
	}

	function processCSV(csv) {
		Papa.parse( csv,{
			// config the first row of parsed data to be interpreted as field names
			header: true,
			complete: function(results)
			{
				// executed after all files are complete
				console.log(results);
				console.log('complete');
				// console.log(results.data);

				// make ajax call to parse-csv.php
				//ajaxCall(results.data);
			}
		});
	}


	// function processCSV(e) {
	// 	Papa.parse( e.target.files[0],{
	// 		// config the first row of parsed data to be interpreted as field names
	// 		header: true,
	// 		before: function(file, inputElem)
	// 		{
	// 			// executed before parsing each file begins;
	// 			// what you return here controls the flow
	// 		},
	// 		error: function(err, file, inputElem, reason)
	// 		{
	// 			// executed if an error occurs while loading the file,
	// 			// or if before callback aborted for some reason
	// 			console.log(err + '\n' + reason);
	// 		},
	// 		complete: function(results, file)
	// 		{
	// 			// executed after all files are complete
	// 			console.log('complete');
	// 			// console.log(results.data);

	// 			// make ajax call to parse-csv.php
	// 			ajaxCall(results.data);
	// 		}
	// 	});
	// }

})( jQuery );