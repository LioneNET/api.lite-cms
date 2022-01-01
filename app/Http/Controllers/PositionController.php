<?php

namespace App\Http\Controllers;

use App\Models\PosItems;
use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    //изменение позиций
    public function positionChange(Request $request) {
        $data = $request->all();
        $this->ChangePosNode($data['a'], $data['b']);
        return $this->getPosItems();
    }

    //объекты связанные с позициями
    public function getPosItems() {
        $items = PosItems::with('positiable')
            ->with('position')->get()->toArray();
        $sort = [];
        $out = [];
        //групируем по позициям
        foreach($items as $val) {
            $val['key']=$val['id'];
            $val['type']=$val['positiable_type']::$title;
            $sort[$val['position_id']][$val['id']] = $val;
        }
        //отсортированный список
        foreach($sort as $val) {
            $out = array_merge($out, $this->sortPrevNext($val));
        }
        return response()->json($out);
    }

    //позиции
    public function getPositions() {
        return response()->json(Position::all());
    }

    /**
     * меняет позиции узлов $a и $b местами в пределах родительского узла
     * @param int $a
     * @param int $b
     * @return bool
     */
    private function ChangePosNode($a, $b) {
        $next = null;
        $sorted_items = [];
        $nodeAPrev = null;
        $nodeANext = null;
        $nodeBPrev = null;
        $nodeBNext = null;
        $nodeA = null;
        $nodeB = null;
        $parent = PosItems::where('id', $a)->first();

        //все ноды из одного узла
        $nodes = PosItems::where('position_id', $parent['position_id'])->orderBy('id', 'DESC')->get()->keyBy('id')->toArray();

        //если оба узла не из одного родителя, то ничего не делаем
        if (!isset($nodes[$a]) || !isset($nodes[$b])) {
            return false;
        }

        //запрет на изменение места с разными позициями
        if ($nodes[$a]['position_id'] !== $nodes[$b]['position_id']) {
            return response()->json(['error' => true, 'message' => 'Нелья перемещать в разные позиции'], 501);
        }

        //сортируем по позициям
        $temp = [];
        foreach ($nodes as $id => $node) {
            if ($node['position_id'] === $nodes[$a]['position_id']) {
                $temp[$id] = $node;

                //ищем начало
                if ($node['prev'] === null) {
                    $next = $node;
                    $sorted_items[] = $node;
                }
            }
        }
        $nodes = $temp;

        //сортируем
        foreach ($nodes as $i) {
            //сортируем
            foreach ($nodes as $j) {
                if ($next['next'] === $j['id']) {
                    $sorted_items[] = $j;
                    $next = $j;
                }
            }
        }

        //установить порядок $a и $b
        foreach ($sorted_items as $val) {
            if ($a === $val['id']) {
                $first = $a;
                break;
            }
            if ($b === $val['id']) {
                $first = $b;
                break;
            }
        }

        //заменить а и b если порядок нарушен
        if ($first === $b) {
            $b = $a;
            $a = $first;
        }

        $nodeA = PosItems::where('id', $a)->first();
        $nodeB = PosItems::where('id', $b)->first();

        if ($nodeA['prev']) {
            $nodeAPrev = PosItems::where('id', $nodeA['prev'])->first();
        }
        if ($nodeA['next']) {
            $nodeANext = PosItems::where('id', $nodeA['next'])->first();
        }
        if ($nodeB['prev']) {
            $nodeBPrev = PosItems::where('id', $nodeB['prev'])->first();
        }
        if ($nodeB['next']) {
            $nodeBNext = PosItems::where('id', $nodeB['next'])->first();
        }

        //поменять с ближним
        if ($nodeA['next'] === $nodeB['id']) {

            $nodeA['next'] = $nodeBNext['id'] ?? null;
            $nodeA['prev'] = $nodeB['id'];
            $nodeB['prev'] = $nodeAPrev['id'] ?? null;
            $nodeB['next'] = $nodeA['id'];
            if ($nodeBNext) {
                $nodeBNext['prev'] = $nodeA['id'];
                $nodeBNext->save();
            }
            if ($nodeAPrev) {
                $nodeAPrev['next'] = $nodeB['id'];
                $nodeAPrev->save();
            }
            $nodeA->save();
            $nodeB->save();
        }
        //поменять с дальним
        else if ($nodeA['next'] !== $nodeB['id']) {
            $nodeA['next'] = $nodeBNext['id'] ?? null;
            $nodeA['prev'] = $nodeBPrev['id'];
            $nodeB['prev'] = $nodeAPrev['id'] ?? null;
            $nodeB['next'] = $nodeANext['id'];
            $nodeANext['prev'] = $nodeB['id'];
            $nodeBPrev['next'] = $nodeA['id'];
            if ($nodeBNext) {
                $nodeBNext['prev'] = $nodeA['id'];
                $nodeBNext->save();
            }
            if ($nodeAPrev) {
                $nodeAPrev['next'] = $nodeB['id'];
                $nodeAPrev->save();
            }
            $nodeANext->save();
            $nodeBPrev->save();
            $nodeA->save();
            $nodeB->save();
        }

        return true;
    }

    //сортируем в порядке следования prev | next
    private function sortPrevNext($array) {
        if(count($array)) {
            $next_id = null;
            $sorted_items = [];
            $checked_items = $array;
    
            //ищем начало
            while($checked_items) {
                $item = array_shift($checked_items);
                if($item['prev'] === null) {
                    $next_id = $item['id'];
                    break;
                }
            }
            
            //сортируем
            for($i=0; $i<count($array); $i++) { 
                $sorted_items[$next_id] = $array[$next_id];
                $next_id = $array[$next_id]['next'];
            }
            return $sorted_items;  
        }
        return $array;
    }

}
