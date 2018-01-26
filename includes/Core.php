<?php
/**
 * Core file
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
        'UZ', // Command PhpWget extract the downloaded archive
        'md5::',
        'sha1::',
        'sha256::'
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
     * @var string $helpMassage
     */
    protected $helpMassage = <<<STR
Usage: php <this script name> -u=<file URL> [options]
   php <this script name> -h

  -f The file path you want to save, by default it will be saved to the current working directory
  -h This help

  --UZ Extract the archive after download
  --md5=<md5sum> Checks MD5 of the download file
  --sha1=<sha1sum> Checks SHA1 of the download file
  --sha256=<sha256sum> Checks SHA256 of the download file\n
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
        8 => '[Notice] Your version of PHP is lower than version 5.5.24 and is likely to go wrong when extracting BSD generated tar file.',
        9 => '[Warning] You provided a too short hash value, can not verify file integrity. Please provide at least 5 characters.'
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
        'green' => '32m',
        'yellow' => '33m'
    ];

    /**
     * Class file loader
     */
    static public function classLoader() {
        require_once APP_PATH . '/includes/DownloadFile.class.php';
        require_once APP_PATH . '/includes/UnZip.class.php';
        require_once APP_PATH . '/includes/VerifyFile.class.php';
    }

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

    final public function run() {
        $downloadFile = new DownloadFile();
        $downloadFile->download();
    }

    /**
     * Instantiate VerifyFile class
     * @param string $filePath
     */
    protected function verifyFile($filePath) {
        if ( isset( $this->options['md5'] ) ) {
            $md5 = new VerifyFile( $filePath, VerifyFile::MD5 );
            $md5->verify( $this->options['md5'] );
        }
        if ( isset( $this->options['sha1'] ) ) {
            $sha1 = new VerifyFile( $filePath, VerifyFile::SHA1 );
            $sha1->verify( $this->options['sha1'] );
        }
        if ( isset( $this->options['sha256'] ) ) {
            $sha1 = new VerifyFile( $filePath, VerifyFile::SHA256 );
            $sha1->verify( $this->options['sha256'] );
        }
    }
}