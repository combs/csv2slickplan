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

$section = $doc->createElement('section');
$section = $sitemap->appendChild($section);

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




// Loop through each row creating a <row> node with the correct data

while (($row = fgetcsv($inputFile)) !== FALSE)
{
 $container = $doc->createElement('row');

 foreach ($headers as $i => $header)
 {
  $child = $doc->createElement($header);
  $child = $container->appendChild($child);
     $value = $doc->createTextNode($row[$i]);
     $value = $child->appendChild($value);
 }

 $sitemap->appendChild($container);
}

file_put_contents($outputFilename,$doc->saveXML());


?>