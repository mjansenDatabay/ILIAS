<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

chdir('../../../../../');

require_once 'Services/Init/classes/class.ilInitialisation.php';
ilInitialisation::initILIAS();

require_once 'Services/FileUpload/classes/class.ilFileUploadUtil.php';
require_once 'Services/Utilities/classes/class.ilUtil.php';
require_once 'Services/UIComponent/ProgressBar/classes/class.ilProgressBar.php';

/**
 * @var $ilIliasIniFile ilIniFile
 * @var $lng ilLanguage
 * @var $ilUser ilObjUser
 * @var $https ilHttps
 */
global $ilIliasIniFile, $lng, $ilUser, $https;

$lng->loadLanguageModule('form');

$htdocs = $ilIliasIniFile->readVariable('server', 'absolute_path') . '/';
$weburl = $ilIliasIniFile->readVariable('server', 'absolute_path') . '/';
if(defined('ILIAS_HTTP_PATH'))
{
	$weburl = substr(ILIAS_HTTP_PATH, 0, strrpos(ILIAS_HTTP_PATH, '/Services')) . '/';
}

$installpath = $htdocs;

// directory where tinymce files are located
$iliasMobPath      = 'data/' . CLIENT_ID . '/mobs/';
$iliasAbsolutePath = $htdocs;
$iliasHttpPath     = $weburl;

// base url for images
$tinyMCE_base_url = $weburl;
$tinyMCE_DOC_url  = $installpath;

// allowed extentions for uploaded image files
$tinyMCE_valid_imgs = array('gif', 'jpg', 'jpeg', 'png');

$response = new stdClass();
$response->success = false;
$response->form = new stdClass();
$response->form->errors = new stdClass();
$response->data = new stdClass();

if(isset($_FILES['img_file']) && is_array($_FILES['img_file']))
{
	// remove trailing '/'
	while(substr($_FILES['img_file']['name'], -1) == '/')
	{
		$_FILES['img_file']['name'] = substr($_FILES['img_file']['name'], 0, -1);
	}

	$error = $_FILES['img_file']['error'];
	switch ($error)
	{
		case UPLOAD_ERR_INI_SIZE:
			$response->form->errors->img_file[] = array('name' => 'img_file', 'message' => $lng->txt('form_msg_file_size_exceeds'));
			break;

		case UPLOAD_ERR_FORM_SIZE:
			$response->form->errors->img_file[] = array('name' => 'img_file', 'message' => $lng->txt("form_msg_file_size_exceeds"));
			break;

		case UPLOAD_ERR_PARTIAL:
			$response->form->errors->img_file[] = array('name' => 'img_file', 'message' => $lng->txt("form_msg_file_partially_uploaded"));
			break;

		case UPLOAD_ERR_NO_FILE:
			$response->form->errors->img_file[] = array('name' => 'img_file', 'message' => $lng->txt("form_msg_file_no_upload"));
			break;

		case UPLOAD_ERR_NO_TMP_DIR:
			$response->form->errors->img_file[] = array('name' => 'img_file', 'message' => $lng->txt("form_msg_file_missing_tmp_dir"));
			break;

		case UPLOAD_ERR_CANT_WRITE:
			$response->form->errors->img_file[] = array('name' => 'img_file', 'message' => $lng->txt("form_msg_file_cannot_write_to_disk"));
			break;

		case UPLOAD_ERR_EXTENSION:
			$response->form->errors->img_file[] = array('name' => 'img_file', 'message' => $lng->txt("form_msg_file_upload_stopped_ext"));
			break;
	}
	
	// check suffixes
	if(!$response->form->errors->img_file)
	{
		$finfo = pathinfo($_FILES['img_file']['name']);
		require_once 'Services/Utilities/classes/class.ilMimeTypeUtil.php';
		$mime_type = ilMimeTypeUtil::getMimeType($_FILES['img_file']['tmp_name'], $_FILES['img_file']['name'], $_FILES['img_file']['type']);
		if(!in_array(strtolower($finfo['extension']), $tinyMCE_valid_imgs) || !in_array($mime_type, array(
			'image/gif',
			'image/jpeg',
			'image/png'
		)))
		{
			$response->form->errors->img_file[] = array('name' => 'img_file', 'message' => $lng->txt("form_msg_file_wrong_file_type"));
		}
	}

	if(!$response->form->errors->img_file)
	{
		if($_FILES['img_file']["tmp_name"] != "")
		{
			$vir = ilUtil::virusHandling($_FILES['img_file']["tmp_name"], $_FILES['img_file']["name"]);
			if($vir[0] == false)
			{
				$response->form->errors->img_file[] = array('name' => 'img_file', 'message' => $lng->txt("form_msg_file_virus_found")."<br />".$vir[1]);
			}
		}
	}

	if(!$response->form->errors->img_file)
	{
		require_once 'webservice/soap/include/inc.soap_functions.php';
		$safefilename = preg_replace('/[^a-zA-z0-9_\.]/', '', $_FILES['img_file']['name']);
		$media_object = ilSoapFunctions::saveTempFileAsMediaObject(session_id() . '::' . CLIENT_ID, $safefilename, $_FILES['img_file']['tmp_name']);
		if(file_exists($iliasAbsolutePath . $iliasMobPath . 'mm_' . $media_object->getId() . '/' . $media_object->getTitle()))
		{
			// only save usage if the file was uploaded
			$media_object->_saveUsage($media_object->getId(), $_GET['obj_type'] . ':html', (int)$_GET['obj_id']);
			$uploadedFile      = $media_object->getId();
			$response->success = true;
		}
	}

	$response->files = $_FILES;
}
else
{
	$progressbar_id = md5(rand());
	$progressbar    = ilProgressBar::getInstance();
	$progressbar->setId($progressbar_id);
	$progressbar->setAnimated(true);
	$progressbar->setCurrent(0);

	$response->data->max_file_size_info = ilUtil::getFileSizeInfo();
	$response->data->max_file_size      = ilFileUploadUtil::getMaxFileSize();
	$response->data->progressbar        = $progressbar->render();
	$response->data->progressbar_id     = $progressbar_id;

	$response->success = true;
}

echo json_encode($response);
exit();
