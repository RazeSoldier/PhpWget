<?php
/**
 * Used to extract the archive
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
 * @class Used to extract the archive
 */
class UnZip extends PhpWget {
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
            $extract = $pharData->extractTo( '.' );
            $this->displayConcludingWords( $extract );
        }
    }

    /**
     * Display concluding words, if the archive successfully extracted to the file system
     */
    private function displayConcludingWords($check) {
        if ( $check === true ) {
            if ( isset( $this->fileDir ) ) {
                $targetDir = $this->fileDir;
            } else {
                $targetDir = getcwd();
            }
            $this->shellOutput( "{$this->archiveName} successfully extracted to {$targetDir}", 'green');
        }
    }
}