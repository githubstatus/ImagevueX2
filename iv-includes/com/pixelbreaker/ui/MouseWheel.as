import flash.external.ExternalInterface;
import com.pixelbreaker.event.EventBroadcaster;
/**
 * @author Gabriel Bucknall
 * 
 * Class that supports using the mouseWheel on Mac OS, requires javascript class
 * swfmacmousewheel.js
 */
class com.pixelbreaker.ui.MouseWheel
{
	private static var isMac:Boolean;
	private static var macBroadcaster:EventBroadcaster;
	
	private static function main():Void
	{
		isMac = System.capabilities.os.toLowerCase().indexOf( "mac" ) != -1;
		if( isMac )
		{
			macBroadcaster = new EventBroadcaster();
			ExternalInterface.addCallback( "externalMouseEvent", MouseWheel, MouseWheel.externalMouseEvent );	
		}
	}
	/**
	 * Add a listener for using the mouseWheel
	 * obj param must have an "onMouseWheel" method to be called.
	 */
	public static function addListener( obj:Object )
	{
		if( !isMac ) MouseWheel.main();
		if( isMac )
		{
			macBroadcaster.addListener( obj );
		}else{
			Mouse.addListener( obj );	
		}
	}
	
	/**
	 * Remove a listener
	 */
	public static function removeListener( obj:Object ):Void
	{
		if( isMac )
		{
			macBroadcaster.removeListener( obj );
		}else{
			Mouse.removeListener( obj );	
		}
	}
	
	private static function externalMouseEvent( delta:Number ):Void
	{
		macBroadcaster.broadcastMessage( "onMouseWheel", delta );
	}
}