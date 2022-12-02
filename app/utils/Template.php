<?php
namespace app\utils;
use Handlebars\Handlebars;

class Template {
    public $rootPath;
    public $fileList;
    public $data;

    function __construct($data) {
        $this->rootPath = 'templates/'.$data['template'];
        $this->data = $data;
    }

    function getFile($relativePath){
        return new TemplateFile($this->rootPath.'/'.$relativePath, $this->data);
    }

    function export($exportPath){
        // iterate through template files recursively, render, and write to exports folder
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->rootPath));

        foreach ($iterator as $file){
            if (!$file->isDir()){
                //make the containing directories if they dont exist
                if(!is_dir("exports/{$exportPath}/{$iterator->getSubPath()}")){
                    mkdir("exports/{$exportPath}/{$iterator->getSubPath()}", 0777, true);// make recursive directory
                }

                //file subpath
                $filePath = $iterator->getSubPathName();

                //render template
                $content = $this->getFile($filePath)->render();

                // write to file
                file_put_contents("exports/{$exportPath}/{$filePath}", $content);
            }
        }

    }
    
}
class TemplateFile {
    public $path;
    public $content;

    function __construct($path, $data) {
        $this->path = $path;
        $this->data = $data;
        $this->content = file_get_contents($path);
    }

    function getContent(){
        return $this->content;
    }

    function getExtension($path) {
        // will get text mimetypes wrong
        $fileNameParts = explode('.', $path);
        return end($fileNameParts);
        
    }
    
    function getMimeType() {
        $extension = $this->getExtension($this->path);
        // will get text mimetypes wrong
        if ($extension == 'js' || $extension == 'css'){
            return "text/{$extension}";
        }
        // fallback for everything else
        return mime_content_type($this->path);
    }

    function isText(){
        return explode('/', $this->getMimeType())[0] == 'text';
    }

    function render() {
        if($this->isText()) {     
            $handlebars = new Handlebars();
            return $handlebars->render($this->getContent(), $this->data);
        }
        else {
            return $this->getContent();
        }
    }
}