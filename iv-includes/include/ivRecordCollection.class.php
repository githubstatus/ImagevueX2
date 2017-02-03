<?php

class ivRecordCollection extends ArrayIterator
{

	public function toArray()
	{
		$array = array();
		foreach ($this as $record) {
			$array[] = $record->toArray();
		}
		return $array;
	}

	public function filter(ivFilter $filter)
	{
		$collection = new ivRecordCollection();
		foreach ($this as $record) {
			if ($filter->accept($record)) {
				$collection->append($record);
			}
		}
		return $collection;
	}

	public function shuffle()
	{
		$collection = new ivRecordCollection();
		$array = array();
		foreach ($this as $record) {
			$array[] = $record;
		}
		shuffle($array);
		foreach ($array as $record) {
			$collection->append($record);
		}
		return $collection;
	}

}