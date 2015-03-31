<?php 

// Source: http://www.htmlgoodies.com/beyond/php/article.php/3877766/Web-Developer-How-To-Upload-Images-Using-PHP.htm

// make a note of the current working directory, relative to root. 
$directory_self = str_replace(basename($_SERVER['PHP_SELF']), '', $_SERVER['PHP_SELF']); 

// make a note of the directory that will recieve the uploaded file 
$uploadsDirectory = $_SERVER['DOCUMENT_ROOT'] . $directory_self . 'uploaded_files/'; 

// make a note of the location of the upload form in case we need it 
$uploadForm = 'http://' . $_SERVER['HTTP_HOST'] . $directory_self . 'uploadImage.php'; 


// make a note of the location of the success page 
$uploadSuccess = 'http://' . $_SERVER['HTTP_HOST'] . $directory_self . 'uploadSuccess.php'; 

// fieldname used within the file <input> of the HTML form 
$filename = 'image_file'; 

// possible PHP upload errors 
$errors = array(1 => 'php.ini max file size exceeded', 
                2 => 'html form max file size exceeded', 
                3 => 'file upload was only partial', 
                4 => 'no file was attached'); 

// check the upload form was actually submitted else print the form 
isset($_POST['submit']) 
    or error('the upload form is neaded', $uploadForm); 

// check for PHP's built-in uploading errors 
($_FILES[$filename]['error'] == 0) 
    or error($errors[$_FILES[$filename]['error']], $uploadForm); 
     
// check that the file we are working on really was the subject of an HTTP upload 
@is_uploaded_file($_FILES[$filename]['tmp_name']) 
    or error('not an HTTP upload', $uploadForm); 
     
// validation... since this is an image upload script we should run a check   
// to make sure the uploaded file is in fact an image. Here is a simple check: 
// getimagesize() returns false if the file tested is not an image. 
@getimagesize($_FILES[$filename]['tmp_name']) 
    or error('only image uploads are allowed', $uploadForm); 
     
// make a unique filename for the uploaded file and check it is not already 
// taken... if it is already taken keep trying until we find a vacant one 
// sample filename: 1140732936-filename.jpg 
$now = time(); 
while(file_exists($uploadFilename = $uploadsDirectory.$now.'-'.$_FILES[$filename]['name'])) 
{ 
    $now++; 
} 

echo "uploadFileName: " . $uploadFilename . "<p>";

//require a connection to the database
require ('_database.php');

// Creates an "empty" OCI-Lob object to bind to the locator
$image_lob = oci_new_descriptor($connection, OCI_D_LOB);

$query="INSERT INTO pacs_images (record_id, image_id, full_size) VALUES (1, image_id_seq.nextval, EMPTY_BLOB()) 
	RETURNING full_size, image_id INTO :image, :id";

$statement = oci_parse($connection, $query);

// Bind the returned Oracle LOB locator to the PHP LOB object
oci_bind_by_name($statement, ":image", $image_lob, -1, OCI_B_BLOB);

oci_bind_by_name($statement, ":id", $id);

// Execute the statement using , OCI_DEFAULT - as a transaction
oci_execute($statement, OCI_DEFAULT) or die ("Unable to execute query\n");

//echo "filename: " . $_FILES[$filename]['name'] . "<p>";

if($image_lob->savefile($_FILES[$filename]['name'])) {
	oci_commit($connection);
	echo "image successfully uploaded";
} else {
	echo "Couldn't upload image";
}

echo "<img src='displayImage.php?id=$id' />";

// Clean up database objects
oci_free_statement($statement);
oci_close($connection);

// The following function is an error handler which is used 
// to output an HTML error page if the file upload fails 
function error($error, $location, $seconds = 5) { 
    //header("Refresh: $seconds; URL="$location""); 
    echo("<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 " +
		"Transitional//EN\">\n" +
		"<HTML>\n" +
		"<HEAD><TITLE>Upload Message</TITLE></HEAD>\n" +
		"<BODY>\n" +
		"<H1>" +
	        response_message +
		"</H1>\n" +
		"</BODY></HTML>");
}
?> 