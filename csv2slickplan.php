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
if (!$inputFile) {
	 exit("Couldn't open $inputFilename \n");}
	 

// Get the headers of the CSV's columns

$headers = fgetcsv($inputFile);
// each call to fgetcsv ingests one line.

// Create DOM document

$doc = new DOMDocument();
$doc->formatOutput = true;

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
$column_translations["auto-color"]="color";
$column_translations["cms-instance"]="cms";
$column_translations["page-count"]="pages";

// Outdated variable name, but why break everything?
$column_translations["average-annual-page-views-per-page"]="pageviews"; 
$column_translations["average-annual-pageviews-per-page"]="pageviews";
$column_translations["average-annual-uniques-per-page"]="pageviews";
$column_translations["annual-uniques"]="sumpageviews";
$column_translations["average-time-on-page"]="time";

$order = 100;

$systems=[];

$levels=[];


// Loop through each row creating a <row> node with the correct data

while (($row = fgetcsv($inputFile)) !== FALSE)
{
 $container = $doc->createElement('cell');
 
 // We only want to save each row if it has a name or a title.
 
 $saveit=false;
 
 // Convenience variables--we'll gather these as we go. 
 // Should've done this as a keyed array.
 
 $title="";
 $cms="";
 $parentid="";
 $url="";
 $iaparent="";
 $pageviews="";
 $pages="";
 $value="";
 $notes="";
 $description="";
 $sumpageviews="";
 $time="";
  
 
 foreach ($headers as $i => $column)
 {
 	$column=strtolower(trim(preg_replace("/ /","-",$column)));
 	$row[$i]=trim($row[$i]);
 	
	if ($column=="" || $row[$i]=="" || $row[$i]=="#N/A" || $row[$i]=="#DIV/0!") {
		continue;
	}
	
 	// Check our translation table. 
 	if(array_key_exists($column,$column_translations)) {
 		// If available, use the translated one.
 		$column=$column_translations[$column];
 	}
 	
 	// If it's the CMS, save it to description and $cms. 
 	
 	if ($column=="cms") {
 		
 		$cms=preg_replace("/^[z]*/","",$row[$i]);
 		
 		// Is it in our list of CMSes?
 		
 		if (strtolower($cms) != "outofscope" &&  !in_array($cms,$systems,true)) {
  			// No? Let's add it
 			array_push($systems,$cms);
 		}
 		
 	}
 	
 	
 	 // many incoming URLs are missing the protocol, add it if needed
 	 
     if ($column=="url" && strpos($row[$i],"http")===false) {
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
     
     if ($column=="pageviews") {
     	$pageviews=$row[$i];
     }
     if ($column=="pages") {
     	$pages=$row[$i];
     }
     if ($column=="value") {
     	$value=$row[$i];
     }
     if ($column=="notes") {
     	$notes=$row[$i];
     }
     if ($column=="sumpageviews" && $row[$i]>0) {
     	$sumpageviews=$row[$i];
     }
     
     if ($column=="time") {
     	$time=$row[$i];
     }
     
     $node_child = addTextNode($doc,$container,$column,$row[$i]);

 }
 
 $order+=100;
 if ($cms && strtolower($cms)=="outofscope") {
 	$saveit=false;
 }
  
 if ($saveit) {
 	
 	if ($cms!="") {
 		$description .= "CMS: " . $cms . ". \n";
 	}
 	if ($iaparent!="") {
 		$description .= "Category: " . $iaparent . ". \n";
 	}
 	if ($sumpageviews!="") {
 		$description .= "Annual uniques: " . intval($sumpageviews) . ". \n";
 	}
 	if ($pages!="") {
 		$description .= "Estimated page count: &#8776;" . intval($pages) . ". \n";
 	}
 	if ($pageviews!="") {
 		$description .= "Estimated uniques/page: &#8776;" . sprintf("%.2f",$pageviews) . ". \n";
 	}
 	if ($time!="") {
 		$description .= "Average time on page: " . $time . ". \n";
 	}
 	if ($value!="") {
 		$description .= "Value: " . $value . ". \n";
 	}
 	if ($notes!="") {
 		$description .= "Other notes: " . $notes . " \n";
 	}
 	 $node_order = addTextNode($doc,$container,"desc",$description);
	 
 	// order attribute increments.
 	// TODO: sort alphabetically?
 	
	 $node_order = addTextNode($doc,$container,"order",$order);
	 
	 // add level attribute. 
	 
	 // In systems heiarchy, site are all level 2. CMSes are level 1. 
	 
	 // In IA heirarchy, sites contain each other, and are levels 1-2.
	  
	 // SlickPlan doesn't seem to particularly care about this attribute,
	 // but let's play nicely.
	 
	 $node_level = addTextNode($doc,$container,"level","2");
	 
	 //	There's more logic further down to change this for the IA arch.
	 
	 
	 // add id attribute.
		 
	 if ($title) {

		//	 	$id=preg_replace("/[^a-zA-Z0-9]*/","",$title . $cms);
		// Adding the CMS into the key is helpful for system heirarchy, but not
		// for the IA-centric heirarchy.
	
		// SlickPlan assigns an alpha rando gibberish. Let's just use the 
		//  title's alpha chars for the ID.
		
		$id=preg_replace("/[^a-zA-Z0-9]*/","",$title);
		 	
	} else {
	 	
	 	$node_text = addTextNode($doc,$container,"text",$url);
	 	$id=preg_replace("/[^a-zA-Z0-9]*/","",$url);
	}
	
	$node_id = addAttribute($doc,$container,"id",$id);
 
 	
 	// Add the CMS as part of the title in the IA heirarchy.
 	
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
	 			
	 			$parent=preg_replace("/[^a-zA-Z0-9]*/","",$iaparent);
	 			$node_level->nodeValue="2";
	 		}
	 		
	 		
	 	} else {
	 		
	 		$node_level->nodeValue="1";
	 		
	 	}
	 	
	 	
	 } else {
	 	
	 	// Systems heirarchy
		 	
		 if ($cms) {
			 	$parent=preg_replace("/[^a-zA-Z0-9]*/","",$cms);
			 }  
		 
	 }
	 
	 
	 if ($parent != "") {
		 	$node_parent = addTextNode($doc,$container,"parent",$parent);
	 }
	 
	 
	 $cells->appendChild($container);
	
	
	
	}
}

