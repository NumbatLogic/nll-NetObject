<?php
	class NetObject_Config extends Project_Config
	{	
		public function __construct($sAction)
		{
			parent::__construct($sAction);
			$this->m_xFileArray = ProjectGen_ParseDirectory(dirname(__FILE__) . "/../../Transpiled/NetObject", ProjectGen_GetSourceRegex($sAction));
		}

		public function GetName() { return "NetObject"; }
		public function GetKind() { return KIND_STATIC_LIBRARY; }
		public function GetBaseDirectory() { return dirname(__FILE__); }

		public function GetDependancyArray()
		{
			return array(
				"LangShared"
			);
		}
	}
?>
