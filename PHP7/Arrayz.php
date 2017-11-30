<?php 
//namespace CodeIgniter\Arrayz;
/**
* Array Manipulations
* Contributor - Giri Annamalai M
* Version - 2.0
* PHP version - 7.0
*/
class Arrayz
{
	private $source;
	private $operator;
	public function __construct($array=[])
	{
		$this->source = [];
		$this->worker = [];
		//$fixedArray = new SplFixedArray($size);
		if($this->_chk_arr($array))
		{
			$this->source = $array;
		}
	}

	/*
	* Object to callable conversion
	*/
	public function __invoke($source=[])
	{
		$this->source = $source;
		return $this;
	}

	private function _chk_arr($array)
	{
		if(is_array($array) && count($array) > 0 )
		{
			return true;
		}
	}

	/*
	* Select the keys and return only them	
	* @param1: 'key1,key2', must be comma seperated.
	*/
	public function select()
	{		
		$args = func_get_args();
		$this->select_fields = $this->format_select($args[0]);
		$preserve = $args[1] ?? FALSE;
		$this->worker['select'] = ['type' => 'select', 'args' => $args, 'priority' => '4', 'preserve' => $preserve];
		return $this;
	}	

	/*
	* Where	
	* @param1: can be array, or string
	*/
	public function where()
	{		
		$args = func_get_args();		

		$preserve = '';
		if(is_string($args[0]))
		{
			if(func_num_args() == 2)
			{				
				$o[] = $args[0];
				$o[] = '=';
				$o[] = $args[1];				
				$this->conditions[] = $o;
			}
			$preserve = $args[3] ?? FALSE;
			$this->conditions[] = $args;
		}else if(is_array($args[0])) {			
			$this->conditions = $this->format_conditions($args[0]);
			$preserve = $args[1] ?? FALSE;
		}
		$this->worker['where'] = [ 'type' => 'resolve_where', 'priority' => '1', 'preserve' => $preserve];
		return $this;
	}

	public function get()
	{
		$this->resolver();		
		if(is_array($this->source) && count($this->source)==0)
 	 	{
 	 		return NULL;
 	 	}		
 	 	if(!is_array($this->source) && $this->source=='')
 	 	{
	 	 	return NULL;
 		}		
		return $this->source;		
	}

	/* Resolve and coordinate combining function calls */
	public function resolver()
	{
		$this->priorizer();
		if(!empty($this->worker['where']))
		{
			$this->{$this->worker['where']['type']}();
		}
	}

	/* Combine functions to reduce loops*/
	public function dependency_optimizer()
	{	
		return TRUE;
	}

	public function resolve_where()
	{
		if(!empty($this->worker['select']))
		{	
			$type_select = count($this->select_fields) == 1 ? 'resolve_single_select_where': 'resolve_select_where';		
			array_walk($this->source, array($this, 'resolve_select_where'));
		}
		else{
			$type_select = count($this->conditions) == 1 ? 'resolve_filter': 'resolve_multi_filter';	
			$source = array_filter($this->source, array($this, $type_select), ARRAY_FILTER_USE_BOTH);
			$this->source = $source;
		}
	}

	public function resolve_filter($v, $k): bool
	{				
		return isset($v[$this->conditions[0][0]]) && $this->_operator_check($v[$this->conditions[0][0]], $this->conditions[0][1], $this->conditions[0][2]);
	}

	public function resolve_multi_filter($src_v, $src_k): bool
	{		
		$resp = FALSE;
		foreach ($this->conditions as $k => $v) {
			$resp = (isset($src_v[$v[0]])) ? ($this->_operator_check($src_v[$v[0]], $v[1], $v[2])) : $resp;
			if($resp==FALSE)  break; //If one condition fails break it. It's not the one, we are searching
		}
		return $resp;
	}

	public function resolve_single_select_where(&$v, &$k)
	{
		isset($v[$this->select_fields]) ?  $v = $v[$this->select_fields] : '';
	}	

	public function resolve_select_where(&$v, &$k)
	{
		$v = array_intersect_key($v, $this->select_fields);
	}
	// public function select_where($v, $k)

	private function format_conditions($cond=''): array
	{		
		$o = []; $i=0;
		array_walk($cond, function($v, $k) use (&$o, &$i) {
			$key = array_map('trim', explode(" ", $k));			
			$key[1] = $key[1] ?? '='; //Default is =			
			$key[2] = $v;			
			$o[$i] = $key;
			$i++;
		});
		return $o;
	}

	/*
	* Check with operators
	*/
    private function _operator_check($retrieved, $operator , $value): bool
	{
		switch ($operator) {
		    default:
		    case '=':
		    case '==':  return $retrieved == $value;
		    case '!=':
		    case '<>':  return $retrieved != $value;
		    case '<':   return $retrieved < $value;
		    case '>':   return $retrieved > $value;
		    case '<=':  return $retrieved <= $value;
		    case '>=':  return $retrieved >= $value;
		    case '===': return $retrieved === $value;
		    case '!==': return $retrieved !== $value;
		}
	}

	private function format_select(string $select=''): array
	{		
		$select = array_map('trim', explode(",", $select));
		$this->select_fields = count($select) == 1 ? $select[0] : array_flip($select);
	}

	/* Get which is prior */
	public function priorizer()
	{
		$sort_by = array_column($this->worker, 'priority');
		array_multisort($sort_by, SORT_ASC, $this->worker);
	}
}
/* End of the file Arrayz.php */
