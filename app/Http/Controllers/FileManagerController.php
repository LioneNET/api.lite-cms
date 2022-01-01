<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileManagerController extends Controller
{
    public function execute(Request $resourse) {
        return $this->get_files($resourse);
    }

    public function apiUpload(Request $resource) {
        $path = $resource->post('path');
        $target_path = public_path()."/uploads".(!empty($path) ? "/".$path."/" : "/");
        $tmp_name = $resource->file->getPathName();
        $filename = mb_strtolower($resource->file->getClientOriginalName());
        $num = $resource->post('num');
        $num_chunks = $resource->post('num_chunks');
        $file_size = $resource->post('file_size');
        $target_file = $target_path.$filename;       
        
        if($file_size > 1024 * 1024 * 1024) {
            return response()->json(['code'=>'error', 'message'=>'Размер файла превышает 1гб'], 500);
        }

        //начало загрузки
        if($num==1) {
            
        }

        move_uploaded_file($tmp_name, $target_file.".part");

        if ( file_exists( $target_file.".part" ) ) {
            $file = fopen($target_file.".part", 'rb');
            $final = fopen($target_file.".load", 'ab');
    
            if(!$file || !$final) {
                echo json_encode(['code'=>'error', 'message'=>'Ошибка загрузки файла'], 500);
                unlink($target_file.'.part');
                unlink($target_file.".load");
                exit();
            }

            while ($buff = fread($file, 1024 * 4)) {
                fwrite($final, $buff);
            }

            fclose($file);
            fclose($final);
            unlink($target_file.".part");
            
            //конец загрузки
            if($num == $num_chunks) {
                rename($target_file.".load", $target_file);
                return response()->json(['code'=>'success', 'message'=>'Загрузка по пути '.$target_file.' завершена успешно']);
            }
        }
        return response()->json(['code'=>'success', 'message'=> $num.' Чать загружена']);
    }

    public function get_files(Request $resourse)
    {
        if(!file_exists(public_path()."/uploads")) {
            return response()->json(['code'=>'error', 'message'=>'Не указан корневой каталог'], 500); 
        }

        $data = $resourse->all();
        $path = isset($data['path']) ? $data['path'] : "";
        $path = public_path()."/uploads".(!empty($path) ? "/".$path : "");
        return response()->json($this->scan_dir($path));
    }

    public function apiDelete(Request $resourse) {
        $data = $resourse->all();
        if(isset($data['items'])){
            $path = $data['current_path'];
            $path = public_path()."/uploads".($path!=="/" ? "/".$path."/" : "/");
            $time = microtime(true);
            
            foreach($data['items'] as $val) {
                if($val['type']=="file"){
                    if(file_exists($path."/".$val['id'])) {
                        //dump($path.$val['id']);
                        unlink($path."/".$val['id']);
                    }
                } else {
                    //dump($path.$val['id']);
                    $this->delTree($path.$val['id']);
                }
            }
            return response()->json($this->scan_dir($path));
        }
    }

    //удалить каталог и все содержимое
    private function delTree($dir) {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            if (is_dir("$dir/$file")) {
                $this->delTree("$dir/$file");
            } else {
                unlink("$dir/$file");
            }
        }
        return rmdir($dir);
    }

    //сканировать файлы и папки
    private function scan_dir($dir = "") {

        if(!file_exists($dir)) {
            return null;
        }

        $ignored = array('.', '..', '.svn', '.htaccess');
        $files = array();
        foreach (scandir($dir) as $file) {
            if (in_array($file, $ignored))
                continue;
            $files[$file] = filemtime($dir . '/' . $file);
        }
        arsort($files);
        //$files = array_keys($files);
        $folders = [];
        $items = [];
        $total = 0;
        preg_match_all("%\/uploads\/.*%", $dir, $matches);
        $url = count($matches[0]) ? $matches[0][0] : '';
        foreach($files as $val=>$key) {
            if(is_dir($dir."/".$val)) {
                $folders[] = [
                    'id'=>preg_replace('/\s+/', '', $key.$val),
                    'item'=>['name'=>$val, 'item_type'=> 'folder'],
                    'size'=> $this->folderSize($dir."/".$val),
                    'url'=> '',
                    'type'=>'',
                    'file_time'=>$key
                ];
            } else {
                $items[] = [
                    'id'=>preg_replace('/\s+/', '', $key.$val),
                    'item'=>['name'=>$val, 'item_type'=> 'file'],
                    'size'=>filesize($dir."/".$val),
                    'url'=> $url."/".$val,
                    'type'=>pathinfo($val)['extension'],
                    'file_time'=>$key
                ];
            }
            
            $total++;
        }



        return ($files) ? [
            'items'=>array_merge_recursive($folders, $items),
            'total'=>$total
        ] : false;
    }

    public function rename_directory(Request $resourse)
    {
        $data = $resourse->all();
        $path = $data['path'];
        $old_name = $data['old_name'];
        $name = $data['name'];
        $path = public_path()."/uploads".($path!=="/" ? "/".$path."/" : "/");
        if(mb_strtoupper($old_name) === mb_strtoupper($name)) {
            return response()->json(['error' => true, 'message' => "Попытка переименовать отдин и тотже каталог"], 500);
        }

        if (empty($path)) {
            return response()->json(['error' => true, 'message' => "Путь не найден"], 500);
        }

        if (preg_match("%^[a-zA-Zа-яА-Я0-9_.\s-]{1,40}$%u", $name)) {
            $folders = $this->scan_dir($path);
            $rename = false;
            if(count($folders['items'])>0) {
                foreach($folders['items'] as $val) {
                    if(mb_strtoupper($val['item']['name']) === mb_strtoupper($name)) {
                        return response()->json(['error' => true, 'message' => "Такое имя уже есть"], 500);
                        break;
                    }
                }
                $rename = true;
            } else {
                $rename = true;
            }

            if($rename) {
                rename($path . "/" . $old_name, $path . "/" . $name);
                return response()->json($this->scan_dir($path));
            }
        } else {
            return response()->json(['error' => true, 'message' => "Неправильне имя каталога"], 500);
        }

    }

    public function create_directory(Request $resourse) {
        $data = $resourse->all();
        $path = $data['path'];
        $name = $data['name'];

        $path = public_path()."/uploads".(!empty($path) ? "/".$path."/" : "/");
        if (preg_match("%^[a-zA-Zа-яА-Я0-9_.\s-]{1,40}$%u", $name)) {
            if (file_exists($path . "/" . $name)) {
                return response()->json(['error' => true, 'message' => "Такое имя уже есть"], 500);
            }

            if (file_exists($path)) {
                mkdir($path . "/" . $name, 0777, true);
                return response()->json($this->scan_dir($path));
            } else
            return response()->json(['error' => true, 'message' => "Путь не найден"], 500);
        } else {
            return response()->json(['error' => true, 'message' => "Неправильне имя каталога"], 500);
        }
    }

    //размер директории
    private function folderSize($dir) {
        $size = 0;
        foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
            $size += is_file($each) ? filesize($each) : $this->folderSize($each);
        }
        return $size;
    }
}