if ($mode_systems) {
		
	natcasesort($systems);
	
	// We need first-level heirarchy for the systems view...
	
	 foreach ($systems as $i => $system) {
	 
	 	
		 $cell = $doc->createElement("cell");
		 $cell = $cells->appendChild($cell);
		 
		 $order+=100;
	 	 $node_order = addTextNode($doc,$cell,"order",$order);
	
		 $node_text = addTextNode($doc,$cell,"text",$system);
		 
		 $id=preg_replace("/[^a-zA-Z0-9]*/","",$system);
		 
		 $node_id = addAttribute($doc,$cell,"id",$id);
		
		 
		 // add level attribute. All are level 2. CMSes are level 1. 
		
		 $node_level = addTextNode($doc,$cell,"level","1");
	 	
	 }
	 
} else {
	
	// Clean up nesting.
	
	setNesting($doc,$cells);
	setNesting($doc,$cells);
	setNesting($doc,$cells);
	sortSitesByName($doc,$cells);
	
}



 
 
$result = file_put_contents($outputFilename,$doc->saveXML()) ;
if (!$result) {
	exit("Couldn't write to $outputFilename \n");}
	



function sortSitesByName($doc,$cells) {


   	$results=$cells->getElementsByTagName("cell");
	foreach($results as $result) {
		$this_id=$result->getAttribute("id"); 
   		$levels[$this_id] = $result->getElementsByTagName("level")->item(0)->nodeValue;	
   	}
		
	ksort($levels);
	$order=100;
	
	foreach($levels as $id => $level) {
		foreach($results as $result) {
   			$result_id=$result->getAttribute("id"); 
   			if ($result_id==$id) {
    			try {
    				$result->getElementsByTagName("order")->item(0)->nodeValue=$order;
    				$order+=100;
    			} catch (Exception $e) {
    			}
    			
   			}
		}
	}
	
}



function setNesting($doc,$cells) {

     	$results=$cells->getElementsByTagName("cell");
		foreach($results as $result) {
			$this_id=$result->getAttribute("id"); 
     		$levels[$this_id] = $result->getElementsByTagName("level")->item(0)->nodeValue;	
     	}
			
		foreach($levels as $id => $level) {
			$parent="";
     		foreach($results as $result) {
     			$result_id=$result->getAttribute("id"); 
     			if ($result_id==$id) {
	     			try {
	     				$parents=$result->getElementsByTagName("parent");
	     				if ($parents->length>0) {
	     					$parent=$result->getElementsByTagName("parent")->item(0)->nodeValue;
	     				}
	     				
		     		} catch (Exception $e) {
		     		} 
	     		}
     		}
     		
     		if ($parent != "" && $parent != $id) {
     			if (array_key_exists($parent,$levels)==false) {
     				trigger_error("The parent site $parent doesn't exist. $id will not appear in the output.
     				\n",E_USER_WARNING);
     				continue;
     			}
     			
     			$parent_level=$levels[$parent];
     			
     			if ($parent_level==$level) {
     				$levels[$id]=$levels[$id]+1;
     				$search=$cells->getElementsByTagName("cell");
	     			foreach($search as $candidate) {
	     				$candidate_id=$candidate->getAttribute("id"); 
						if ($candidate_id==$id) {
							$candidate->getElementsByTagName("level")->item(0)->nodeValue = $levels[$id];	
						}
	     			}
     			}
     		}
		}	
}

	


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