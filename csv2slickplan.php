#!/usr/bin/php
<?php
error_reporting(E_ALL | E_STRICT);
// ini_set('display_errors', true);
ini_set('auto_detect_line_endings', true);

// h/t http://stackoverflow.com/questions/4852796/php-script-to-convert-csv-files-to-xml

if (!$argv[1] || !$argv[2]) {
	exit("\nUsage: " . $argv[0] . " input.csv output.xml\n\n");
}
$inputFilename    = $argv[1];
$outputFilename   = $argv[2];



// Open csv to read

$inputFile  = fopen($inputFilename, 'rt');

// Get the headers of the CSV's columns

$headers = fgetcsv($inputFile);
// each call to fgetcsv ingests one line.

// Create DOM document

$doc  = new DOMDocument();
$doc->formatOutput   = true;

// Add root sitemap node to the document

$sitemap = $doc->createElement('sitemap');
$sitemap = $doc->appendChild($sitemap);

// Add the main section to the document. 
// SlickPlan seems to use this to contain all of the objectss

$section = $doc->createElement('section');
$section = $sitemap->appendChild($section);

$sectionid=$doc->createAttribute('id');
$sectionid->value="svgmainsection";
$sectionid=$section->appendChild($sectionid);

// Set sitemap title from input filename

$title = $doc->createElement('title');
$title=$sitemap->appendChild($title);

$titlecontents=$doc->createTextNode(basename($inputFilename));
$titlecontents=$title->appendChild($titlecontents);

// Define version

$version = $doc->createElement('version');
$version = $sitemap->appendChild($version);

$versioncontents=$doc->createTextNode("1.0");
$versioncontents=$version->appendChild($versioncontents);

// Set SlickPlan options... hoping to skip this.

$options=$doc->createElement("options");
$options=$section->appendChild($options);

// Create cells container.

$cells=$doc->createElement("cells");
$cells=$section->appendChild($cells);


// Translation table for the spreadsheet.

$column_translations["url"]="url";
$column_translations["title"]="text";
$column_translations["cms-instance"]="desc";
$column_translations["auto-color"]="color";


$order = 100;


// Loop through each row creating a <row> node with the correct data

while (($row = fgetcsv($inputFile)) !== FALSE)
{
 $container = $doc->createElement('cell');

 foreach ($headers as $i => $column)
 {
 	// Check our translation table. 
 	
 	if(array_key_exists(strtolower($column),$column_translations)) {
 		// If available, use the translated one.
 		$column=$column_translations[strtolower($column)];
 	}
 	
 	 // many incoming URLs are missing the protocol, add it if needed
 	 
     if ($column=="url" && strpos($row[$i],"http")==false) {
     	$row[$i]="http://" . $row[$i];
     }
     
     
	 $child = $doc->createElement($column);
	 $child = $container->appendChild($child);
     $value = $doc->createTextNode($row[$i]);
     $value = $child->appendChild($value);
     
 }
 
 $order+=100;
 
 $child = $doc->createElement("order");
 $child = $container->appendChild($child);
 $value = $doc->createTextNode($order);
 $value = $child->appendChild($value); 
 $cells->appendChild($container);
}

file_put_contents($outputFilename,$doc->saveXML());


?>