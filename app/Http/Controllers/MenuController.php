<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Menu;

class MenuController extends Controller
{
    public function execute(Request $resourse) {
        //return view('admin.layout.menu');

        function loop($data) {
            $arr = [];
            foreach($data as $node) {
                $arr[] = [
                    'key'=>$node['data']['id'],
                    'id_parent'=>$node['data']['id_parent'],
                    'title'=>$node['data']['name'],
                    'url'=>$node['data']['url'],
                    'position'=>$node['data']['position'],
                    'children'=>loop($node['childs'])
                ];
            }
            return $arr;
        }


        return response()->json(loop($this->getMenuItems()));
    }

    //Cоздания меню
    public function apiCreateMenu(Request $resourse) {
        $data = $resourse->all();
        $data['position_id'] = $data['position_id'] ?? 1;
        
        $this->addMenuItem($data);
        return $this->execute($resourse);
    }

    //Добавление меню
    public function apiAddMenu(Request $resourse) {
        //return response()->json($resourse->all());
        $parent_id = $resourse->all()['id_parent'];
        $this->addMenuItem($resourse->all(), $parent_id);
        return $this->execute($resourse);
    }

    //Oбновить меню
    public function apiUpdateMenu(Request $resourse) {
        $data = $resourse->all();
        //позиция по умолчанию
        $position_id = 1;

        $menu = Menu::where('id', $data['id'])->first();

        //манипуляции с изменением позиции доступны только для root узлов
        if($menu['id_parent']===null) {
            $position_id = $data['position_id'];
        }

        $menu->name = $data['name'];
        $menu->url = $data['url'];
        $menu->position_id = $position_id;
        $menu->save();
        if(isset($data['moveNodeToID'])) {
            $this->MoveNode($data['id'], $data['moveNodeToID']);
        }
        return $this->execute($resourse);
    }

    //Удалить меню
    public function apiDeleteMenu(Request $resourse) {
        $data = $resourse->all();
        $this->deleteMenu($data['id']);
        return $this->execute($resourse);
    }

    //Изменить позицию меню
    public function apiChangePositionMenu(Request $request) {
        $data = $request->all();
        $success = $this->ChangePosNode($data['a'], $data['b']);
        if($success === true) {
            return $this->execute($request);
        } else {
            return $success;
        }
        
    }

     /**
     * Добавить узел в узел по id
     * 
     * @param Menu $data объект узла
     * @param int $id узел родителя
     * 
     */
    private function addMenuItem($data, $id=null) {
        if($id===null) {
            $newNode = new Menu();
            $newNode->name = $data['name'];
            $newNode->position_id=$data['position_id'];
            $newNode->save();
            return $newNode->id;
        }

        $rootNode = Menu::where('id', $id)->first();
        if(!$rootNode) {
            return response()->json(['error'=>'true', 'message'=>'No find root id', 422]);
        }
        $lastChildNode = Menu::where('id_parent', $rootNode['id'])->where('next', null)->first();
        $newNode = new Menu();
        $newNode['name'] = $data['name'];
        $newNode['id_parent'] = $rootNode['id'];
        $newNode['position_id'] = $data['id_position'] ?? 1;

        if($lastChildNode) {
            $newNode['prev']=$lastChildNode['id'];
            $newNode->save();
            $lastChildNode['next'] = $newNode['id'];
            $lastChildNode->save();

        } else {
            $newNode['prev'] = null;
            $newNode->save();
        }
       
        return $newNode['id'];
    }

    //удалить меню и все его потомки
    private function deleteMenu($id) {
        $childs = array_merge($this->getAllChildNodesByID($id, $this->getMenuItems($id)));
        $this->removeMenuFromList($id);
        Menu::whereIn('id', $childs)->delete();
        Menu::find($id)->delete();
    }

    /**
     * все id подузлов из массива $arr
     * @param int $id корневой узел
     * @param array $arr массив узлов
     * @return array
     */
    private function getAllChildNodesByID($id, $arr) {
        $ids = [];
        foreach($arr as $val) {
            if($id==$val['data']['id_parent']) {
                $ids[] = $val['data']['id'];
                if(count($val['childs'])){
                    $ids = array_merge($ids, $this->getAllChildNodesByID($val['data']['id'], $val['childs']));
                }
            }            
        }
        return $ids;
    }

