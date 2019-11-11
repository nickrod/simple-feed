#!/usr/bin/php

<?php

//

include "/var/www/relayo.com/includes/config2.php";

//

$query = "SELECT id, title, description, postalcode, country, company, salary, education, jobtype, experience, cdate, cats.name AS category FROM gigs LEFT JOIN (SELECT gig_id, GROUP_CONCAT(TRIM(name) SEPARATOR 0x1D) AS name FROM gig_tags, categories WHERE gig_tags.cat_id = categories.id GROUP BY gig_id) cats ON gigs.id = cats.gig_id WHERE status = 1 AND status_user = 1 AND cdate >= (CURRENT_DATE() - INTERVAL 60 DAY) ORDER BY title";

// vars

$savefile = "/var/www/relayo.com/xml/jobs";

// create dom

$domtree = new DOMDocument('1.0', 'UTF-8');
$domtree->formatOutput = true;
$domtree->preserveWhiteSpace = false;

// create root element and append it to the document created

$xmlRootNode = $domtree->appendChild($domtree->createElement("source"));
$xmlRootNode->appendChild($domtree->createElement("publisher", "Relayo"));
$xmlRootNode->appendChild($domtree->createElement("publisherurl", "https://www.relayo.com"));
$xmlRootNode->appendChild($domtree->createElement("lastBuildDate", date('c')));

// reset query

$result = $con->select($query);

// loop through request data

while ($row = $result->fetch_assoc())
{
  // create job node and append to root

  $currentJobNode = $xmlRootNode->appendChild($domtree->createElement("job"));
  $gig_id = $row['id'];
  $gig_name = fix($row['title']);
  $gig_date = $row['cdate'];
  $gig_date_obj = new DateTime($gig_date);
  $gig_date_lastmod = $gig_date_obj->format('Y-m-d\TH:i:sP');
  $url = "https://www.relayo.com/gigs/$gig_id/$gig_name.htm";

  // add gig cdata

  $titleNode = $currentJobNode->appendChild($domtree->createElement('title'));
  $titleNode->appendChild($domtree->createCDATASection(noHTML($row['title'])));

  //

  $jobDateNode = $currentJobNode->appendChild($domtree->createElement('date'));
  $jobDateNode->appendChild($domtree->createCDATASection($gig_date_lastmod));

  //

  $referenceNumberNode = $currentJobNode->appendChild($domtree->createElement('referencenumber'));
  $referenceNumberNode->appendChild($domtree->createCDATASection($gig_id));

  //

  $urlNode = $currentJobNode->appendChild($domtree->createElement('url'));
  $urlNode->appendChild($domtree->createCDATASection($url));

  //

  $companyNode = $currentJobNode->appendChild($domtree->createElement('company'));
  $companyNode->appendChild($domtree->createCDATASection(noHTML($row['company'])));

  //

  $countryNode = $currentJobNode->appendChild($domtree->createElement('country'));
  $countryNode->appendChild($domtree->createCDATASection(noHTML($row['country'])));

  //

  $zipCodeNode = $currentJobNode->appendChild($domtree->createElement('postalcode'));
  $zipCodeNode->appendChild($domtree->createCDATASection(noHTML($row['postalcode'])));

  //

  $descriptionNode = $currentJobNode->appendChild($domtree->createElement('description'));
  $descriptionNode->appendChild($domtree->createCDATASection(noHTML($row['description'])));

  //

  $salaryNode = $currentJobNode->appendChild($domtree->createElement('salary'));
  $salaryNode->appendChild($domtree->createCDATASection(noHTML($row['salary'])));

  //

  $educationNode = $currentJobNode->appendChild($domtree->createElement('education'));
  $educationNode->appendChild($domtree->createCDATASection(noHTML($row['education'])));

  //

  $jobTypeNode = $currentJobNode->appendChild($domtree->createElement('jobtype'));
  $jobTypeNode->appendChild($domtree->createCDATASection(noHTML($row['jobtype'])));

  //

  $categoryNode = $currentJobNode->appendChild($domtree->createElement('category'));
  $categoryNode->appendChild($domtree->createCDATASection(catSep($row['category'])));

  //

  $experienceNode = $currentJobNode->appendChild($domtree->createElement('experience'));
  $experienceNode->appendChild($domtree->createCDATASection(noHTML($row['experience'])));
}

//

$result->close();

// print the xml

$domtree->save($savefile . '.xml');

// compress file

gzCompressFile($savefile . '.xml');

//

function gzCompressFile($source, $level = 9)
{ 
  $dest = $source . '.gz'; 
  $mode = 'wb' . $level; 
  $error = false; 

  //

  if ($fp_out = gzopen($dest, $mode))
  { 
    if ($fp_in = fopen($source, 'rb'))
    { 
      while (!feof($fp_in))
      {
        gzwrite($fp_out, fread($fp_in, 1024 * 512)); 
      }

      //

      fclose($fp_in); 
    }
    else
    {
      $error = true; 
    }

    //

    gzclose($fp_out); 
  }
  else
  {
    $error = true; 
  }

  //

  if ($error)
  {
    return false; 
  }
  else
  {
    return $dest; 
  }
} 

//

function fix($str, $limit = 80)
{ 
  $str = str_replace(' ', '-', $str);
  $str = preg_replace('/[^A-Za-z0-9\-]/', '', strtolower($str));
  $str = preg_replace('/-+/', '-', $str);
  $str = substr($str, 0, $limit);
  $str = trim($str, '-');
  $str = trim($str);
  
  //
  
  return $str;
}

//

function catSep($str)
{
  $str = noHTML($str);

  //

  return str_replace(chr(29), ',', $str);
}

?>
