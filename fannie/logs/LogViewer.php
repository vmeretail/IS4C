<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of CORE-POS.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../classlib2.0/FannieAPI.php');
}

class LogViewer extends FanniePage 
{

    protected $title = 'Fannie - Logs';
    protected $header = 'View Logs';
    protected $must_authenticate = true;
    protected $auth_classes = array('admin');

    public $description = '[Log Viewer] shows Fannie\'s log files through the web.';
    public $themed = true;

    private $mode = 'list';

    public function preprocess()
    {
        global $FANNIE_LOG_COUNT;
        $fn = FormLib::get_form_value('logfile', false);
        if ($fn !== false) {
            $this->mode = 'show';
            if (FormLib::get_form_value('rotate', false) !== false) {
                $this->doRotate(base64_decode($fn));
            }
        }

        return true;
    }

    public function body_content()
    {
        if ($this->mode == 'list') {
            return $this->list_content();
        } elseif ($this->mode == 'show') {
            return $this->show_content();
        }
    }

    private function list_content()
    {
        $ret = "Choose a log file:<ul>";
        $dh = opendir(dirname(__FILE__));
        while (($file = readdir($dh)) !== false) {
            if ($file[0] == "." || $file == "index.php" || $file == 'LogViewer.php') {
                continue;
            }
            if (is_numeric(substr($file,-1))) { // log rotations
                continue;
            }
            if (is_dir($file)) { // someone put a directory here
                continue;
            }
            $ret .= sprintf('<li><a href="%s?logfile=%s">%s</a></li>',
                $_SERVER['PHP_SELF'],
                base64_encode($file),
                $file);
        }
        $ret .= "</ul>";

        return $ret;
    }

    public function css_content()
    {
        if ($this->mode == 'show') {
            // force word wrap
            return '
                pre {
                     white-space: pre-wrap;       /* css-3 */
                     white-space: -moz-pre-wrap !important;  /* Mozilla, since 1999 */
                     white-space: -pre-wrap;      /* Opera 4-6 */
                     white-space: -o-pre-wrap;    /* Opera 7 */
                     word-wrap: break-word;       /* Internet Explorer 5.5+ */
                }
            ';
        }

        return '';
    }

    public function show_content()
    {
        global $FANNIE_PRETTY_LOGS, $FANNIE_URL, $FANNIE_LOG_LIMIT;

        $fn = base64_decode(FormLib::get_form_value('logfile'));
        $log = $this->getLogFile($fn);

        $ret = '<a href="LogViewer.php">Back to listing</a>';
        if ($log){
            $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            $ret .= sprintf('<a href="LogViewer.php?logfile=%s&rotate=yes"
                onclick="return confirm(\'Are you sure?\');">Rotate
                log</a>',base64_encode($fn));
        }
        $ret .= '<hr />';
        if ($log === false) {
            $ret .= "<i>Error opening logfile</i><br />";
        } elseif (empty($log)) {
            $ret .= "<i>File is empty</i><br />";
        } else {

            if ($FANNIE_PRETTY_LOGS != 0) {
                $this->addScript($FANNIE_URL.'src/javascript/syntax-highlighter/scripts/jquery.syntaxhighlighter.min.js');
                $highlite_cmd = sprintf('
                        $.SyntaxHighlighter.init({
                        \'baseUrl\' : \'%s\',
                        \'prettifyBaseUrl\': \'%s\'        
                        });',
                    $FANNIE_URL.'src/javascript/syntax-highlighter',
                    $FANNIE_URL.'src/javascript/syntax-highlighter/prettify');
                $this->addOnloadCommand($highlite_cmd);
            }
        
            $ret .= '<code>';
            $ret .= '<pre class="highlight">';
            $ret .= $log;
            $ret .= '</pre>';
            $ret .= '</code>';
        }
        
        return $ret;
    }

    public function getLogFile($fn, $num_lines=0)
    {
        if (!file_exists($fn) || !is_file($fn) || !is_readable($fn)) {
            return false;
        }

        if ($num_lines == 0) {
            return file_get_contents($fn);
        } else {
            $ret = '';
            $fp = fopen($fn, 'r');
            $line = 0;
            while (!feof($fp) && $line < $num_lines) {
                $ret .= fgets($fp);
                $line++;
            }

            return $ret;
        }
    }

    private function doRotate($fn)
    {
        global $FANNIE_LOG_COUNT;
        // don't rotate empty files
        if (filesize($fn) == 0) {
            return false;
        }

        for ($i=$FANNIE_LOG_COUNT-1; $i>=0; $i--) {
            if (file_exists($fn.".".$i)) {
                rename($fn.".".$i,$fn.".".($i+1));
            }
        }

        if (file_exists($fn)) {
            rename($fn,$fn.".0");
        }

        $fp = fopen($fn,"w");
        fclose($fp);

        return true;
    }

    public function helpContent()
    {
        return '<p>View Fannie\'s logs through the browser</p>
            <p><em>dayend.log</em> logs information about scheduled
            tasks run and their results.</p>
            <p><em>php-errors.log</em> logs developer-oriented information
            about program warnings and errors.</p>
            <p><em>queries.log</em> logs developer-oriented information
            about database errors.</p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
        $this->mode = 'show';
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
        $phpunit->assertNotEquals(0, strlen($this->css_content()));
    }
}

FannieDispatch::conditionalExec();

