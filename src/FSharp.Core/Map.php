<?php



class MapEmpty extends MapTree {
    function __construct() {

    }
}
class MapOne extends MapTree {
    public $key;
    public $value;
    function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }
} 

class MapNode extends MapTree {
    public $key;
    public $value;
    public $left;
    public $right;
    public $height;

    function __construct($key, $value, $left, $right, $height)
    {
        $this->key = $key;
        $this->value = $value;
        $this->left = $left;
        $this->right = $right;
        $this->height = $height;
    }
}

class MapTree {
    static function sizeAux($acc,$m)
    { 
        switch(get_class($m))
        {
            case 'MapEmpty':
                return $acc;
            case 'MapOne':
                return $acc + 1;
            default:
                return MapTree::sizeAux(MapTree::sizeAux($acc+1, $m->left), $m->right); 
        }
    }

    static function size($x) {
        return MapTree::sizeAux(0, $x);
    }
    
    static function height ($m)
    { 
        switch(get_class($m))
        {
            case 'MapEmpty':
                return 0;
            case 'MapOne':
                return 1;
            default:
                return $m->height;
        }
    }

    static function isEmpty($m)
    { 
        return $m instanceof MapEmpty; 
    }

    static function mk($l, $k, $v, $r)
    { 
        if($l instanceof MapEmpty && $r instanceof MapEmpty)
            return new MapOne ($k, $v);
        $hl = MapTree::height($l); 
        $hr = MapTree::height($r); 
        $m = $hl < $hr ? $hr : $hl; 
        return new MapNode ($k, $v, $l, $r, $m+1);
    }

    static function rebalance($t1, $k, $v, $t2)
    {
        $t1h = MapTree::height($t1); 
        $t2h = MapTree::height($t2); 
        if ($t2h > $t1h + 2)
        {
            // right is heavier than left
            if ($t2 instanceof MapNode)
            { 
                $t2k = $t2->key;
                $t2v = $t2->value;
                $t2l = $t2->left;
                $t2r = $t2->right; 
                // one of the nodes must have height > height t1 + 1 
                if (MapTree::height($t2l) > $t1h + 1)
                {
                    // balance left: combination 
                    if ($t2l instanceof MapNode)
                    { 
                        $t2lk = $t2l->key;
                        $t2lv = $t2l->value;
                        $t2ll = $t2l->left;
                        $t2lr = $t2l->right;
                        return MapTree::mk(MapTree::mk($t1, $k, $v, $t2ll), $t2lk, $t2lv, MapTree::mk($t2lr, $t2k, $t2v, $t2r)); 
                    } else
                    {
                        throw new Error("rebalance");
                    }
                }
                else
                { 
                    // rotate left 
                    return MapTree::mk(MapTree::mk($t1, $k, $v, $t2l), $t2k, $t2v, $t2r);
                }
            }
            else
            {
                throw new Error("rebalance");
            }
        }
        else
        {
            if  ($t1h > $t2h + 2) 
            {
                // left is heavier than right
                if ($t1 instanceof MapNode)
                {
                    $t1k = $t1->key;
                    $t1v = $t1->value;
                    $t1l = $t1->left;
                    $t1r = $t1->right; 
                    // one of the nodes must have height > height t2 + 1 
                    if (MapTree::height($t1r) > $t2h + 1) 
                    {
                        // balance right: combination 
                        if ($t1r instanceof MapNode)
                        {
                            $t1rk = $t1r->key;
                            $t1rv = $t1r->value;
                            $t1rl = $t1r->left;
                            $t1rr = $t1r->right;
                            return MapTree::mk(MapTree::mk($t1l, $t1k, $t1v, $t1rl), $t1rk, $t1rv, MapTree::mk($t1rr, $k, $v, $t2));
                        }
                        else
                        {
                            throw new Error("rebalance");
                        }
                    }
                    else
                        return MapTree::mk($t1l, $t1k, $t1v, MapTree::mk($t1r, $k, $v, $t2));
                }
                else
                {
                    throw new Error("rebalance");
                }
            }
            else 
            {
                return MapTree::mk($t1, $k, $v, $t2);
            }
        }
    }

    static function add($comparer, $k, $v, $m)
    { 
        switch(get_class($m))
        {
            case 'MapEmpty':
                return new MapOne($k, $v);
            case 'MapOne':
                $k2 = $m->key; 
                $c = $comparer['Compare']($k, $k2); 
                if ($c < 0) 
                    return new MapNode($k, $v, new MapEmpty(), $m, 2);
                elseif ($c == 0)
                    return new MapOne($k, $v);
                else
                    return new MapNode($k, $v, $m, new MapEmpty(), 2);
            default:
                $k2 = $m->key;
                $v2 = $m->value;
                $l = $m->left;
                $r = $m->right;
                $h = $m->height;
                $c = $comparer['Compare']($k, $k2); 
                if ($c < 0) 
                    return MapTree::rebalance(MapTree::add($comparer, $k, $v, $l), $k2, $v2, $r);
                elseif ($c == 0) 
                    return new MapNode($k, $v, $l, $r, $h);
                else
                    return MapTree::rebalance($l, $k2, $v2, MapTree::add($comparer, $k, $v, $r)); 
        }
    }


