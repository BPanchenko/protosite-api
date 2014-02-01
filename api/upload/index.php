<?php

	$Response->meta->request = array(
		"_files" => $_FILES,
		"upload_dir" => $_SERVER['DOCUMENT_ROOT']."/temp/"
	);
	$Response->meta->error_type = "APIFailedUpload";
	$Response->meta->error_message = "Unknown upload error";
	
	$dbh = DB::dbh();
	
	$upload_dir = $_SERVER['DOCUMENT_ROOT']."/media/photos/temp";
	$image_types = array('image/jpeg','image/png','image/gif');
	$image_exts = array('jpg','png','gif');
	
	if(array_key_exists('photos', $_FILES) && $_FILES['photos']['error'] == 0 ) {
		$temp = $_FILES['photos'];
		
		if(!in_array($temp['type'], $image_types)) {
			$Response->meta->error_code = 310;
			$Response->meta->error_message->en = "Not registered file type";
			$Response->meta->error_message->ru = "Не зарегистрированный тип файла";
			echo_response($Response);
		}
		
		$file_name_exploded = explode(".",$temp['name']);
		$file_name = $file_name_exploded[0];
		$file_ext = $image_exts[array_search($temp['type'],$image_types)];
		if(move_uploaded_file($temp['tmp_name'], $upload_dir.'/'.$file_name.".".$file_ext)) {
			
			// сохранение фотографии
			$Photo = new Photo(array( 'filepath' => $upload_dir.'/'.$file_name.".".$file_ext ));
			$Response->data = $Photo->fetch()->resize(800)->pasteWatermark()->save()->toArray('src,file,filesize_str,width,height');
			$Response->meta->code = 200;
			
			echo_response($Response);
		}
		
	} else {
		$message = codeErrorToMessage($_FILES['photos']['error']);
		
		$Response->meta->error_code = 300 + $_FILES['photos']['error'];
		$Response->meta->error_message->en = $message['en'];
		$Response->meta->error_message->ru = $message['ru'];
	}
	echo_response($Response);


function codeErrorToMessage($code) {
	switch ($code) {
		case UPLOAD_ERR_INI_SIZE:
			$message['en'] = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
			$message['ru'] = "";
			break;
		case UPLOAD_ERR_FORM_SIZE:
			$message['en'] = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
			$message['ru'] = "";
			break;
		case UPLOAD_ERR_PARTIAL:
			$message['en'] = "The uploaded file was only partially uploaded";
			$message['ru'] = "";
			break;
		case UPLOAD_ERR_NO_FILE:
			$message['en'] = "No file was uploaded";
			$message['ru'] = "Файлы не были загруженны";
			break;
		case UPLOAD_ERR_NO_TMP_DIR:
			$message['en'] = "Missing a temporary folder";
			$message['ru'] = "Недоступна временная папка";
			break;
		case UPLOAD_ERR_CANT_WRITE:
			$message['en'] = "Failed to write file to disk";
			$message['ru'] = "Ошибка записи файла на диск";
			break;
		case UPLOAD_ERR_EXTENSION:
			$message['en'] = "File upload stopped by extension";
			$message['ru'] = "";
			break;
			
		default:
			$message['en'] = "Unknown upload error";
			$message['ru'] = "Неизвестная ошибка загрузки файла";
			break;
	}
	return $message;
}
?>