<?php

class RangeList implements IteratorAggregate
{
	public $sequences = [];
	
	public function addRange($start, $finish){
		$this->sequences[$start] = $finish;
	}
	public function add($start){
		$this->sequences[$start] = $start;
	}
	
	#[ReturnTypeWillChange]
	public function getIterator(){
		return function(){
			foreach($this->sequences as $start => $end){
				for($s = $start; $s <= $end; ++$s){
					yield $s;
				}
			}
		};
	}
}

