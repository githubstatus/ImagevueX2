<?php

class ivFilterIteratorDot extends FilterIterator
{

	public function accept()
	{
		return ('.' != substr($this->current()->getFilename(), 0, 1) && '_vti' != substr($this->current()->getFilename(), 0, 4));
	}

}
