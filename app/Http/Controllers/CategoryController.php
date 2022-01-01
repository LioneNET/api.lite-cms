<?php
namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
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
                    'alias'=>$node['data']['alias'],
                    'image'=>$node['data']['image'],
                    'children'=>loop($node['childs'])
                ];
            }
            return $arr;
        }


        return response()->json(loop($this->getCategoryItems()));
    }

    //возвращает массив категорй
    public function get_breadcrumb($id=false) {
        $items = [];
        $cats = Category::all();
        function tree($id, $cats, $deep, $count) {
            $deep++;
            $item = [];
            foreach($cats as $val) {
                if($deep>=$count) {
                    return [null];
                }
                if($val['id'] == $id) {
                    $item['id_'.$val['id']] = ['id'=>$id, 'name'=>$val['name']];
                    if($val['id_parent'] === null) {
                        return $item;
                    } else {
                        return array_merge($item, tree($val['id_parent'], $cats, $deep, $count));
                    }
                }
            }
        }
        if(!$id) {
            foreach($cats as $val) {
                $items['id_'.$val['id']] = tree($val['id'], $cats, 0, count($cats));
            }
        } else {
            $items = tree($id, $cats, 0, count($cats));
            if(array_search(null, $items)) {
                return false;
            }
        }
        return array_reverse($items);
    }

    //Cоздания 
    public function apiCreateCategory(Request $resourse) {
        $data = $resourse->all();
        $this->addNode($data);
        return $this->execute($resourse);
    }

    //Добавление 
    public function apiAddCategory(Request $resourse) {
        //return response()->json($resourse->all());
        $parent_id = $resourse->all()['id_parent'];
        $this->addNode($resourse->all(), $parent_id);
        return $this->execute($resourse);
    }

    //Oбновить 
    public function apiUpdateCategory(Request $resourse) {
        $data = $resourse->all();
        $node = Category::where('id', $data['id'])->first();
        $node->name = $data['name'];
        $node->alias = $data['alias'] ?? null;
        $node->image = $data['image'] ?? null;
        $node->save();
        if($data['moveNodeToID']) {
            $this->MoveNode($data['id'], $data['moveNodeToID']);
        }

        return $this->execute($resourse);
    }

    //Удалить 
    public function apiDeleteCategory(Request $resourse) {
        $data = $resourse->all();
        $this->deleteCategory($data['id']);
        return $this->execute($resourse);
    }

     /**
     * Добавить узел в узел по id
     * 
     * @param Menu $data объект узла
     * @param int $id узел родителя
     * 
     */
    private function addNode($data, $id=null) {
        //создаем корневую ноду
        if($id===null) {
            $newNode = new Category();
            $newNode->name = $data['name'];
            $newNode->alias = $data['alias'] ?? null;
            $newNode->image = $data['image'] ?? null;
            $newNode->save();
            return $newNode->id;
        }

        $rootNode = Category::where('id', $id)->first();
        $lastChildNode = Category::where('id_parent', $rootNode['id'])->where('next', null)->first();
        $newNode = new Category();
        $newNode['name'] = $data['name'];
        $newNode['alias'] = $data['alias'] ?? null;
        $newNode['image'] = $data['image'] ?? null;
        $newNode['id_parent'] = $rootNode['id'];

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
    private function deleteCategory($id) {
        $childs = array_merge([$id], $this->getAllChildNodesByID($id, $this->getCategoryItems($id)));
        $this->removeCategoryFromList($id);
        Category::whereIn('id', $childs)->delete();
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
        $nodeA = Category::where('id', $a)->first();
        $nodeB = Category::where('id', $b)->first();

        //запрет на перемещение в тот же родительский узел
        if($nodeA['id_parent'] === $nodeB['id']) {
            return false;
        }
        //запрет на перемещение корневого узла
        if($nodeA['id_parent'] === null) {
            return false;
        }
        //запрет перемещение родительского узла в потомка
        $childNodes = $this->getAllChildNodesByID($a, $this->getCategoryItems($a));
        if(array_search($b, $childNodes)) {
            return false;
        }

        $this->removeCategoryFromList($nodeA['id']); 

        //устанавливаем перемещаемый узел $a в конец узлов $b если есть
        $nodeBEnd = Category::where('id_parent', $nodeB['id'])->where('next', null)->first();

        if($nodeBEnd) {
            $nodeBEnd['next'] = $nodeA['id'];
            $nodeA['prev'] = $nodeBEnd['id'];
            $nodeBEnd->save();
        }else {
            $nodeA['prev'] = null;
        }
        $nodeA['id_parent'] = $nodeB['id'];
        $nodeA['next'] = null; 
        $nodeA->save();

        return true;
    }

    //пункты меню
    /**
     * возвращает дерево нод меню в виде data child
     * @param null|int $root_id id корневой ноды или null
     * @return array если null то дерево всех нод
     */
    private function getCategoryItems($root_id = null) {

        $root_nodes = [];
        $sub_nodes = [];
        $array = Category::OrderBy('id', 'ASC')->get()->toArray();

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
                $root_nodes[$items['id']] = [
                    'data'=>$items,
                    'childs' => []
                ];
            } else {
                $sub_nodes[$items['id']] = $items;
            }
        }

        //проходим по корневым узлам и ищем подузлы
        foreach($root_nodes as $id=>$items) {
            $root_nodes[$id]['childs'] = $sub_items($id, $sub_nodes);
        }

        return $root_nodes;
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
    private function removeCategoryFromList($id) {
        $nodePrev = null;
        $nodeNext = null;
        $node = Category::where('id', $id)->first();
        if($node['prev']) {
            $nodePrev = Category::where('id', $node['prev'])->first();
        }
        if($node['next']) {
            $nodeNext = Category::where('id', $node['next'])->first();
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
