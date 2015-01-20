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

// Link is required but can be garbage


$link = $doc->createElement("link");
$link = $sitemap->appendChild($link);

$linkcontents=$doc->createTextNode("http://slickplan.com/");
$linkcontents=$link->appendChild($linkcontents);


// Set SlickPlan options... hoping to skip this.

$options=$doc->createElement("options");
$options=$section->appendChild($options);

// Create cells container.

$cells=$doc->createElement("cells");
$cells=$section->appendChild($cells);


// Translation table for the spreadsheet.

$column_translations["url"]="url";
$column_translations["title"]="text";
// $column_translations["cms-instance"]="desc";
$column_translations["auto-color"]="color";


$order = 100;

$systems=[];


// Loop through each row creating a <row> node with the correct data

while (($row = fgetcsv($inputFile)) !== FALSE)
{
 $container = $doc->createElement('cell');
 
 // We only want to save each row if it has a name or a title.
 $saveit=false;
 $title="";
 $cms="";
 $parentid="";
 
 foreach ($headers as $i => $column)
 {
 	$column=trim($column);

 	// Check our translation table. 
 	if(array_key_exists(strtolower($column),$column_translations)) {
 		// If available, use the translated one.
 		$column=$column_translations[strtolower($column)];
 	}
 	
 	// If it's the CMS, save it to description and $cms. 
 	
 	if (strtolower($column)=="cms-instance") {
 		$column="desc";
 		$cms=$row[$i];
 		
 		// Is it in our list of CMSes?
 		
 		if (!in_array($cms,$systems,true)) {
  			// No? Let's add it
 			array_push($systems,$cms);
 		}
 		
 	}
 	
 	
 	 // many incoming URLs are missing the protocol, add it if needed
 	 
     if ($column=="url" && strpos($row[$i],"http")==false) {
     	$row[$i]="http://" . $row[$i];
     }
     
     if ($column=="url" || $column=="text") {
     	$saveit=true;
     }
     
     if ($column=="text") {
     	$title=$row[$i];
     }
     
     
	 $child = $doc->createElement($column);
	 $child = $container->appendChild($child);
     $value = $doc->createTextNode(trim($row[$i]));
     $value = $child->appendChild($value);
     
 }
 
 $order+=100;
 
 
 if ($saveit) {
 	// order attribute increments.
 	// TODO: sort alphabetically?
 	
	 $child = $doc->createElement("order");
	 $child = $container->appendChild($child);
	 $value = $doc->createTextNode($order);
	 $value = $child->appendChild($value); 
	 
	 // add level attribute. All are level 2. CMSes are level 1. 
	 // TODO: children.
	 
	 $child = $doc->createElement("level");
	 $child = $container->appendChild($child);
	 $value = $doc->createTextNode("2");
	 $value = $child->appendChild($value); 
	 
	 // add id attribute.
		 
	 if ($title) {
	 	$id=preg_replace("/[^a-zA-Z]*/","",$title);
	 } else {
	 	
	 	$id=dechex(rand());
	 }
	 $child = $doc->createAttribute("id");
	 $child = $container->appendChild($child);
	 $value = $doc->createTextNode($id);
	 $value = $child->appendChild($value); 
	 
	 // add parent.
	 
	 if ($cms) {
		 	$parent=preg_replace("/[^a-zA-Z]*/","",$cms);
		 } else  {
		 	$parent="";
		 }
		 
	 if ($parent != "") {
	 	
		 $child = $doc->createElement("parent");
		 $child = $container->appendChild($child);
		 $value = $doc->createTextNode($parent);
		 $value = $child->appendChild($value); 
	 }
	 
	 $cells->appendChild($container);
	
	
	
	}
}

 foreach ($systems as $i => $system) {
 
 	
	 $cell = $doc->createElement("cell");
	 $cell = $cells->appendChild($cell);
	 
	 $order+=100;
 	 
 	 
	 $child = $doc->createElement("order");
	 $child = $cell->appendChild($child);
	 $value = $doc->createTextNode($order);
	 $value = $child->appendChild($value); 
 	      	
	 $child = $doc->createElement("text");
	 $child = $cell->appendChild($child);
	 $value = $doc->createTextNode($system);
	 $value = $child->appendChild($value); 
	 
	 $id=preg_replace("/[^a-zA-Z]*/","",$system);
	 
	 $child = $doc->createAttribute("id");
	 $child = $cell->appendChild($child);
	 $value = $doc->createTextNode($id);
	 $value = $child->appendChild($value); 
	 
	 
	 // add level attribute. All are level 2. CMSes are level 1. 
	
	 $child = $doc->createElement("level");
	 $child = $cell->appendChild($child);
	 $value = $doc->createTextNode("1");
	 $value = $child->appendChild($value); 
	 
 	
 }
 
 
 
 
file_put_contents($outputFilename,$doc->saveXML());


?>