#!/usr/bin/php
<?php
error_reporting(E_ALL | E_STRICT);
// ini_set('display_errors', true);
ini_set('auto_detect_line_endings', true);

// h/t http://stackoverflow.com/questions/4852796/php-script-to-convert-csv-files-to-xml

if (!$argv[1] || !$argv[2]) {
	exit("\nUsage: " . $argv[0] . " input.csv output.xml [ia/systems] \n\n");
}
$inputFilename    = $argv[1];
$outputFilename   = $argv[2];

$mode_ia=false;
$mode_systems=true;
if (strtolower($argv[3])=="ia") {
	$mode_ia=true;
	$mode_systems=false;
}




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
 
 // Convenience variables--we'll gather these as we go.
 
 $title="";
 $cms="";
 $parentid="";
 $url="";
 $iaparent="";
 
 
 foreach ($headers as $i => $column)
 {
 	$column=strtolower(trim($column));
 	$row[$i]=trim($row[$i]);
 	
	if ($column=="" || $row[$i]=="") {
		continue;
	}
	
 	// Check our translation table. 
 	if(array_key_exists($column,$column_translations)) {
 		// If available, use the translated one.
 		$column=$column_translations[$column];
 	}
 	
 	// If it's the CMS, save it to description and $cms. 
 	
 	if ($column=="cms-instance") {
 		$column="desc";
 		$cms=$row[$i];
 		
 		// Is it in our list of CMSes?
 		
 		if (strtolower($cms) != "outofscope" &&  !in_array($cms,$systems,true)) {
  			// No? Let's add it
 			array_push($systems,$cms);
 		}
 		
 	}
 	
 	
 	 // many incoming URLs are missing the protocol, add it if needed
 	 
     if ($column=="url" && strpos($row[$i],"http")==false) {
     	$row[$i]="http://" . $row[$i];
     	
     }
     
     if ($column=="url" ){
     	$url=$row[$i];	
     }
          
     if ($column=="url" || $column=="text") {
     	$saveit=true;
     }
     
     if ($column=="text") {
     	$title=$row[$i];
     }
     
     if ($column=="ia-parent") {
     	$iaparent=$row[$i];
     }
     
          
     
     $node_child = addTextNode($doc,$container,$column,$row[$i]);

      
     
 }
 
 $order+=100;
 if ($cms && strtolower($cms)=="outofscope") {
 	$saveit=false;
 }
  
 if ($saveit) {
 	// order attribute increments.
 	// TODO: sort alphabetically?
 	
	 $node_order = addTextNode($doc,$container,"order",$order);
	 
	 // add level attribute. All are level 2. CMSes are level 1. 
	 // TODO: children.
	 
	 	
	 	$node_level = addTextNode($doc,$container,"level","2");
	 
	 
	 // add id attribute.
		 
	 if ($title) {

//	 	$id=preg_replace("/[^a-zA-Z]*/","",$title . $cms);
// Adding the CMS into the key is helpful for system heirarchy, but not
// for the IA-centric heirarchy.

	 	$id=preg_replace("/[^a-zA-Z]*/","",$title);
	 	
	 } else {
	 	
	 	$node_text = addTextNode($doc,$container,"text",$url);
	 	$id=preg_replace("/[^a-zA-Z]*/","",$url);
	 }

	 $node_id = addAttribute($doc,$container,"id",$id);
 
	if ($mode_ia && $cms) {
     	$results=$container->getElementsByTagName('text');
		foreach($results as $result) {
			$result->nodeValue = $result->nodeValue . " (" . $cms . ")";
		}
     }
 
	
	
	
	 // add parent.
	 $parent="";
	 
	 if ($mode_ia) {
	 	
	 	// Information architecture heirarchy
	 	
	 	if ($iaparent) {
	 		
	 		if (strtolower(trim($iaparent))==strtolower(trim($title))) {
	 			
	 			// is it defined as its own parent? then it is top-level
	 			
		 		$node_level->nodeValue="1";
	 		
	 		} else {
	 			
	 			// set its parent 
	 			
	 			$parent=preg_replace("/[^a-zA-Z]*/","",$iaparent);
	 			$node_level->nodeValue="2";
	 		}
	 		
	 		
	 	} else {
	 		
	 		$node_level->nodeValue="1";
	 		
	 	}
	 	
	 	
	 } else {
	 	
	 	// Systems heirarchy
		 	
		 if ($cms) {
			 	$parent=preg_replace("/[^a-zA-Z]*/","",$cms);
			 }  
		 
	 }
	 
	 
	 if ($parent != "") {
		 	$node_parent = addTextNode($doc,$container,"parent",$parent);
	 }
	 
	 
	 $cells->appendChild($container);
	
	
	
	}
}

if ($mode_systems) {
		
	// We need first-level heirarchy for the systems view...
	
	 foreach ($systems as $i => $system) {
	 
	 	
		 $cell = $doc->createElement("cell");
		 $cell = $cells->appendChild($cell);
		 
		 $order+=100;
	 	 $node_order = addTextNode($doc,$cell,"order",$order);
	
		 $node_text = addTextNode($doc,$cell,"text",$system);
		 
		 $id=preg_replace("/[^a-zA-Z]*/","",$system);
		 
		 $node_id = addAttribute($doc,$cell,"id",$id);
		
		 
		 // add level attribute. All are level 2. CMSes are level 1. 
		
		 $node_level = addTextNode($doc,$cell,"level","1");
	 	
	 }
	 
}

 
 
file_put_contents($outputFilename,$doc->saveXML());







function addTextNode($doc,$parent,$name,$value) {
	$child = $doc->createElement($name);
	$child = $parent->appendChild($child);
	$value = $doc->createTextNode($value);
	$value = $child->appendChild($value); 
	return $child;	
}
function addAttribute($doc,$parent,$name,$value) {
	$child = $doc->createAttribute($name);
	$child = $parent->appendChild($child);
	$value = $doc->createTextNode($value);
	$value = $child->appendChild($value); 
	return $child;	
}



?>