    /**
     * Перемещает ноду из родительской во внешнюю
     * @param int $a перемещаемый узел
     * @param int $b узел назначения
     * @return bool
     */
    private function MoveNode($a, $b) {
        $menuA = Menu::where('id', $a)->first();
        $menuB = Menu::where('id', $b)->first();

        //запрет на перемещение в тот же родительский узел
        if($menuA['id_parent'] === $menuB['id']) {
            return false;
        }
        //запрет на перемещение корневого узла
        if($menuA['id_parent'] === null) {
            return false;
        }
        //запрет перемещение родительского узла в потомка
        $childNodes = $this->getAllChildNodesByID($a, $this->getMenuItems($a));
        if(array_search($b, $childNodes)) {
            return false;
        }

        $this->removeMenuFromList($menuA['id']); 

        //устанавливаем перемещаемый узел $a в конец узлов $b если есть
        $menuBEnd = Menu::where('id_parent', $menuB['id'])->where('next', null)->first();

        if($menuBEnd) {
            $menuBEnd['next'] = $menuA['id'];
            $menuA['prev'] = $menuBEnd['id'];
            $menuBEnd->save();
        }else {
            $menuA['prev'] = null;
        }
        $menuA['id_parent'] = $menuB['id'];
        $menuA['next'] = null; 
        $menuA->save();
        return true;
    }

