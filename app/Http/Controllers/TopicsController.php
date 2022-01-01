<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Topic;
use App\Models\Category;
use Intervention\Image\Facades\Image;

class TopicsController extends Controller
{

    public function execute(Request $request) {
        return $this->get_topics($request);
    }

    //Cоздания 
    public function apiCreateTopic(Request $resourse) {
        $data = $resourse->all();
        $topic = new Topic();
        $topic->title = $data['title'];
        $topic->short_text = Str::words(strip_tags($data['cleanText']), 30, ' ...');
        $topic->text = $data['text'];
        $topic->category_id = $data['category_id'];
        $topic->section_id = $data['section_id'];
        $topic->image = $data['image'];
        $topic->save();
        return $this->execute($resourse);
    }

    //Oбновить 
    public function apiUpdateTopic(Request $resourse) {
        $data = $resourse->all();
        $id = $data['id'];      
        Topic::where('id', $id)->update([
            'title'=> $data['title'],
            'short_text'=> Str::words(strip_tags(preg_replace('/\s+/', ' ', $data['cleanText'])), 30, ' ...'),
            'text'=> $data['text'],
            'category_id'=> $data['category_id'],
            'section_id'=> $data['section_id'],
            'image' => $data['image']
        ]);
        return $this->execute($resourse);
    }

    //Удалить 
    public function apiDeleteTopic(Request $resourse) {
        $id = $resourse->all();
        if(isset($id['id'])) {
            Topic::where('id', $id['id'])->delete();
        }
        return $this->execute($resourse);
    }

    private function get_topics(Request $resourse) {
        $data = $resourse->all();
        $show_items = $data['pageSize'] ?? 10;
        $category_ids = [];
        $page = $data['current'] ?? 1;
        $query = $data['query'] ?? "";
        $queryType = $data['queryType'] ?? "title";
        $sortedBy = $data['sortField'] ?? 'created_at';
        $sortedOrder = $data['sortOrder'] ?? 'DESC';

        if(!empty($query)) {
            if($queryType == 'title') {
                $count = Topic::where('title', 'LIKE', "%{$query}%")->count();
            } else if($queryType == 'date') {
                $count = Topic::where('created_at', 'LIKE', "%{$this->date_filter($query)}%")->count();
            } else if($queryType == 'category') {
                $res = Category::where('title', 'LIKE', "%{$query}%")->withCount('materials')->get();
                $count = 0;
                foreach($res as $val) {
                    $category_ids[] = $val['id'];
                    $count += $val['materials_count'];
                }
            }
        } else {
            $count = Topic::count();
        }
        $pages = intval(ceil($count/$show_items));
        $page = $page > $pages ? $pages : ($page <= 0 ? 1 : $page);
        $skip = ($page-1) * $show_items;
        $page = $page <= 0 ? 1 : $page;
        if(!empty($query)) {
            if($queryType == 'title') {
                $collection = Topic::where('title', 'LIKE', "%{$query}%")
                ->orderBy($sortedBy, $sortedOrder==='ascend' ? 'ASC' : 'DESC')->skip($skip)->take($show_items)
                ->with('category')
                ->with('section')
                ->get();
            } else if($queryType == 'date') {
                $collection = Topic::where('created_at', 'LIKE', "%{$this->date_filter($query)}%")
                ->with('category')
                ->with('section')
                ->orderBy($sortedBy, $sortedOrder==='ascend' ? 'ASC' : 'DESC')->skip($skip)->take($show_items)
                ->get();
            } else if($queryType == 'category') {
                $collection = Topic::whereIn('category_id', $category_ids)
                ->with('category')
                ->with('section')
                ->orderBy($sortedBy, $sortedOrder==='ascend' ? 'ASC' : 'DESC')->skip($skip)->take($show_items)
                ->get();
            }
        } else {
            $collection = Topic::with('category')
            ->with('section')
            ->orderBy($sortedBy, $sortedOrder==='ascend' ? 'ASC' : 'DESC')->skip($skip)
            ->take($show_items)->get();
        }
        $cats = new CategoryController;
        return response()->json([
            'current'=>$page,
            'pageSize'=>$show_items,
            'items'=>$collection, 
            'total'=>$count, 
            'crumbs'=>$cats->get_breadcrumb()
        ]);
    }

