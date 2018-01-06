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

/**
 * @class Main class
 */
class PhpWget {
    /**
     * Each character in this string will be used as option characters
     * @var string $optionIndex
     */
    protected $optionIndex = 'hu:f::';

    /**
     * Store long option elements
     * @var array $longopts
     */
    protected $longopts = [
        'UZ' // Command PhpWget extract the downloaded archive
    ];

    /**
     * @var array $options
     */
    protected $options;

    /**
     * @var string|null $fileURL
     */
    protected $fileURL;

    /**
     * @var string|null $fileDir
     */
    protected $fileDir;

    /**
     * @var string $helpMassage
     */
    protected $helpMassage = <<<STR
Usage: php <this script name> -u=<file URL> [options]
   php <this script name> -h

  -f The file path you want to save, by default it will be saved to the current working directory
  -h This help

  --UZ Extract the archive after download\n
STR;

    /**
     * @var array $errorMassage
     */
    protected $errorMassages = [
        1 => '[Notice] You have not typed \'u\' option, the script exit.',
        2 => '[Notice] The URL you entered is not in the correct format, please check the URL you entered.',
        3 => '[Warning] You did not load curl extension, the script does not work.',
        4 => '[Warning] PhpWget does not support your operating system.',
        5 => '[Warning] This script must be run in cli mode.',
        6 => '[Error] PhpWget can not download file.',
        7 => '[Warning] You did not load phar extension, PhpWget can\'t extract archive.',
        8 => '[Notice] Your version of PHP is lower than version 5.5.24 and is likely to go wrong when extracting BSD generated tar file.'
    ];

    /**
     * Stores regular expression pattenrs
     * @var array $rePatterns
     */
    protected $rePatterns = [
        'win' => [
            1 => '/[a-zA-Z]:[\/\\\\]([a-zA-Z0-9\s]*[\/\\\\])*/'
        ],
        'unix' => [
            1 => '/^\/?([a-zA-Z0-9]*\/)*[a-zA-Z0-9]*/', //Used to match the full path
            2 => '/^\/?([a-zA-Z0-9]*\/)*/' //Used to match the folder path
        ],
        'web' => [
            1 => '/\bhttps?:\/{2}([a-zA-Z0-9-]*\.)*[a-zA-Z]*\/?\b/', // Used to match domain name
            2 => '/\bhttps?:\/{2}\b/', // Used to match 'http' protocol name
            3 => '/([a-zA-Z0-9_&%$#()-]*\/)*/'
        ]
    ];

    /**
     * @var bool|null $pharLoaded
     */
    protected $pharLoaded;

    /**
     * @var array $shellColor Store the color code
     */
    protected $shellColor = [
        'red' => '31m',
        'green' => '32m'
    ];

    /**
     * Render the font color of the output
     *
     * downloadFile::shellOutput callback function
     *
     * @param string $input
     * @param string $color
     * @return string
     */
    private function setShellColor($input, $color) {
        $output = "\033[{$this->shellColor[$color]}" . $input . " \033[0m";
        return $output;
    }

    /**
     * Output
     */
    protected function shellOutput($input, $color = 'red') {
        if ( PHP_OS === 'Linux' || PHP_OS === 'Unix' ) {
            $output = $this->setShellColor( $input, $color ) . "\n";
        } else {
            $output = $input . "\n";
        }
        echo $output;
    }

    /**
     * Check if the server meets the requirements
     */
    private function checkPHPEnvironment() {
        if ( !extension_loaded( 'curl' ) ) {
            $this->shellOutput( $this->errorMassages[3] );
            die ( 1 );
        }
        // Check if this script is running in cli mode
        if ( php_sapi_name() !== 'cli' ) {
            $this->shellOutput( $this->errorMassages[5] );
            die ( 1 );
        }
        if ( !extension_loaded( 'phar' ) ) {
            $this->pharLoaded = false;
        }
        /**
         * Compare PHP version, if the current version is lower than 5.5.24,
         * then output a notice
         * @Bug https://github.com/RazeSoldier/PhpWget/issues/1
         */
        if ( version_compare( PHP_VERSION, '5.5.24', '<' ) ) {
            $this->shellOutput( $this->errorMassages[8] );
        }
    }

    public function __construct() {
        echo "PHP runs in cli mode.\n";
        $this->checkPHPEnvironment();
    }
}

/**
 * Used to download file
 * @class Download action class
 */
class downloadFile extends PhpWget {
    /**
     * @var resource $curlResource
     */
    private $curlResource;

