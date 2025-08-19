<?php

namespace App\Modules;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpParser\ErrorHandler\Throwing;

trait FileUpload {
    public function upload(Request $request,$filefiled,$fn=null)
    {
        // загрузка файла        
        if ($request->isMethod('post') && $request->file($filefiled)) {
            $file = $request->file($filefiled);            
            $upload_folder = 'public/img';
            $file_Arr = [];
            if(is_array($file)) {
                foreach($file as $f1) {
                    $file_Arr[] = str_replace($upload_folder,'/storage/img',$f1->store($upload_folder));
                }
                return $file_Arr;
            }
            else return [str_replace($upload_folder,'/storage/img',$file->store($upload_folder))];
        }
        return null;
    }
    public function loadFromServer(string $url,string $fname) { 
        /*       
        $file_headers = @get_headers($url);
        if(!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') {
            throw new Exception("Ошибка загрузки ".$url);
            return null;
        }        
        */
        $upload_folder = 'public/img';
        try {
            $content = $this->myFileGetContents($url);
        }
        catch(Exception $e) {
            return null;
        }

        if(strpos($fname,'.')===false) {
            $url_arr = explode('.',$url);
            $ext = array_pop($url_arr);
            $fname .=".".$ext;
        } 
        Storage::put($upload_folder."/".$fname,$content);        
        return "/storage/img/".$fname;
    }
    public function myFileGetContents($url) {
        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );        
        $html = file_get_contents($url, false,stream_context_create($arrContextOptions));
        return $html;
    }
}