    //пункты меню
    /**
     * возвращает дерево нод меню в виде data child
     * @param null|int $root_id id корневой ноды или null
     * @return array если null то дерево всех нод
     */
    private function getMenuItems($root_id = null) {
        //корневые узлы сгрупированые по позизиям
        $position_id_list = [];
        $root_menus = [];
        $sub_menus = [];
        $array = Menu::OrderBy('position_id', 'ASC')->with('position')->get()->toArray();

        //рекурсивно ищем узлы узлов
        $sub_items = function ($id, $sub_data) use (&$sub_items) {
            $array = [];

            foreach($sub_data as $items) {
                if($id == $items['id_parent']) {
                    $array[$items['id']] = [
                        'data'=> $items,
                        'childs' => $sub_items($items['id'], $sub_data)
                    ];
                }
            }

            return $this->sortPrevNext($array);;
        };

        //формируем корневые узлы
        foreach($array as $items) {
            if($items['id_parent'] === $root_id) {    
                $root_menus[$items['id']] = [
                    'data'=>$items,
                    'childs' => []
                ];
            } else {
                $sub_menus[$items['id']] = $items;
            }
        }

        //проходим по корневым узлам и ищем подузлы
        foreach($root_menus as $id=>$items) {
            $root_menus[$id]['childs'] = $sub_items($id, $sub_menus);
        }

        return $root_menus;
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
        $menuAPrev = null;
        $menuANext = null;
        $menuBPrev = null;
        $menuBNext = null;
        $menuA = null;
        $menuB = null;
        $parent = Menu::where('id', $a)->first();
        $first = $a;

        if(!$parent) {
            return false;
        }

        //все ноды из одного узла
        $nodes = Menu::where('id_parent', $parent['id_parent'])->orderBy('id', 'DESC')->get()->keyBy('id')->toArray();

        //если оба узла не из одного родителя, то ничего не делаем
        if(!isset($nodes[$a]) || !isset($nodes[$b])) {
            return false;
        }

        //запрет на перемещение корневых узлов
        if($nodes[$a]['id_parent'] == null || $nodes[$b]['id_parent'] == null) {
            return false;
        }

        //запрет на изменение места с разными позициями
        if($nodes[$a]['position_id'] !== $nodes[$b]['position_id']) {
            return response()->json(['error'=>true, 'message'=>'Нелья перемещать в разные позиции'], 422);
        }

        //сортируем по позициям
        $temp = [];
        foreach($nodes as $id=>$node) {
            if($node['position_id'] === $nodes[$a]['position_id']) {
                $temp[$id] = $node;

                //ищем начало
                if($node['prev'] === null) {
                    $next = $node;
                    $sorted_items[] = $node;
                }
            }
        }
        $nodes = $temp;

        //сортируем
        foreach($nodes as $i) {
            //сортируем
            foreach($nodes as $j) {
                if($next['next'] === $j['id']) {
                    $sorted_items[] = $j;
                    $next = $j;
                }
            }
        }

        //установить порядок $a и $b
        foreach($sorted_items as $val) {
            if($a === $val['id']) {
                $first = $a;
                break;
            }
            if($b === $val['id']) {
                $first = $b;
                break;
            }
        }

        //заменить а и b если порядок нарушен
        if($first === $b) {
            $b = $a;
            $a = $first;
        }
        
        $menuA = Menu::where('id', $a)->first();
        $menuB = Menu::where('id', $b)->first();

        if($menuA['prev']) {
            $menuAPrev = Menu::where('id', $menuA['prev'])->first();
        }
        if($menuA['next']) {
            $menuANext = Menu::where('id', $menuA['next'])->first();
        }
        if($menuB['prev']) {
            $menuBPrev = Menu::where('id', $menuB['prev'])->first();
        }
        if($menuB['next']) {
            $menuBNext = Menu::where('id', $menuB['next'])->first();
        }
        
        //поменять с ближним
        if($menuA['next'] === $menuB['id']) {

            $menuA['next'] = $menuBNext['id'] ?? null;
            $menuA['prev'] = $menuB['id'] ?? null;
            $menuB['prev'] = $menuAPrev['id'] ?? null;
            $menuB['next'] = $menuA['id'] ?? null;
            if($menuBNext) {
                $menuBNext['prev'] = $menuA['id'];
                $menuBNext->save();
            }
            if($menuAPrev) {
                $menuAPrev['next'] = $menuB['id'];
                $menuAPrev->save();
            }
            $menuA->save();
            $menuB->save();
        }
        //поменять с дальним
        else if($menuA['next'] !== $menuB['id']) {
            $menuA['next'] = $menuBNext['id'] ?? null;
            $menuA['prev'] = $menuBPrev['id'] ?? null;
            $menuB['prev'] = $menuAPrev['id'] ?? null;
            $menuB['next'] = $menuANext['id'] ?? null;
            $menuANext['prev'] = $menuB['id'] ?? null;
            $menuBPrev['next'] = $menuA['id'] ?? null;
            if($menuBNext) {
                $menuBNext['prev'] = $menuA['id'];
                $menuBNext->save();
            }
            if($menuAPrev) {
                $menuAPrev['next'] = $menuB['id'];
                $menuAPrev->save();
            }
            $menuANext->save();
            $menuBPrev->save();
            $menuA->save();
            $menuB->save();
        }

        return true;
    }

    //вспомогательные функции

    //сортируем в порядке следования prev | next
    private function sortPrevNext($array) {
        if(count($array)) {
            $next_id = null;
            $sorted_items = [];
            $checked_items = $array;
    
            //ищем начало
            while($checked_items) {
                $item = array_shift($checked_items);
                if($item['data']['prev'] === null) {
                    $next_id = $item['data']['id'];
                    break;
                }
            }
            
            //сортируем
            for($i=0; $i<count($array); $i++) { 
                $sorted_items[$next_id] = $array[$next_id];
                $next_id = $array[$next_id]['data']['next'];
            }
            return $sorted_items;  
        }
        return $array;
    }

    //изьять меню из позиций
    private function removeMenuFromList($id) {
        $nodePrev = null;
        $nodeNext = null;
        $node = Menu::where('id', $id)->first();
        if($node['prev']) {
            $nodePrev = Menu::where('id', $node['prev'])->first();
        }
        if($node['next']) {
            $nodeNext = Menu::where('id', $node['next'])->first();
        }
        if($nodePrev) {
            $nodePrev['next'] = $nodeNext['id'] ?? null;
            $nodePrev->save();
        }
        if($nodeNext) {
            $nodeNext['prev'] = $nodePrev['id'] ?? null;
            $nodeNext->save();
        }
    }
    
}
