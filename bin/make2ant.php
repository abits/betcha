<?php
/*
 * This is a simple script to generate an ant wrapper file for a gnu
 * makefile.
 *
 * Author:    Christoph Martel  <chris@codeways.org>
 * Date:      23 Nov 2012
 * Copyright: Christoph Martel, 2012
 * License:   GPLv3  <http://www.gnu.org/licenses/gpl.html>
 */

/**
 * Parses makefiles for wrapper targets.
 */
class Parser
{
    /**
     * Parse makefile and return relevant data for ant wrapper.
     *
     * @param $filename String
     * @return array Relevant data retrieved from Makefile
     */
    public static function parseFile($filename)
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
}


/**
 * Generates xml for ant build files from parser output.
 */
class Generator
{
    /**
     * @var Array Holds user options.
     */
    protected $options;

    /**
     * @param array $options
     */
    function __construct(Array $options)
    {
        $this->options = $options;
    }


    /**
     * Generate ant xml from parser output.
     *
     * @param array $values
     * @return SimpleXMLElement
     */
    public function generate(Array $values)
    {
        $ant = new SimpleXMLElement('<project></project>');
        $ant->addAttribute('name', $this->options['projectName']);
        $ant->addAttribute('basedir', '.');
        $ant->addAttribute('default', $this->options['defaultTarget']);
        $ant->addChild('description', $this->options['description']);
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
}


/**
 * Generic output handler.
 */
class Writer
{
    /**
     * @var Array Holds user options.
     */
    protected $options;

    public function __construct(Array $options)
    {
        $this->options = $options;
    }


    /**
     * Write ant xml to file.  Calls write() method.
     *
     * @param SimpleXMLElement $ant xml document
     * @param null $filename Optional filename to use.
     * @throws Exception
     */
    public function writeBuildFile(SimpleXMLElement $ant, $filename = null)
    {
        $buildfile = new DOMDocument('1.0');
        $buildfile->preserveWhiteSpace = false;
        $buildfile->formatOutput = true;
        $buildfile->loadXML($ant->asXML());
        if (isset($this->options['outfile']) && !$filename)
        {
            $this->write($buildfile, $this->options['outfile']);
        }
        elseif (!is_null($filename))
        {
            $this->write($buildfile, $filename);
        }
        else
        {
            $errorMessage = sprintf('I do not have a file to write to.');
            throw new Exception($errorMessage);
        }
    }


    /**
     * Write ant xml to stdout.
     *
     * @param SimpleXMLElement $ant
     */
    public function stdoutBuildFile(SimpleXMLElement $ant)
    {
        $this->writeBuildFile($ant, 'php://stdout');
    }

    /**
     * Print help message.
     */
    public function writeUsage()
    {
        echo $this->options['usage'];
    }

    /**
     * Actually write stuff to file.
     *
     * @param DOMDocument $buildfile
     * @param $filename
     * @throws Exception
     */
    protected function write(DOMDocument $buildfile, $filename)
    {
        try
        {
            $buildfile->save($filename);
        }
        catch (Exception $e)
        {
            $errorMessage = sprintf('Cannot write file %s.', $filename);
            throw new Exception($errorMessage);
        }
    }
}


/**
 * Container for user options and default settings.
 */
class Options
{
    /**
     * @return array
     */
    public static function parseOptions()
    {
        $options = array();
        $options['description'] = 'Do not edit!  This file was generated and any changes will be overwritten.';
        $options['defaultTarget'] = 'ant-default';

        $options['usage'] = "Generate ant wrapper for gnu makefiles.\n";
        $options['usage'] .= "Syntax: php make2ant.php [Optionen]\n";
        $options['usage'] .= "Options:\n";
        $options['usage'] .= "  --outfile\tName of ant build file to generate ['build.xml'].\n";
        $options['usage'] .= "  --project\tName of project [name of current directory].\n";
        $options['usage'] .= "  --stdout\tPrint generated xml to stdout.\n";
        $options['usage'] .= "  --help\tPrint this message.\n\n";

        $shortopts = "";
        $longopts  = array(
            "outfile:",
            "infile:",
            "project:",
            "stdout",
            "help",
        );

        $opts = getopt($shortopts, $longopts);

        // special case: user wants help, we offer it and exit
        if (isset($opts['help']))
        {
            $writer = new Writer($options);
            $writer->writeUsage();
            exit(0);
        }

        if (isset($opts['outfile']))
        {
            $options['outfile'] = $opts['outfile'];
        }
        else
        {
            $options['outfile'] = 'build.xml';
        }
        if (isset($opts['infile']))
        {
            $options['infile'] = $opts['infile'];
        }
        else
        {
            $options['infile'] = 'Makefile';
        }
        if (isset($opts['project']))
        {
            $options['projectName'] = $opts['project'];
        }
        else
        {
            $options['projectName'] = basename(getcwd());
        }
        if (isset($opts['stdout']))
        {
            $options['stdout'] = $opts['stdout'];
        }

        return $options;
    }
}


/**
 * Main controller.
 */
class Main
{
    /**
     * @var Array Hold user options.
     */
    protected $options;

    /**
     * @var Writer
     */
    public $writer;

    /**
     * @var Generator
     */
    public $generator;

    function __construct()
    {
        $options = new Options();
        $this->options = $options::parseOptions();
        $this->writer = new Writer($this->options);
        $this->generator = new Generator($this->options);
    }

    /**
     * Main process.
     */
    public function run()
    {
        $values = Parser::parseFile($this->options['infile']);
        $ant = $this->generator->generate($values);
        if (isset($this->options['stdout']))
        {
            $this->writer->stdoutBuildFile($ant);
        }
        $this->writer->writeBuildfile($ant);
    }
}

// main flow
$main = new Main();
$main->run();
exit(0);
