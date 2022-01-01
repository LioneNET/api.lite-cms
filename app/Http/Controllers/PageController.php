<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Page;

class PageController extends Controller
{
    public function execute(Request $resourse)
    {
        return view('admin.layout.pages');
    }

    public function save(Request $resourse) {

        $data = $resourse->all();
        
        if($data['mode'] == "new") {
            $Page = new Page();
            $Page->title = $data['title'];
            $Page->text = $data['text'];
            $Page->alias = $data['alias'];
            $Page->save();
        } else {

            $id = $data['id'];  

            Page::where('id', $id)->update([
                'title'=> $data['title'],
                'text'=> $data['text'],
                'alias'=> $data['alias']
            ]);
        }
        return response()->json(['code'=>"ok", 'message'=>'Данные добавлены!']);
    }

    public function delete(Request $resourse) {
        $id = $resourse->all();
        if(isset($id['id'])) {
            Page::where('id', $id['id'])->delete();
        }
    }

    public function edit(Request $resourse) {
        $id = $resourse->route('id');
        $editor = $resourse->route('editor');
        $editor = $editor == "codemirror" ? "tinymce" : "codemirror";
        $mode = "new";
        $text = "";
        $title = "";
        $alias = "";

        if($id) {
            $res = Page::where('id', $id)->first();
            if($res) {
                $res = $res->toArray();
                $mode = "edit";
                $text = $res['text'];
                $title = $res['title'];
                $alias = $res['alias'];
            } else {
                return redirect('/admin/pages');
            }
        }

        return view('admin.forms.new_Page_form',[
            'page_id'=>$id?$id:0,
            'mode'=>$mode,
            'text'=>$text,
            'title'=>$title,
            'editor'=>$editor,
            'alias'=>$alias
        ]);
    }

    public function get_pages(Request $resourse)
    {
        $data = $resourse->all();
        $show_items = 3;
        $page = isset($data['page']) ? $data['page'] : 0;

        $count = Page::count();
        if($count<=0) {
            return response()->json(['items'=>[], 'pages'=>0]);
            exit();
        }
        $pages = intval(ceil($count/$show_items));
        $page = $page > $pages ? $pages : ($page <= 0 ? 0 : $page);
        $skip = $page * $show_items;
        
        $collection = Page::orderBy('id', 'DESC')->skip($skip)
        ->take($show_items)->get();
        return response()->json(['items'=>$collection, 'pages'=>$pages]);
    }
}
