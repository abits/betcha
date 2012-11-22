<?php
/*
 * This is a simple script to generate an ant wrapper file for a gnu
 * makefile.
 *
 * Author:    Christoph Martel  <chris@codeways.org>
 * Date:      23 Nov 2012 00:12:38
 * Copyright: Christoph Martel, 2012
 * Licence:   GPLv3
 */

/**
 * Retrieve targets from a gnu make file.
 *
 * @param $filename makefile to parse
 * @return Array holding relevant target data
 */
function parseMakefile($filename)
{
  $variables = array();
  $targets = array();

  if ($fh = fopen($filename, "r")) 
  {
    while(!feof($fh)) 
    {
      $line = fgets($fh);
      if (preg_match('~^[a-zA-Z0-9]~', $line) 
          && preg_match('~=~', $line))
      {
        $defs = explode('=', $line);
        $variables[$defs[0]] = $defs[1];
      }
      if ((preg_match('~^[a-zA-Z0-9]~', $line)
           && preg_match('~:~', $line)))
      {
        list($target, $deps) = explode(':', $line);
        $targets[] = array('target' => $target, 'deps' => $deps);
      }
    }
    fclose($fh);
  }

  return array_merge(array('variables' => $variables), array('targets' => $targets));
}

function generateAnt(Array $values, Array $options)
{
  
  if (!isset($options['description']))
  {
    $options['description'] = 'Do not edit!  This file was generated and any changes will be overwritten.';
  }
  if (!isset($options['defaultTarget']))
  {
    $options['defaultTarget'] = 'ant-default';
  }
  if (!isset($options['projectName']))
  {
    $options['projectName'] = basename(getcwd());
  }

  $ant = new SimpleXMLElement('<project></project>');
  $ant->addAttribute('name', $options['projectName']);
  $ant->addAttribute('basedir', '.');
  $ant->addAttribute('default', $options['defaultTarget']);
  $ant->addChild('description', $options['description']);
  foreach ($values['targets'] as $target)
  {
    $targetElement = $ant->addChild('target');
    $targetElement->addAttribute('name', $target['target']);
    $execElement = $targetElement->addChild('exec');
    $execElement->addAttribute('dir', '.');
    $execElement->addAttribute('executable', 'make');
    $execElement->addAttribute('failonerror', 'true');
    $argElement = $execElement->addChild('arg');
    $argElement->addAttribute('value', $target['target']);
  }
  
  return $ant;
}

function writeBuildfile($ant, $filename = null)
{
  if (!$filename)
  {
    $filename = 'php://stdout';
  }
  $buildfile = new DOMDocument('1.0');
  $buildfile->preserveWhiteSpace = false;
  $buildfile->formatOutput = true;
  $buildfile->loadXML($ant->asXML());
  $buildfile->save($filename);
}

function parseOptions()
{
  $options = array();
  $shortopts = "";  
  $longopts  = array(
     "outfile:",
     "infile:",
     "project:",
     "stdout",
     "help",
     );
  $opts = getopt($shortopts, $longopts);
  if (isset($opts['outfile']))
  {
    $options['outfile'] = $opts['outfile'];
  }
  if (isset($opts['infile']))
  {
    $options['infile'] = $opts['infile'];
  }
  if (isset($opts['project']))
  {
    $options['projectName'] = $opts['project'];
  }
  if (isset($opts['stdout']))
  {
    $options['stdout'] = $opts['stdout'];
  }
  if (isset($opts['help']))
  {
    $options['help'] = $opts['help'];
    printUsage();
    exit(0);
  }

  return $options;
}

function printUsage()
{

  $usage = "Generate ant wrapper for gnu makefiles.\n";
  $usage .= "Syntax: php make2ant.php [Optionen]\n";
  $usage .= "Options:\n";
  $usage .= "  --outfile\tName of ant build file to generate ['build.xml'].\n";
  $usage .= "  --project\tName of project [name of current directory].\n";
  $usage .= "  --stdout\tPrint generated xml to stdout.\n";
  $usage .= "  --help\tPrint this message.\n\n";
  echo $usage;  
}


$options = parseOptions(); 

$infile = isset($options['infile']) ? $options['infile'] : 'Makefile';
$outfile = isset($options['outfile']) ? $options['outfile'] : 'build.xml';
$values = parse_Mkefile($infile);
$ant = generateAnt($values, $options);
if (isset($options['stdout']))
{
  writeBuildfile($ant);
}
writeBuildfile($ant, $outfile);
exit(0);
