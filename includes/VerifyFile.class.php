<?php
/**
 * Verify the integrity of file
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

class VerifyFile extends PhpWget {
    const MD5 = 'md5';
    const SHA1 = 'sha1';
    const SHA256 = 'sha256';

    /**
     * @var string File path waiting to be checked
     */
    private $filePath;

    /**
     * @var int
     */
    private $verifyType;

    /**
     * @var string The hash value of the file
     */
    private $fileHashValue;

    /**
     * @var array
     */
    private $messages = [
        'pass' => '[Check File Integrity] File integrity can be guaranteed.',
        'no-pass' => '[Check File Integrity] File may be damaged.'
    ];

    public function __construct($filePath, $verifyType) {
        $this->filePath = $filePath;
        $this->verifyType = $verifyType;

        $this->hash();
    }

    /**
     * Calculate the hash value of the file
     */
    private function hash() {
        $this->fileHashValue = hash_file( $this->verifyType, $this->filePath );
    }

    /**
     * Do verify
     * @param string $provideValue
     */
    public function verify($provideValue) {
        $this->handleProvideValue( $provideValue );
        $provideValue = strtolower( $provideValue );
        $pattern = "/^{$provideValue}/";
        $match = preg_match( $pattern, $this->fileHashValue );
        if ( $match === 1 ) {
            $this->shellOutput( $this->messages['pass'], 'green' );
        } else {
            $this->shellOutput( $this->messages['no-pass'], 'yellow' );
        }
    }

    private function handleProvideValue($provideValue) {
        $length = strlen( $provideValue );
        if ( $length < 5 ) {
            $this->shellOutput( $this->errorMessages[9] );
            die ( 1 );
        }
    }
}