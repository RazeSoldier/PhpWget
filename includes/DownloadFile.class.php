<?php
/**
 * Used to download file
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
 * @class Download action class
 */
class DownloadFile extends PhpWget {
    /**
     * @var resource $curlResource
     */
    private $curlResource;

    /**
     * @var string Where will the file be downloaded?
     */
    private $filePath;

	/**
	 * @var resource Local file
	 */
	private $fileResource;

	/**
	 * @var bool Download result
	 */
	private $result;

	public function __construct() {
        $this->options = getopt( $this->optionIndex, $this->longopts );

        $this->displayHelpMassage();
        $this->checkOptions();
        $this->fileURL = $this->options['u'];
        if ( isset( $this->options['f'] ) and !$this->options['f'] === false) {
            $this->filePath = $this->options['f'];
            $this->checkFileDir();
        }
        $this->checkURL();
        $this->setFilePath();

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
        if ( is_dir( $this->filePath ) ) {
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
        preg_match( $pattern, $this->filePath, $matches);

        if ( !is_writable( $matches[0] ) ) {
            $this->shellOutput( "[Warning] PHP can't write to $matches[0], please make sure PHP can be written to the target directory" );
            die ( 1 );
        }
    }

    /**
     * According to the URL to determine the file directory.
     */
    private function setFilePath() {
        if ( isset( $this->filePath ) ) {
            if ( is_dir( $this->filePath ) ) {
                $this->filePath = $this->filePath . '/index.html';
            } else {
                $this->filePath = $this->filePath;
            }
        } else {
            $pattern[1] = $this->rePatterns['web'][1];
            $pattern[2] = $this->rePatterns['web'][3];
            $urlFilename = preg_replace( $pattern[1], null, $this->fileURL );
            $this->filePath = preg_replace( $pattern[2], null, $urlFilename );
            if ( $this->filePath === '' ) {
                $this->filePath = 'index.html';
            }
        }
        $this->replaceWinSysSpecialCharacters();
    }

    /**
     * If PhpWget run under windows system,
     * remove special characters that are sensitive to windows system
     * @Bug 2 https://github.com/RazeSoldier/PhpWget/issues/2
     */
    private function replaceWinSysSpecialCharacters() {
        if ( PHP_OS === 'WINNT' ||
                PHP_OS === 'WIN32' || PHP_OS === 'Windows'
            ) {
            $specialcharacters = '/[:?<>\|]/';
            $replacement = '.';
            $output = preg_replace( $specialcharacters, $replacement, $this->filePath );
            $this->filePath = $output;
        }
    }

    /**
     * Set some option for a cURL transfer
     */
    private function setCurlOpt() {
		# Support big file download
		curl_setopt( $this->curlResource, CURLOPT_FILE, $this->fileResource );
        curl_setopt( $this->curlResource, CURLOPT_AUTOREFERER, true );
        // Stop cURL from verifying the peer's certificate
        curl_setopt( $this->curlResource, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $this->curlResource, CURLOPT_FOLLOWLOCATION, true );
    }

    public function download() {
		$this->fileResource = fopen( $this->filePath, 'wb' );
        $this->setCurlOpt();
        $this->result = curl_exec( $this->curlResource );
        if ( $this->result === false ) {
            $this->shellOutput( $this->errorMassages[6], 'red' );
            die ( 1 );
        }

        $this->displayConcludingWords( $this->result );

        if ( isset($this->options['UZ'] ) ) {
            $unZip = new UnZip( $this->getFileName() );
            $unZip->unZip();
        }
    }

    /**
     * Display concluding words, if the file successfully written to the file system
     */
    private function displayConcludingWords($check) {
        if ( isset( $this->filePath ) ) {
            $targetDir = $this->filePath;
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
        if ( isset( $this->filePath ) ) {
            if ( is_dir( $this->filePath ) ) {
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
                $filename = preg_replace( $pattern, null, $this->filePath );
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
		if ( is_resource( $this->fileResource ) ) {
            fclose( $this->fileResource );
        }
		if ( !$this->result ) {
			unlink( $this->filePath );
		}
    }
}