<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/*
 * To send zip file of feedback files for local/qmul_download_feedback
 * we need access to a protected assign class method, so we extend it
 *
 * 4th Sep 2020
 * php version 7.2
 *
 * @category local
 * @package  local_qmul_download_feedback
 * @author   Vasileios Sotiras <v.sotiras@qmul.ac.uk>
 * @license  GPL v3
 * @link     http://qmplus.qmul.ac.uk
 */
/** @noinspection PhpIncludeInspection */

namespace local_qmul_download_feedback;

/*
 * We need to access a protected method inside the assign class to create zip files.
 * A proposal is to make a child class just for this minor issue
*/
defined('MOODLE_INTERNAL') || die();
require_once("{$CFG->dirroot}/mod/assign/locallib.php");

use assign;
use zip_packer;

/**
 * Class zip_assign
 *
 * @category Local
 * @package  Local_Qmul_Download_Feedback
 * @author   Vasileios Sotiras <v.sotiras@qmul.ac.uk>
 * @license  GPL v3
 * @link     http://qmplus.qmul.ac.uk
 */
class zip_assign extends assign
{
    /**
     * Generate zip file from array of given files.
     *
     * @param array $files             - array of files to pass
     *                                 into archive_to_pathname.
     *                                 This array is indexed by the
     *                                 final file name and each
     *                                 element in the array is an
     *                                 instance of a stored_file object.
     *
     * @return       false|string filename of a temporary fie
     *         not have a .zip extension - it is a temp file.
     * @noinspection PhpMissingParamTypeInspection
     * @noinspection MissingReturnTypeInspection
     */
    final public function pack_files($files) {
        global $CFG;
        // Create path for new zip file.
        $temp = tempnam($CFG->tempdir . '/', 'assignment_');
        // Zip files.
        $zipper = new zip_packer();
        if ($zipper->archive_to_pathname($files, $temp)) {
            return $temp;
        }
        return false;
    }
}
