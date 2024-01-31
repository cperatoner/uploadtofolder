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

/**
 * Manage files in folder module instance
 *
 * @package   mod_folder
 * @copyright 2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once("$CFG->dirroot/mod/folder/locallib.php");
require_once("$CFG->dirroot/mod/folder/edit_form.php");
require_once("$CFG->dirroot/repository/lib.php");
$redirect_url = $CFG->wwwroot.'/course/view.php?id='.$_GET['course_id'];
// $dir = $CFG->dirroot.'\\filetoupload\\';
$dir = $CFG->fileUploadDir;
$files = scandir($dir,1);
function filter_files($var)
{
	if($var != ''){
		$fileInfo = explode('-', $var ?? '');
		if(!empty($fileInfo) && trim($fileInfo[0]) == trim($_GET['course_id'])){
			return true;
		}
	}
}
$files = array_values(array_filter($files,"filter_files"));

if(!empty($files))
{
	foreach ($files as $file) {
		$fileex = explode('^', $file);
		$fileInfo = explode('-', $fileex[0]);
		if(count($fileInfo) == 4){
			$filepath = $fileInfo[2].'-'.$fileInfo[3];
			$fileTitle = $fileex[1];
		}
		else if(count($fileInfo) == 3){
			$filepath = $fileInfo[2];
			$fileTitle = $fileex[1];
		}
		else{
			$filepath = '/';
			$fileTitle = $fileex[1];	
		} 
		$ext = pathinfo($file, PATHINFO_EXTENSION);
		if(!empty($fileInfo) && $fileInfo[0] != '.' && $fileInfo[0] != '..'){
			$module_id = $fileInfo[1];
			$file_name_with_full_path = $dir.$file;
			$id = $module_id;
			$sql = "SELECT * FROM mdl_course_modules WHERE id=$id";
			$folderInfo = $DB->get_records_sql($sql);
			if(!empty($folderInfo)){
				// echo "han hai";
				$cm = get_coursemodule_from_id('folder', $id, 0, true, MUST_EXIST);
				 // print_r($cm);
				$context = context_module::instance($cm->id, MUST_EXIST);
				$folder = $DB->get_record('folder', array('id'=>$cm->instance), '*', MUST_EXIST);
				$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

				require_login($course, false, $cm);
				require_capability('mod/folder:managefiles', $context);

				$PAGE->set_url('/mod/folder/edit.php', array('id' => $cm->id));
				$PAGE->set_title($course->shortname.': '.$folder->name);
				$PAGE->set_heading($course->fullname);
				$PAGE->set_activity_record($folder);
				$data = new stdClass();
				$data->id = $cm->id;
				$maxbytes = get_user_max_upload_file_size($context, $CFG->maxbytes);
				$options = array('subdirs' => 1, 'maxbytes' => $maxbytes, 'maxfiles' => -1, 'accepted_types' => '*');
				file_prepare_standard_filemanager($data, 'files', $options, $context, 'mod_folder', 'content', 0);

				$mform = new mod_folder_edit_form(null, array('data'=>$data, 'options'=>$options));

				$formdata = new stdClass();
				$formdata->id = $id;
				$formdata->files_filemanager = $data->files_filemanager;
				$formdata->submitbutton = 'Save changes';

				$fmoptions = new stdClass();
				$fmoptions->maxbytes       = $options['maxbytes'];
				$fmoptions->maxfiles       = $options['maxfiles'];
				$fmoptions->client_id      = uniqid();
				$fmoptions->itemid         = $data->files_filemanager;
				$fmoptions->subdirs        = $options['subdirs'];
				$fmoptions->accepted_types = $options['accepted_types'];
				$fm = new form_filemanager($fmoptions);


				$client_id = $fm->options->client_id;
				$currentMonth = date('n');

				/*switch ($currentMonth) {
				       case 1:
				       case 2: 
				       case 3:
				          $filepath = date('Y').'-01';
				          break;
				       case 4:
				       case 5:
				       case 6:
				          $filepath = date('Y').'-02';
				          break;
				       case 7:
				       case 8:
				       case 9:
				          $filepath = date('Y').'-03';
				          break;
				       case 10:
				       case 11:
				       case 12:
				          $filepath = date('Y').'-04';
				          break;
				       deafault:
				          $filepath = date('Y').'-01';
				          break;
				}*/
				$filepath2 = '/'.$filepath.'/';
				$sql = "SELECT * FROM mdl_files as mf WHERE contextid=$context->id AND filepath='$filepath2'";
				$folderInfo = $DB->get_records_sql($sql);
				$userid = $USER->id;
				// print_r($folderInfo);
				// die;


				if(empty($folderInfo)){
					$postData = array(
					'client_id' => $fm->options->client_id,
					'filepath' => '/',
					'itemid' => $data->files_filemanager,
					'newdirname' => $filepath,
					'action' => 'mkdir'
					);
					 
					$ch = curl_init();

					curl_setopt($ch, CURLOPT_URL, $CFG->wwwroot."/repository/draftfiles_ajax_custom.php?action=mkdir&itemid=$data->files_filemanager&newdirname=$filepath&filepath=/&client_id=$client_id&userid=$userid");
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
					$output = curl_exec($ch);
					if($errno = curl_errno($ch)) {
					    $error_message = curl_strerror($errno);
					    echo "cURL error ({$errno}):\n {$error_message}";
					}
					curl_close($ch);

					$sql = "SELECT * FROM mdl_files as mf WHERE itemid=$data->files_filemanager AND filepath='$filepath2'";
					$folderInfo = $DB->get_records_sql($sql);

					if($folderInfo){
						foreach ($folderInfo as $key => $folderin) {
								$folderin->filearea = 'content';
								$folderin->contextid = $context->id;
								$folderin->component = 'mod_folder';
								$folderin->itemid = 0;
								$folderin->filearea = 'content';
								$DB->update_record('files', $folderin);
						}
					}
				}

				if( strpos( $ext, 'xls' ) !== false) {
				    $filepath2 = '/';
				}

				$author = 'Carlo+Peratoner';
				$fileTitle = urlencode($fileTitle);
				$target_url = $CFG->wwwroot."/repository/repository_ajax_custom.php?action=upload&overwrite=true&title=$fileTitle&repo_id=5&author=$author&userid=$userid";
				// $file_name_with_full_path = $CFG->dirroot.'/filetoupload/test-lms-12-md12.txt';
				
				if (function_exists('curl_file_create')) { // php 5.5+
				  $cFile = curl_file_create($file_name_with_full_path);
				} else { // 
				  $cFile = '@' . realpath($file_name_with_full_path);
				}
				// print_r($cFile);

				$post = array('repo_upload_file'=> $cFile, 'license' => 'allrightsreserved', 'p' => '', 'page' => '', 'env' => 'filemanager', 'client_id' => $client_id, 'itemid' => $data->files_filemanager, 'maxbytes' => -1, 'areamaxbytes' => -1, 'ctx_id' => $context->id, 'savepath' =>$filepath2);
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL,$target_url);
				curl_setopt($ch, CURLOPT_POST,1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
				$result=curl_exec ($ch);
				curl_close ($ch);
				

				$formdata = file_postupdate_standard_filemanager($formdata, 'files', $options, $context, 'mod_folder', 'content', 0);
				$folder->timemodified = time();
				$folder->revision = $folder->revision + 1;

				$DB->update_record('folder', $folder);


				$params = array(
				    'context' => $context,
				    'objectid' => $folder->id
				);
				$event = \mod_folder\event\folder_updated::create($params);
				$event->add_record_snapshot('folder', $folder);
				$event->trigger();
				//unlink($dir.$file);
				
			}
		}
	}
	
	redirect($redirect_url, 'All files has been uploaded successfully..', null, \core\output\notification::NOTIFY_SUCCESS);

	// redirect($CFG->httpswwwroot);
}
else{
	redirect($redirect_url, 'No files to upload for this course.', null, \core\output\notification::NOTIFY_ERROR);
}

