IMPORTANT!
To add the upload button on the class navigation toolbar we need to add some code to two files:

1. in blocks/upload_files/block_upload_files.php
in the get_content() function. 
Search for the if(isset($this->config->text)){...} statement and add the below code:

if(isset($COURSE->id) && $COURSE->id != ''){
  $this->content->text = '<a href="'.$CFG->wwwroot.'/mod/folder/uploadFileFromServer.php?course_id='. $COURSE->id.'">File Upload</a>';
}

2. in lib/navigationlib.php
in load_course_settings($forceopen = false) function add below code:

global $COURSE;
if (strpos($_SERVER['PHP_SELF'], '/course/view.php') !== false) {
    if(isset($COURSE->id) && $COURSE->id != ''){
                
        $urlup = $CFG->wwwroot.'/mod/folder/uploadFileFromServer.php?course_id='. $COURSE->id;
        $coursenode->add('Upload Files', $urlup, self::TYPE_SETTING, null, null, new pix_icon('i/backup', ''));
    }
}

Example files are included in this repository, but they may differ from your version. 
