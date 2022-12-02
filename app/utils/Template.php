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

    function getSchema(){
        // iterate through template files recursively, and append content
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->rootPath));
        $schema = [];

        foreach ($iterator as $file){
            $content='';
            if (!$file->isDir()){
                //file subpath
                $filePath = $iterator->getSubPathName();

                $file = $this->getFile($filePath);

                if ($file->isText()){
                    $content = $this->getFile($filePath)->getContent();
                    $fileSchema = new Schema($content);
                    //$schema[$filePath] = $fileSchema->getSchema();
                    $schema = array_merge_recursive($schema, $fileSchema->getSchema());
                }
            }
        }
        return $schema;
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

class Schema{
    private $content;
    public $schema = [];
    private $currentElement;
    private $elementPath = []; // array tracking the traversal to get to our current position in the schema

    function __construct($content) {
        $this->content = $content;
        $this->currentElement = &$this->schema;
        $this->parse();
    }

    function getSchema(){
        return $this->schema;
    }
    function addElement($name){
        //if it's set already, do nothing.
        if(!array_key_exists($name, $this->currentElement)){
            $this->currentElement[$name]= [];
        }
        $this->currentElement = &$this->currentElement[$name];
        $this->elementPath[] = $name;
    }

    function returnToParent(){
        $reference = &$this->schema; // gotta start from the root
        array_pop($this->elementPath); // get rid of child
        
        //run down the stack
        foreach($this->elementPath as $level){
            $reference = &$reference[$level];
        }

        $this->currentElement = &$reference;
 
    }


    function parse(){
        $regexp = '/{{([^}]*)}}/';
        $matches = [];
        preg_match_all($regexp, $this->content, $matches, PREG_PATTERN_ORDER);
        foreach($matches[1] as $expression){ //0 is match group
            $helper = null;
            $variable = $expression;
            if (preg_match('/([#\/]\w*)\s?([\w\.]*)/', $expression, $terms)) { // if it contains a helpers
                $helper = $terms[1];
                $variable = $terms[2];                
            }

            // dot notation
            $terms = explode('.', $variable);

            
            foreach($terms as $term){
                if (!empty($term)){
                    $this->addElement($term);
                }
            }

            // run back up the tree
            $parentReturns = count($terms);
            if ($helper == '#each'){
                $parentReturns = 0;
            }
            elseif ($helper == '/each'){
                $parentReturns = 1;
            }
            $gotos = 0;
            for($i = 0; $i < $parentReturns; ++$i){
                $this->returnToParent();
                $gotos +=1;

            }       
        }
    }

}