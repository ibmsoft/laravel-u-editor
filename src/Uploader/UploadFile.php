<?php namespace Stevenyangecho\UEditor\Uploader;

use Intervention\Image\Facades\Image;
use Stevenyangecho\UEditor\Uploader\Upload;

/**
 *
 *
 * Class UploadFile
 *
 * 文件/图像普通上传
 *
 * @package Stevenyangecho\UEditor\Uploader
 */
class UploadFile  extends Upload{
    use UploadQiniu;
    public function doUpload()
    {


        $file = $this->request->file($this->fileField);
        if (empty($file)) {
            $this->stateInfo = $this->getStateInfo("ERROR_FILE_NOT_FOUND");
            return false;
        }
        if (!$file->isValid()) {
            $this->stateInfo = $this->getStateInfo($file->getError());
            return false;

        }

        $this->file = $file;

        $this->oriName = $this->file->getClientOriginalName();

        $this->fileSize = $this->file->getSize();
        $this->fileType = $this->getFileExt();

        $this->fullName = $this->getFullName();


        $this->filePath = $this->getFilePath();

        $this->fileName = basename($this->filePath);


        //检查文件大小是否超出限制
        if (!$this->checkSize()) {
            $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");
            return false;
        }
        //检查是否不允许的文件格式
        if (!$this->checkType()) {
            $this->stateInfo = $this->getStateInfo("ERROR_TYPE_NOT_ALLOWED");
            return false;
        }

        if(config('UEditorUpload.core.mode')=='local'){
            try {
                if (isset($this->config['watermarks'])) {
                    $img = Image::make($file);
                    $img->text($this->config['watermarks'], 120, 100, function($font) {
                        $font->file(public_path('fonts/SF-UI-Text-Light.otf'));
                        $font->size(28);
                        $font->color('#e1e1e1');
                        $font->align('center');
                        $font->valign('bottom');
                    });
                    $img->save(public_path(dirname($this->filePath) .'/'. $this->fileName));

                }else{
                    $this->file->move(dirname($this->filePath), $this->fileName);

                }
//                \Log::info('fileName:'.asset(dirname($this->filePath) .'/'. $this->fileName));
                $this->stateInfo = $this->stateMap[0];

            } catch (FileException $exception) {
                $this->stateInfo = $this->getStateInfo("ERROR_WRITE_CONTENT");
                return false;
            }

        }else if(config('UEditorUpload.core.mode')=='qiniu'){

            $content=file_get_contents($this->file->getPathname());
            return $this->uploadQiniu($this->filePath,$content);

        }else{
            $this->stateInfo = $this->getStateInfo("ERROR_UNKNOWN_MODE");
            return false;
        }




        return true;

    }
}
