<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\Topic;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function execute() {
        return response()->json(Section::withCount('materials')->get());
    }

    public function apiCreate(Request $request) {
        $data = $request->all();
        Section::create([
            'title'=>$data['title'],
            'name'=>$data['name']
        ]);
        return $this->execute();
    }

    public function apiUpdate(Request $request) {
        $data = $request->all();
        Section::where('id', $data['id'])->update([
            'title'=>$data['title'],
            'name'=>$data['name']
        ]);
        return $this->execute();
    }

    public function apidelete(Request $request) {
        $id = $request->all()['id'];
        Section::where('id', $id)->delete();
        Topic::where('section_id', $id)->update([
            'section_id'=>null
        ]);
        return $this->execute();
    }
}
