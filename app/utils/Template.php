<?php
namespace app\utils;
use Handlebars\Handlebars;

class Template {
    public $path;
    public $content;

    function __construct($path) {
        $this->path = $path;
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

    function render($data) {
        $handlebars = new Handlebars();
        return $handlebars->render($this->getContent(), $data);
    }
}