<?php
	include_once dirname(__FILE__) . "/../../ProjectGen/ProjectGen.php";
	include_once dirname(__FILE__) . "/../../LangShared/LangShared.php";

	include_once dirname(__FILE__) . "/NetObject/NetObject.php";
	include_once dirname(__FILE__) . "/NetObjectTest/NetObjectTest.php";

	class NetObject_Solution_Config extends Solution_Config
	{
		public function __construct($sAction)
		{
			parent::__construct($sAction);

			$this->m_pProjectArray[] = new LangShared_Config($sAction, dirname(__FILE__) . "/LangShared.package-list");
			$this->m_pProjectArray[] = new NetObject_Config($sAction);
			$this->m_pProjectArray[] = new NetObjectTest_Config($sAction);
		}

		public function GetName() { return "NetObject"; }
	}

	ProjectGen(new NetObject_Solution_Config(ProjectGen_GetAction()));
?>
