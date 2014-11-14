<?php

namespace Application\Controller;

use Core\Controller;
use Service\File as FileService;
use Model\File as FileModel;

class File extends Controller
{

	public function indexAction()
	{
		$fileId = $this->getRequest()->getGet('file');
		$file = FileService::getById($fileId);

		$this->file($file->getType(), $file->getContent());
	}

	public function addAction()
	{
		$image = file_get_contents('php://input');

		$mime = getimagesizefromstring($image);

		$file = new FileModel();
		$file->setUserId($this->_currentUser->getId());
		$file->setType($mime['mime']);
		$file->setContent($image);
		$file->setCreated(time());

		$fileId = FileService::store($file);

		$this->json(array(
			'file_id' => $fileId
		));
	}

}