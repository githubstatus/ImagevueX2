/**
 * register and broadcast events, mirror of AsBroadcaster.
 * 
 * @author		Gabriel Bucknall
 * @version		1.0
 **/
class com.pixelbreaker.event.EventBroadcaster
{
	private var _listeners:Array;
	
	function EventBroadcaster()
	{
		_listeners = [];
	}
	
	/**
	 * Adds an object to the EventBroadcaster instance
	 * @usage   
	 * <code>
	 * import com.pixelbreaker.event.EventBroadcaster;
	 * 
	 * var myBroadcaster:EventBroadcaster = new EventBroadcaster();
	 * 
	 * var myListener:Object = new Object();
	 * myListener.onEventName = function()
	 * {
	 *	// something to execute
	 * }
	 * 
	 * myBroadcaster.addListener(myListener);
	 * </code>
	 * @param   obj		Object to add to the _listeners array
	 * @return  Boolean true if added, false if already exists
	 */
	public function addListener(obj:Object):Boolean
	{
		if(indexOf(obj) == -1)
		{
			_listeners.push(obj);
			return true;
		}
		return false;
	}
	
	/**
	 * Removes an object from the EventBroadcaster instance
	 * @usage   
	 * <code>
	 * import com.pixelbreaker.event.EventBroadcaster;
	 * 
	 * myBroadcaster.removeListener(myListener);
	 * </code>
	 * @param   obj 	Object to remove from the _listeners array
	 * @return  Boolean true if removed, false if didn't exist
	 */
	public function removeListener(obj:Object):Boolean
	{
		var removed:Boolean = false;
		while(indexOf(obj) != -1)
		{
			_listeners.splice(indexOf(obj), 1);
			removed = true;
		}
		return removed;
	}
	
	/**
	 * Broadcast and event to the registered listeners in the _listeners array
	 * @usage   
	 * <code>
	 * import com.pixelbreaker.event.EventBroadcaster;
	 * 
	 * myBroadcaster.broadcastMessage("onEventName"[, arg1, arg2, argN]);
	 * 
	 * </code>
	 * @param   method				String name of method to call
	 * @param	furtherArguments	Add as many arguments as you like to be passed to the event methods
	 * @return  Nothing
	 */
	public function broadcastMessage( method:String ):Void
	{
		var i:Number;
		var obj:Object;
		var args:Array = arguments.splice( 1 );
		for(i=0; i<_listeners.length; i++)
		{
			obj = _listeners[i];
			obj[ method ].apply( obj, args );
			if(obj == undefined)
			{
				_listeners.splice(i--, 1);
			}
		}
	}
	
	private function indexOf(str):Number
	{
		var foundIndex = -1;
		for(var i=0; i<_listeners.length; i++){
			if(_listeners[i] == str){
				foundIndex = i;
				break;
			}
		}
		return foundIndex;
	}
}