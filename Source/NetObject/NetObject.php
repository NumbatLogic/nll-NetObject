<?php
	class NetObject_Config extends Project_Config
	{	
		public function __construct($sAction)
		{
			parent::__construct($sAction);
			$this->m_xFileArray = ProjectGen_ParseDirectory(dirname(__FILE__) . "/../../Transpiled/NetObject", "/\.h$|\.c$|\.hpp$|\.cpp$/");
		}

		public function GetName() { return "NetObject"; }
		public function GetKind() { return KIND_STATIC_LIBRARY; }
		public function GetBaseDirectory() { return dirname(__FILE__); }

		public function GetIncludeDirectoryArray($sConfiguration, $sArchitecture)
		{
			$sArray = array(
			//	"../LangShared"
			//	"../ThirdParty",
			//	"../Package",
			//	"../Engine",
			);

			return $sArray;
		}

		public function GetDependancyArray()
		{
			$sArray = array(
			//	"LangShared",
			//	"ThirdParty",
			//	"Package",
			//	"Engine",
			//	"Core",
			);

			/*if ($this->m_sAction == ACTION_CMAKE)
			{
				$sArray = array_merge($sArray, array(
					"X11",
					"m",
				));
			}*/

			return $sArray;
		}
	}
?>
