<?php

class ivRecursiveFolderIterator implements RecursiveIterator
{

	private $_collection;

	public function __construct($collection)
	{
		if (is_a($collection, 'ivFolder')) {
			$this->_collection = new ArrayIterator(array($collection));
		} else if (is_a($collection, 'ivRecordCollection')) {
			$this->_collection = $collection;
		} else if (empty($collection)) {
			$this->_collection = new ArrayIterator(array());
		} else {
			throw new Exception('Argument 1 passed to ' . __METHOD__ . '() must be an instance of ivFolder or ivRecordCollection, instance of ' . get_class($collection) . ' given');
		}
	}

	public function getChildren()
	{
		return new ivRecursiveFolderIterator($this->current()->Folders);
	}

	public function hasChildren()
	{
		return $this->_collection->count();
	}

	public function current()
	{
		return $this->_collection->current();
	}

	public function key()
	{
		return $this->_collection->key();
	}

	public function next()
	{
		$this->_collection->next();
	}

	public function rewind()
	{
		$this->_collection->rewind();
	}

	public function valid()
	{
		return $this->_collection->valid();
	}

}

class ivRecursiveFolderIteratorVisible extends RecursiveFilterIterator
{

	public function accept()
	{
		return !$this->current()->isHidden() && (IS_MOBILE && $this->current()->showOnMobile || !IS_MOBILE && $this->current()->showInHtml);
	}

}

class ivRecursiveFolderIteratorPassword extends RecursiveFilterIterator
{

	private $_password;

	public function __construct (RecursiveIterator $iterator, $password = '')
	{
		parent::__construct($iterator);
		$this->_password = $password;
	}

	public function accept()
	{
		return true;
	}

	public function hasChildren()
	{
		return $this->current()->checkPassword($this->_password);
	}

}

class ivRecursiveFolderIteratorShare extends RecursiveFilterIterator
{

	public function accept()
	{
		return ('share' != $this->current()->name);
	}

}