<?php
error_reporting(E_ERROR);
require_once 'oss/ALIOSS.php';
class Syn
{
    public $oss;
    public $dir;
    public $local;
    public $master;
    public $slaves;
    public $log;
    public $logfile;
    function __construct(){
        require_once 'config.php';
        $this->config       = $config;
        $this->master       = $config['master'];
        $this->slaves       = $config['slaves'];
        $this->local        = $config['local'];
        $this->dir          = $this->local['backdir'];
        $this->oss          = new ALIOSS(
            $this->master['accessKey'],
            $this->master['secureKey'],
            $this->master['host']
        );
        $this->oss->set_debug_mode(false);
        $this->log          = 'all';
        if(!is_dir($this->dir))
            mkdir($this->dir,0777,true);
        if($this->log == 'all' || $this->log == 'file'){
           $log = dirname(__FILE__).DIRECTORY_SEPARATOR.'log.txt';
           if(is_file($log)){
                unlink($log); 
           }
           $this->logfile = fopen($log,'w');
        }
    }
    function download($prefix,$marker){
        $bucket = $this->master['bucket'];
        $options = array(
            'delimiter'         => '/',
            'prefix'            => $prefix,
            'max-keys'          => 100,
            'marker'            => $marker,
        );
        $response = $this->oss->list_object($bucket,$options);
        if($response->isOK()){
            $xml = simplexml_load_string($response->body);
            if(isset($xml->Contents)){
                $contents = $xml->Contents;
                foreach($contents as $file){
                    $key = (string)$file->Key;
                    $fileName = $this->dir.DIRECTORY_SEPARATOR.$key;
                    $extension = pathinfo($fileName,PATHINFO_EXTENSION);
                    if($extension){
                        $options = array( 
                            ALIOSS::OSS_FILE_DOWNLOAD           => $fileName,
                        );
                        if(!is_file($fileName)){
                            $response = $this->oss->get_object($bucket,$key,$options);
                            if($response->isOK()){
                                $this->log('dowload '.$fileName.' success'); 
                            }
                        }
                    }
                }
            }
            if(isset($xml->NextMarker)){
                $nextMarker = (string)$xml->NextMarker;
                $this->download($prefix,$nextMarker);
            }
            if(isset($xml->CommonPrefixes)){
                $commonPrefixes = $xml->CommonPrefixes;
                foreach($commonPrefixes as $prefix){
                    $prefixName = (string)$prefix->Prefix;
                    $fileName = $this->dir.DIRECTORY_SEPARATOR.$prefixName;
                    $fileName = trim($fileName,'/');
                    $fileName = str_replace('/',DIRECTORY_SEPARATOR,$fileName);
                    if(!is_dir($fileName)){
                        if(mkdir($fileName,0777,true)){
                            $this->log('create folder '.$fileName.' success'); 
                        }
                    }
                    $this->download($prefixName,'');
                }
            }
        }
    }
    function upload(){
        foreach($this->slaves as $key=>$slave){
            $this->log("\n");
            $this->log('slave '.$key.' option start...'); 
            $oss                = new ALIOSS(
                $slave['accessKey'],
                $slave['secureKey'],
                $slave['host']
            );
            $oss->set_debug_mode(false);
            $this->log('slave '.$key.' delete file start...'); 
            $this->deleteObject($oss,$slave['bucket'],'','');
            $this->log('slave '.$key.' delete file end'); 
            $this->log('slave '.$key.' upload start...'); 
            $this->listDir($this->dir,$oss,$slave['bucket']);
            $this->log('slave '.$key.' upload end'); 
            $this->log('slave '.$key.' option end'); 
        }
    }
    function listDir($filedir,$oss,$bucket){
        $objPre = substr($filedir,strlen($this->dir),strlen($filedir));
        $dir = dir($filedir);
        while (($file = $dir->read()) !== false){
            if($file != "." && $file != ".."){
                $object             = $objPre;
                $object             = $object.DIRECTORY_SEPARATOR;
                $object             = $object.$file;
                $object             = trim($object,DIRECTORY_SEPARATOR);
                $object             = str_replace("\\","/",$object);
                if(is_dir($filedir.DIRECTORY_SEPARATOR.$file)) {
                    $response       = $oss->create_object_dir($bucket,$object);
                    if($response->isOK()){
                        $this->log('create oss folder '.$object.' success'); 
                        $this->listDir($filedir.DIRECTORY_SEPARATOR.$file,$oss,$bucket);
                    }
                }
                else {
                    $file_path      = $filedir.DIRECTORY_SEPARATOR.$file;
                    $response       = $oss->upload_file_by_file($bucket,$object,$file_path);
                    if($response->isOK()){
                        $this->log('upload file '.$file_path.' success'); 
                    }
                }
            }
        }
        $dir->close();
    }
    function deleteObject($oss,$bucket,$prefix,$marker){
        $options = array(
            'delimiter'         => '/',
            'prefix'            => $prefix,
            'max-keys'          => 100,
            'marker'            => $marker,
        );
        $response = $this->oss->list_object($bucket,$options);
        if($response->isOK()){
            $xml = simplexml_load_string($response->body);
            if(isset($xml->Contents)){
                $contents = $xml->Contents;
                foreach($contents as $file){
                    $key = (string)$file->Key;
                    $response = $this->oss->delete_object($bucket,$key);
                    if($response->isOK()){
                        $this->log('delete oss file '.$key.' success'); 
                    }
                }
            }
            if(isset($xml->NextMarker)){
                $nextMarker = (string)$xml->NextMarker;
                $this->deleteObject($oss,$bucket,$prefix,$nextMarker);
            }
            if(isset($xml->CommonPrefixes)){
                $commonPrefixes = $xml->CommonPrefixes;
                foreach($commonPrefixes as $prefix){
                    $prefixName = (string)$prefix->Prefix;
                    $this->deleteObject($oss,$bucket,$prefixName,'');
                }
            }
        }
    }
    function log($mess){
        if($mess !== "\n"){
            $m = date('Y-m-d H:i:s',time()).'  '.$mess."\n"; 
        }
        else{
            $m = $mess;
        }
        if($this->log =='all' || $this->log == 'print'){
            echo $m; 
        }
        if($this->log =='all' || $this->log == 'file'){
            if ($this->logfile) {
                fwrite($this->logfile,$m);
            }
        }
    }
    function end(){
        $this->log('Complete');
        if ($this->logfile) {
            fclose($this->logfile);
        }
    }
}

$op = new Syn();
$op->log('Start Download');
$op->download('','');
$op->log('End Download');
$op->log('Start Upload');
$op->upload();
$op->log('End Upload');
$op->end();
