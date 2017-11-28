<?php
/**
 * This script is used to test whether phpwget functions as required.
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

class test {
    /**
     * PhpWget script file path
     *
     * @var string $testFilePath
     */
    private $testFilePath = '../phpwget.php';

    /**
     * @var array $errorMassages
     */
    private $errorMassages = [
        1 => "[Warning] This script must be run in cli mode.\n",
        2 => "[Warning] You did not load curl extension, PhpWget will not work.\n",
        3 => "[Warning] PhpWget script does not exist or the working path is wrong, please check the script exists and make sure to call this script in the subordinate directory of the script.\n",
        4 => "[Warning] 'System' function has been disabled, please enable it. This script needs this function.\n"
        ];

    public function __construct() {
        $this->checkPHPEnvironment();
        $this->checkFileExist();
    }

    /**
     * Check if the server meets the requirements
     */
    private function checkPHPEnvironment() {
        // Check if this script is running in cli mode
        if ( php_sapi_name() !== 'cli' ) {
            echo $this->errorMassages[1];
            die ( 1 );
        }
        if ( !extension_loaded( 'curl' ) ) {
            echo $this->errorMassages[2];
            die ( 1 );
        }
        if ( !function_exists( 'system' ) ) {
            echo $this->errorMassages[4];
            die ( 1 );
        }
    }

    /**
     * Check if PhpWget script exists
     */
    private function checkFileExist() {
        if ( !file_exists( $this->testFilePath ) ) {
            echo $this->errorMassages[3];
            die ( 1 );
        }
    }
}
$test = new \PhpWget\test();