    static function tryGetValue($comparer, $k, &$v, $m)
    { 
        switch(get_class($m))
        { 
            case 'MapEmpty':
                return false;
            case 'MapOne':
                $k2 = $m->key;
                $v2 = $m->value;
                $c = $comparer['Compare']($k, $k2); 
                if ($c == 0)
                    { $v = $v2;
                       return true;
                    }
                else
                    return false;
            default:
                $k2= $m->key;
                $v2= $m->value;
                $l= $m->left;
                $r= $m->right;
                $c = $comparer['Compare']($k, $k2); 
                if ($c < 0) 
                    return MapTree::tryGetValue($comparer, $k, $v, $l);
                elseif ($c == 0) 
                    { $v = $v2; 
                      return true; }
                else 
                    return MapTree::tryGetValue($comparer, $k, $v, $r);
        }
    }

    static function find($comparer, $k, $m)
    {
        if (MapTree::tryGetValue($comparer, $k, $v, $m))
            return $v;
        else
            throw new Exception("Key not found");
    }

    static function tryFind($comparer, $k, $m)
    { 
        if (MapTree::tryGetValue($comparer, $k, $v, $m))
            return $v;
        else
            return NULL;
    }

    static function fold($f, $x, $m)
    {
        switch (get_class($m))
        {
            case 'MapEmpty': 
                return $x;
            case 'MapOne':
                return $f($x, $m->key, $m->value);
            default:
                $x = MapTree::fold($f, $x, $m->left);
                $x = $f($x, $m->key, $m->value);
                return MapTree::fold($f, $x, $m->right);
        }
    }

    static function exists($f, $m)
    { 
        switch (get_class($m))
        {
            case 'MapEmpty': 
                return false;
            case 'MapOne':
                return $f($m->key, $m->value);
            default:
                return 
                    MapTree::exists($f, $m->left)
                    || $f($m->key, $m->value)
                    || MapTree::exists($f, $m->right);
        }
    }

    static function mapi($f,$m)
    {
        switch(get_class($m))
        {
            case 'MapEmpty';
                return $m;
            case 'MapOne':
                return new MapOne ($m->key, $f($m->key, $m->value));
            default: 
                $k = $m->key;
                $l2 = MapTree::mapi($f, $m->left); 
                $v2 = $f($k, $m->value); 
                $r2 = MapTree::mapi($f, $m->right); 
                return new MapNode ($k, $v2, $l2, $r2, $m->height);
        }
    }
}

class Map implements IteratorAggregate
{
    // This type is logically immutable. This field is only mutated during deserialization.
    public $Comparer;
 
    // This type is logically immutable. This field is only mutated during deserialization.
    public $Tree;

    function __construct($comparer, $tree)
    {
        $this->Comparer = $comparer;
        $this->Tree = $tree;
    }

    static $empty;

    static function empty($comparer) 
    {
        return new Map($comparer, new MapEmpty());
    } 

    static function count($table)
    {
        return MapTree::size($table->Tree);
    }

    static function ofList($list)
    {
        $tree = new MapEmpty();
        $comparer = [ 'Compare' => 'Util::compare' ];

        while ($list instanceof Cons)
        {
            $tree = MapTree::add($comparer, $list->value[0], $list->value[1], $tree);
            $list = $list->next;
        }

        return new Map($comparer, $tree);
    }
    
    static function ofSeq($seq)
    {
        $tree = new MapEmpty();
        $comparer = [ 'Compare' => 'Util::compare' ];

        foreach ($seq as $item)
        {
            $tree = MapTree::add($comparer, $item[0], $item[1], $tree);
        }

        return new Map($comparer, $tree);
    }

    static function ofArray($seq)
    {
        $tree = new MapEmpty();
        $comparer = [ 'Compare' => 'Util::compare' ];

        foreach ($seq as $item)
        {
            $tree = MapTree::add($comparer, $item[0], $item[1], $tree);
        }

        return new Map($comparer, $tree);
    }

    static function add($key, $value, $table)
    {
        return new Map($table->Comparer, MapTree::add($table->Comparer, $key, $value, $table->Tree));
    }

    static function find($key, $table)
    {
        return MapTree::find($table->Comparer,$key,$table->Tree);
    }
    static function tryFind($key, $table)
    {
        return MapTree::tryFind($table->Comparer,$key,$table->Tree);
    }


    static function toSeq($table)
    {
        return $table;
    }

    
    static function toList($table)
    {
        return FSharpList::ofSeq($table);
    }

    static function fold($f,$acc,$table)
    {
        return MapTree::fold($f,$acc,$table->Tree);
    }

    static function exists($f, $table)
    {
        return MapTree::exists($f, $table->Tree);
    }
    static function map($f, $table)
    {
        return new Map($table->Comparer, MapTree::mapi($f, $table->Tree));
    }
    static function FSharpMap__get_Item__2B595($table, $key)
    {
        return MapTree::find($table->Comparer, $key, $table->Tree);
    }

    public function getIterator() {
        $stack = [];
        $tree = $this->Tree;
        while(!is_null($tree))
        {
            switch(get_class($tree))
            {
                case 'MapOne':
                    yield [$tree->key, $tree->value];
                    $tree = array_pop($stack);
                break;
                case 'MapNode':
                    array_push($stack, $tree->right);
                    array_push($stack, new MapOne($tree->key, $tree->value));
                    $tree = $tree->left;
                break;
                default:
                    $tree = array_pop($stack);
                break;
            }
        }
    } 
    
    

}

