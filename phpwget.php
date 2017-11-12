<?php
/**
 * This script can be download file.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

namespace PhpWget;

class downloadFile {
    /**
     * Each character in this string will be used as option characters
     *
     * @var string $optionIndex
     */
    private $optionIndex = 'hu:f::';

    /**
     * @var array $options
     */
    private $options;

    /**
     * @var string|null $fileURL
     */
    private $fileURL;

    /**
     * @var string|null $fileDir
     */
    private $fileDir;

    /**
     * @var string $helpMassage
     */
    private $helpMassage = <<<STR
Usage: php <this script name> -u=<file URL> [options]
   php <this script name> -h

  -f The file path you want to save, by default it will be saved to the current working directory
  -h This help\n
STR;

    /**
     * @var array $errorMassage
     */
    private $errorMassages = [
        1 => "[Notice] You have not typed 'u' option, the script exit.\n",
        2 => "[Notice] The URL you entered is not in the correct format, please check the URL you entered.\n",
        3 => "[Warning] You did not load curl extension, the script does not work.\n",
        4 => "[Warning] PhpWget does not support your operating system.\n"
        ];

    /**
     * @var resource $curlResource
     */
    private $curlResource;

    public function __construct() {
        echo "PHP runs in cli mode.\n";

        $this->checkPHPEnvironment();

        $this->options = getopt( $this->optionIndex );

        $this->displayHelpMassage();
        $this->checkOptions();
        $this->fileURL = $this->options['u'];
        if ( isset( $this->options['f'] ) and !$this->options['f'] === false) {
            $this->fileDir = $this->options['f'];
            $this->checkFileDir();
        }
        $this->checkURL();

        $this->curlResource = curl_init( $this->fileURL );
    }

    /**
     * Check if the server meets the requirements
     */
    private function checkPHPEnvironment() {
        if ( !extension_loaded( 'curl' ) ) {
            echo $this->errorMassages[3];
            die ( 1 );
        }
    }

    private function displayHelpMassage() {
        if ( isset( $this->options['h'] ) ) {
            echo $this->helpMassage;
            die ( 1 );
        }
    }

    /**
     * Check if the user-entered options meet the requirements
     */
    private function checkOptions() {
        if ( !isset( $this->options['u'] ) ) {
            echo $this->errorMassages[1];
            echo $this->helpMassage;
            die ( 1 );
        }
    }

    /**
     * Check the URL user entered
     */
    private function checkURL() {
        // Check the format of the URL is correct
        $pattern = '/\bhttps?:\/{2}[a-zA-Z0-9.]*\b/';
        $i = preg_match( $pattern, $this->fileURL );
        if ( $i === 0 ) {
            echo $this->errorMassages[2];
            die ( 1 );
        }
    }

    /**
     * Check PHP can write to the target directory
     */
    private function checkFileDir() {
        if ( is_dir( $this->fileDir ) ) {
            if ( PHP_OS === 'WINNT' or PHP_OS === 'WIN32' or PHP_OS === 'Windows') {
                $pattern = '/[a-zA-Z]:[\/\\\\]([a-zA-Z0-9\s]*[\/\\\\])*[a-zA-Z0-9\s]*/';
            } elseif ( PHP_OS === 'Linux' or PHP_OS === 'Unix') {
                $pattern = '/^\/?([a-zA-Z0-9]*\/)*[a-zA-Z0-9]*/';
            } else {
                echo $this->errorMassages[4];
                die ( 1 );
            }
        } else {
            if ( PHP_OS === 'WINNT' or PHP_OS === 'WIN32' or PHP_OS === 'Windows') {
                $pattern = '/[a-zA-Z]:[\/\\\\]([a-zA-Z0-9\s]*[\/\\\\])*/';
            } elseif ( PHP_OS === 'Linux' or PHP_OS === 'Unix') {
                $pattern = '/^\/?([a-zA-Z0-9]*\/)*/';
            } else {
                echo $this->errorMassages[4];
                die ( 1 );
            }
        }
        preg_match( $pattern, $this->fileDir, $matches);

        if ( !is_writable( $matches[0] ) ) {
            die ( "[Warning] PHP can't write to $matches[0], please make sure PHP can be written to the target directory\n" );
        }
    }

    public function download() {
        curl_setopt( $this->curlResource, CURLOPT_RETURNTRANSFER, true);
        curl_setopt( $this->curlResource, CURLOPT_AUTOREFERER, true);
        $filename = $this->getFilename();
        file_put_contents( $filename, curl_exec( $this->curlResource ) );
    }

    /**
     * According to the URL to determine the file name.
     */
    private function getFilename() {
	   if ( isset( $this->fileDir ) ) {
            if ( is_dir( $this->fileDir ) ) {
                $filename = $this->fileDir.'/index.html';
            } else {
                $filename = $this->fileDir;
            }
        } else {
            $pattern = '/\bhttps?:\/\/\b[a-zA-Z0-9.]*\/?/';
            $filename = preg_replace( $pattern, null, $this->fileURL );
            if ( $filename === '' ) {
                $filename = 'index.html';
            }
        }
        return $filename;
    }

    public function __destruct() {
        if ( is_resource( $this->curlResource ) ) {
            curl_close( $this->curlResource );
        }
    }
}

$downloadFile = new \PhpWget\downloadFile();
$downloadFile->download();
