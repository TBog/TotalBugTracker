<?php
	/**
	 * xajax backbutton and bookmark plug-in
	 * last changed: 2007-07-27, Ralf Blumenthal (ralf@goldenzazu.de)
	 * feel free to use it
	 * 
	 * see http://code.goldenzazu.de/ for details and news
	 * 
	 * Based on Brad Neuberg's "Really Simple History" 
	 * See: http://codinginparadise.org/projects/dhtml_history/README.html for details
	 * 
	 * this file has to be located in "xajax/xajax_plugins/reponse"
	 */
	if (false == class_exists('xajaxPlugin') || false == class_exists('xajaxPluginManager'))
	{
		$sBaseFolder = dirname(dirname(dirname(__FILE__)));
		$sXajaxCore = $sBaseFolder . '/xajax_core';
		
		if (false == class_exists('xajaxPlugin'))
			require $sXajaxCore . '/xajaxPlugin.inc.php';
		if (false == class_exists('xajaxPluginManager'))
			require $sXajaxCore . '/xajaxPluginManager.inc.php';
	}
	
	class dhtmlHistoryPlugin extends xajaxResponsePlugin
	{
		var $sDefer;
		var $sJavascriptURI;
		var $bInlineScript;
		var $sWaypointFunctionName;
		
		function dhtmlHistoryPlugin()
		{
			$this->sDefer = '';
			$this->sJavascriptURI = '';
			$this->bInlineScript = true;
			$this->sWaypointFunctionName='xajax_waypoint_handler';
		}
		
		function configure($sName, $mValue)
		{
			if ('javascript URI' == $sName) {
				$this->sJavascriptURI = $mValue;
			} else if ('WaypointFunctionName' == $sName) {
				$this->sWaypointFunctionName = $mValue;
			} 
/*
 // does only work with inline script so far; todo enable script loading using redo timeout stuff
			} else if ('scriptDeferral' == $sName) {
				if (true === $mValue || false === $mValue) {
					if ($mValue) $this->sDefer = 'defer ';
					else $this->sDefer = '';
				}
			} else if ('inlineScript' == $sName) {
				if (true === $mValue || false === $mValue)
					$this->bInlineScript = $mValue;
*/			
		}

	/*
		Function: generateClientScript
		
		Called by the <xajaxPluginManager> during the script generation phase.  This
		will either inline the script or insert a script tag which references the
		<tableUpdater.js> file based on the value of the <clsTableUpdater->bInlineScript>
		configuration option.
	*/
		function generateClientScript(){
			$JSONVer=2005;  // 2005|2007
			if ($this->bInlineScript)
			{
				echo "\n<script type='text/javascript' " . $this->sDefer . " charset='UTF-8'>\n";
				echo "/* <![CDATA[ */\n";
				include(dirname(__FILE__) . "/json$JSONVer.js");
				echo "\n\n";
				include(dirname(__FILE__) . '/rsh.js');
				if ($JSONVer==2007)
					echo "\n\nwindow.dhtmlHistory.create(); \n";
				else
					echo "window.dhtmlHistory.create({toJSON: function(o) {return JSON.stringify(o);}, fromJSON: function(s) {return JSON.parse(s);}});\n";
				echo "\n\nfunction dhtmlHistoryInit() { \n";
				/*
				echo  "\tif (realOnLoadBB)\n";
				echo  "\t\trealOnLoadBB();\n";
				*/
				echo "\tdhtmlHistory.initialize();\n";
				echo "\tdhtmlHistory.addListener(".$this->sWaypointFunctionName.");\n";
				echo "\tvar s=dhtmlHistory.getCurrentLocation(); // check for bookmark hash value\n";
				echo "\tif (s.length>0)\n";
				echo "\t\t".$this->sWaypointFunctionName."(s,null);\n";
				echo "}\n";
				/* i do NOT need this init ... i have my own
				echo "var realOnLoadBB = null;\n";
				echo "if (window.onload){\n";
				echo "\trealOnLoadBB=window.onload;\n";
				echo "\talert('added one more onload');\n";
				echo "}\n";
				echo "window.onload=dhtmlHistoryInit;\n";
				*/
				echo "/* ]]> */\n";
				echo "</script>\n";
			} else {
				echo "\n<script type='text/javascript' src='" . $this->sJavascriptURI . "dhtmlHistory.js' " . $this->sDefer . "charset='UTF-8'>\n";
			}
		}
		
		function getName() {
			return "dhtmlHistoryPlugin";
		}
		
	   	function addWaypoint($sWaypointName, $aWaypointData) {
	   		$sWaypointData = base64_encode(serialize($aWaypointData));
	   		//$this->objResponse->script("dhtmlHistory.add('$sWaypointName','$sWaypointData')");
			$this->addCommand(array('cmd'=>'js'),"dhtmlHistory.add('$sWaypointName','$sWaypointData')" );	   		
	   	}
		
	}
	
	// register rsh plugin	
	$pluginManager =& xajaxPluginManager::getInstance();
	$pluginManager->registerPlugin(new dhtmlHistoryPlugin());
	
	/**
	 * xajax 0.5 plugin for backbutton and bookmark
	 * 
	 * helper functions 
	 * 
	 */

   	/**
   	 * Adds a waypoint into the backbutton history
   	 *
   	 * @param unknown_type $objResponse
   	 * @param string $sWaypointName
   	 * @param mixed $aWaypointData
   	 */
   	function dhtmlHistoryAdd(&$objResponse, $sWaypointName, $aWaypointData)
   	{
		global $global_historyadd_block;
		if ( empty($global_historyadd_block) )
		{
			//$objResponse->alert('dhtmlHistoryAdd( '.$sWaypointName.' , '.s_print_r($aWaypointData).' )');
			$objResponse->plugin('dhtmlHistoryPlugin', 'addWaypoint', $sWaypointName, $aWaypointData);	 //php4 and php5
			//$objResponse->dhtmlHistoryPlugin->addWaypoint($sWaypointName, $aWaypointData);	    	//php5 only   		
		}
   	}
	
	/**
	 * decode the stringified waypoint data (see addWaypoint() also)
	 */
   	function decodeWaypointData($sWaypointData)
   	{
   		return (is_string($sWaypointData)?(unserialize(base64_decode($sWaypointData))):'');
   	}
	
	/**
	 * block / deblock adding to history
	 * used to disable dhtmlHistoryAdd when restoring the Waypoint
	 * 
	 * @param bool $bBlock
	 */
	function blockHistoryAdd($bBlock)
	{
		global $global_historyadd_block;
		$global_historyadd_block = $bBlock;
	}
?>