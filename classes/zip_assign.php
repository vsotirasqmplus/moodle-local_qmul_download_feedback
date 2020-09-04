<?php

/*
 * To send zip file of feedback files for local/qmul_download_feedback
 * we need access to a protected assign class method, so we extend it
 * @author Vasileios Sotiras <v.sotiras@qmul.ac.uk> 4th Sep 2020
 *
 */

namespace local_qmul_download_feedback;

# require_once("{$CFG->dirroot}/mod/assign/locallib.php");

/*
 * We need to access a protected method inside the assign class to create zip files.
 * A proposal is to make a child class just for this minor issue
*/
# require_once '../../../config.php';
/** @var object $CFG */
/** @noinspection PhpIncludeInspection */
require_once("{$CFG->dirroot}/mod/assign/locallib.php");

use assign;
use zip_packer;

class zip_assign extends assign
{
	/**
	 * Generate zip file from array of given files.
	 *
	 * @param array $files_for_zipping - array of files to pass into archive_to_pathname.
	 *                                 This array is indexed by the final file name and each
	 *                                 element in the array is an instance of a stored_file object.
	 *
	 * @return false|string filename of a temporary fie
	 *         not have a .zip extension - it is a temp file.
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection MissingReturnTypeInspection
	 */
	final public function pack_files($files_for_zipping)
	{
		global $CFG;
		// Create path for new zip file.
		$temp_zip = tempnam($CFG->tempdir . '/', 'assignment_');
		// Zip files.
		$zipper = new zip_packer();
		if($zipper->archive_to_pathname($files_for_zipping, $temp_zip)) {
			return $temp_zip;
		}
		return FALSE;
	}
}