    public function __construct() {
        $this->options = getopt( $this->optionIndex, $this->longopts );

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
     * Display help massage, if there is 'u' option
     */
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
            $this->shellOutput( $this->errorMassages[1] );
            echo $this->helpMassage;
            die ( 1 );
        }
    }

    /**
     * Check the URL user entered
     */
    private function checkURL() {
        $pattern[1] = $this->rePatterns['web'][1];
        $pattern[2] = $this->rePatterns['web'][2];

        // Auto-fill 'http' protocol name,
        // if user entered the URL without the protocol name
        $matchResult2 = preg_match( $pattern[2], $this->fileURL );
        if ( $matchResult2 === 0 ) {
            $this->fileURL = 'http://' . $this->fileURL;
        }
        // Check the format of the URL is correct
        $matchResult1 = preg_match( $pattern[1], $this->fileURL );
        if ( $matchResult1 === 0 ) {
            $this->shellOutput( $this->errorMassages[2] );
            die ( 1 );
        }
    }

    /**
     * Check PHP can write to the target directory
     */
    private function checkFileDir() {
        if ( is_dir( $this->fileDir ) ) {
            switch( PHP_OS ) {
                case 'WINNT':
                case 'WIN32':
                case 'Windows':
                    $pattern = $this->rePatterns['win'][1];
                    break;
                case 'Linux':
                case 'Unix':
                    $pattern = $this->rePatterns['unix'][1];
                    break;
                default:
                    $this->shellOutput( $this->errorMassages[4] );
                    die ( 1 );
            } //end switch
        } else {
            switch( PHP_OS ) {
                case 'WINNT':
                case 'WIN32':
                case 'Windows':
                    $pattern = $this->rePatterns['win'][1];
                    break;
                case 'Linux':
                case 'Unix':
                    $pattern = $this->rePatterns['unix'][2];
                    break;
                default:
                    $this->shellOutput( $this->errorMassages[4] );
                    die ( 1 );
            } //end switch
        } //end if
        preg_match( $pattern, $this->fileDir, $matches);

        if ( !is_writable( $matches[0] ) ) {
            $this->shellOutput( "[Warning] PHP can't write to $matches[0], please make sure PHP can be written to the target directory" );
            die ( 1 );
        }
    }

    /**
     * Set some option for a cURL transfer
     */
    private function setCurlOpt() {
        curl_setopt( $this->curlResource, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $this->curlResource, CURLOPT_AUTOREFERER, true );
        // Stop cURL from verifying the peer's certificate
        curl_setopt( $this->curlResource, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $this->curlResource, CURLOPT_FOLLOWLOCATION, true );
    }

    public function download() {
        $this->setCurlOpt();
        if ( !curl_exec( $this->curlResource ) ) {
            $this->shellOutput( $this->errorMassages[6], 'red' );
            die ( 1 );
        }
        $filedir = $this->getFileDir();
        $download = file_put_contents( $filedir, curl_exec( $this->curlResource ) );
        $this->displayConcludingWords($download);

        if ( isset($this->options['UZ'] ) ) {
            $unZip = new unZip( $this->getFileName() );
            $unZip->unZip();
        }
    }

    /**
     * According to the URL to determine the file directory.
     */
    private function getFileDir() {
        if ( isset( $this->fileDir ) ) {
            if ( is_dir( $this->fileDir ) ) {
                $filedir = $this->fileDir.'/index.html';
            } else {
                $filedir = $this->fileDir;
            }
        } else {
            $pattern[1] = $this->rePatterns['web'][1];
            $pattern[2] = $this->rePatterns['web'][3];
            $urlFilename = preg_replace( $pattern[1], null, $this->fileURL );
            $filedir = preg_replace( $pattern[2], null, $urlFilename );
            if ( $filedir === '' ) {
                $filedir = 'index.html';
            }
        }
        return $filedir;
    }

    /**
     * Display concluding words, if the file successfully written to the file system
     */
    private function displayConcludingWords($check) {
        if ( isset( $this->fileDir ) ) {
            $targetDir = $this->fileDir;
        } else {
            $targetDir = getcwd();
        }

        if ( !$check === false ) {
            $this->shellOutput( "{$this->getFileName()} successfully download to $targetDir", 'green');
        }
    }

    /**
     * According to the URL to determine the file name.
     */
    private function getFileName() {
        if ( isset( $this->fileDir ) ) {
            if ( is_dir( $this->fileDir ) ) {
                $filename = 'index.html';
            } else {
                switch( PHP_OS ) {
                    case 'WINNT':
                    case 'WIN32':
                    case 'Windows':
                        $pattern = $this->rePatterns['win'][1];
                        break;
                    case 'Linux':
                    case 'Unix':
                        $pattern = $this->rePatterns['unix'][2];
                        break;
                    default:
                        $this->shellOutput( $this->errorMassages[4] );
                        die ( 1 );
                }
                $filename = preg_replace( $pattern, null, $this->fileDir );
            }
        } else {
            // Default action, if 'f' option is not specified
            $pattern[1] = $this->rePatterns['web'][1];
            $pattern[2] = $this->rePatterns['web'][3];
            $urlFilename = preg_replace( $pattern[1], null, $this->fileURL );
            $filename = preg_replace( $pattern[2], null, $urlFilename );
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

/**
 * Used to extract the archive
 * @class Used to extract the archive
 */
class unzip extends PhpWget {
    /**
     * @var string $archiveName
     */
    private $archiveName;

    public function __construct($archiveName) {
        $this->archiveName = $archiveName;
    }

    /**
     * Extract the archive
     */
    public function unZip() {
        if ( $this->pharLoaded === false ) {
            $this->shellOutput( $this->errorMassages[7] );
        } else {
            $pharData = new \PharData( $this->archiveName );
            $pharData->extractTo( '.' );
        }
    }
}

$PhpWget = new PhpWget();
$downloadFile = new \PhpWget\downloadFile();
$downloadFile->download();
