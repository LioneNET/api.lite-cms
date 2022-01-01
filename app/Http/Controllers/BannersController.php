<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BannersController extends Controller
{
    public function execute()
    {
        return response()->json(Banner::OrderBy('position_id', 'ASC')
            ->with('position')
            ->get()->toArray());
    }

    //Cоздания меню
    public function apiCreate(Request $resourse)
    {
        $data = $resourse->all();
        $data['position_id'] = $data['position']['id'];
        Banner::create([
            'name'=>$data['name'],
            'position_id'=>$data['position_id'],
            'inner_text'=>$data['inner_text'],
            'class'=>$data['class'] ?? null
        ]);
    }

    //Oбновить 
    public function apiUpdate(Request $resourse)
    {
        $data = $resourse->all();
        $data['position_id'] = $data['position']['id'];
        $rootNode = Banner::where('id', $data['id'])->first();
        if($rootNode) {
            $rootNode['name'] = $data['name'];
            $rootNode['class'] = $data['class'];
            $rootNode['inner_text'] = $data['inner_text'];
            $rootNode['position_id'] = $data['position_id'];
            $rootNode->save();
        }
    }

    //Удалить 
    public function apiDelete(Request $resourse)
    {
        $data = $resourse->all();
        Banner::where('id', $data['id'])->delete();
    }

}