    //получить дерево категорий
    public function get_categories($toArray=false)
    {
        $data = Category::all()->toArray();
        $root_cat = [];
        $sub_cat = [];
        
        //рекурсивно ищем подкатегории
        function create_tree($id, $data) {
            $sub_arr = [];

            foreach($data as $val) {
                if($id==$val['id_parent']) {
                    $sub_arr[] = [
                        'id' => $val['id'],
                        'id_parent' => $val['id_parent'],
                        'prioritet' => $val['prioritet'],
                        'title' => $val['title'],
                        'items' => $val['items'],
                        'alias' => $val['alias'],
                        'image' => $val['image'],
                        'child'=> create_tree($val['id'], $data)
                    ];
                }
            }
            return $sub_arr;
        }

        //пробегаемся по массиву и берем корневые каталоги
        foreach($data as $val) {
            if(!$val['id_parent']) {
                $root_cat[] = [
                    'id' => $val['id'],
                    'id_parent' => $val['id_parent'],
                    'prioritet' => $val['prioritet'],
                    'title' => $val['title'],
                    'items' => $val['items'],
                    'alias' => $val['alias'],
                    'image' => $val['image'],
                    'child'=> []
                ];
            } else {
                $sub_cat[] = $val;
            }
        }

        //проходим по корневым категориям и ищем подкатегории
        if(count($sub_cat)>0) {
            foreach($root_cat as $key=>$val) {
                $root_cat[$key]['child'] = create_tree($val['id'], $sub_cat);
            }
        }
        
        //сортировать по приоритету 1 высший приоритет
        function sort_tree(&$data) {
            for($i=0; $i<count($data); $i++) {
                if(count($data[$i]['child'])>0) {
                    sort_tree($data[$i]['child']);
                }
                for($j=0; $j<count($data); $j++) {
                    if($data[$j]['prioritet']>$data[$i]['prioritet']) {
                        $temp = $data[$i];
                        $data[$i] = $data[$j];
                        $data[$j] = $temp;
                    }
                }
            }
        }
        sort_tree($root_cat);
        if($toArray) {
            return $root_cat;
        } else {
            echo json_encode(['items'=>$root_cat]);
            exit();
        }
    }

    private function get_youtube_url($url) {
        if ($url !== NULL) {
            if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $id)) {
                $values = "https://www.youtube.com/embed/" . $id[1];
            } else if (preg_match('/youtube\.com\/watch\?v=([^\&\?\/]+)/', $url, $id)) {
                $values = "https://www.youtube.com/embed/" . $id[1];
            } else if (preg_match('/youtube\.com\/embed\/([^\&\?\/]+)/', $url, $id)) {
                $values = "https://www.youtube.com/embed/" . $id[1];
            } else if (preg_match('/youtube\.com\/v\/([^\&\?\/]+)/', $url, $id)) {
                $values = "https://www.youtube.com/embed/" . $id[1];
            } else if (preg_match('/youtu\.be\/([^\&\?\/]+)/', $url, $id)) {
                $values = "https://www.youtube.com/embed/" . $id[1];
            } else if (preg_match('/youtube\.com\/verify_age\?next_url=\/watch%3Fv%3D([^\&\?\/]+)/', $url, $id)) {
                $values = "https://www.youtube.com/embed/" . $id[1];
            } else {
                $values = "";
            }
            return $values;
        }
    }

    //удаляем минеатюры по id
    private function unlink_image($id) {
        //удаляем старый thumbs если есть
        $edit = Topic::where('id', $id)->first();
        if($edit) {
            $res = $edit->toArray();
            $img = $res['image'];
            if($img != null) {
                if(file_exists(public_path()."/thumbs/low_".basename($img))) {
                    unlink(public_path()."/thumbs/low_".basename($img));
                }
                if(file_exists(public_path()."/thumbs/medium_".basename($img))) {
                    unlink(public_path()."/thumbs/medium_".basename($img));
                }
            }
        }
    }

    //фильтр по дате
    private function date_filter($date) {
        $date = preg_replace('%[(\s.\/\-\\,:)]+%', '/', $date);      
        if (preg_match("%^([0-9]{4}|[0-9]{2})[.\/-]([0]?[1-9]|[1][0-2])[.\/]([0]?[1-9]|[1|2][0-9]|[3][0|1])$%", $date)) {
            $date = explode("/", $date);
            return (iconv_strlen($date[0]) == 2 ? "20" . $date[0] : $date[0]) . "-" .
                    (iconv_strlen($date[1]) == 1 ? "0" . $date[1] : $date[1]) . "-" .
                    (iconv_strlen($date[2]) == 1 ? "0" . $date[2] : $date[2]);
        } elseif (preg_match("%^([0-9]{4}|[0-9]{2})[.\/-]([0]?[1-9]|[1][0-2])$%", $date)) {
            $date = explode("/", $date);
            return  (iconv_strlen($date[0]) == 2 ? "20" . $date[0] : $date[0]) . "-" .
                    (iconv_strlen($date[1]) == 1 ? "0" . $date[1] : $date[1]);
        } elseif (preg_match("%^([0-9]{4}|[0-9]{2})$%", $date)) {
            return (iconv_strlen($date) == 2 ? "20" . $date : $date);
        } else {
            return Str::random(30);
        }
    }

    private function resizeImage($path) {
        $name = basename($path);
        Image::make(public_path().$path)
        ->resize(400, 400, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })
        ->save(public_path().'/thumbs/low_'.$name);

        Image::make(public_path().$path)
        ->resize(1000, 900, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })
        ->save(public_path().'/thumbs/medium_'.$name);
    }
}
