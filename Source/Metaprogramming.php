<?
	global $g_pNetObjectArray;
	$g_pNetObjectArray = array();

	function GetPrefix($sType)
	{
		switch ($sType)
		{
			case "Uint32": return "n";
			case "string": return "s";
		}
		return "p";
	}

	class NetObjectField
	{
		public string $m_sType;
		public string $m_sName;
		public bool $m_bVector;
		public int $m_nDataIndex;

		function __construct(string $sType, string $sName, bool $bVector)
		{
			$this->m_sType = $sType;
			$this->m_sName = $sName;
			$this->m_bVector = $bVector;
			$this->m_nDataIndex = 0;
		}

		function GetMemberType()
		{
			if ($this->IsString())
				return "InternalString*";
			return $this->m_sType;
		}

		function GetMemberName()
		{
			return "__" . GetPrefix($this->m_sType) . $this->m_sName;
		}

		function IsBasicType()
		{
			return $this->m_sType == "Uint32";
		}

		function IsString()
		{
			return $this->m_sType == "string";
		}

		function IsCustomType()
		{
			return !$this->IsBasicType() && !$this->IsString();
		}

		function GetBlobPackType()
		{
			if ($this->IsString())
				return "InternalString";
			return $this->m_sType;
		}

		function GetDataType()
		{
			if ($this->IsBasicType())
				return $this->m_sType;
			if ($this->IsString())
				return "String";
			return "Object"; // todo vector
		}

		function GetFieldInfoType()
		{
			switch ($this->m_sType)
			{
				case "Uint32": return "NetObject::FieldInfo::Type::UINT32";
				case "string": return "NetObject::FieldInfo::Type::STRING";
			}
			return "NetObject::FieldInfo::Type::OBJECT";
		}
	}


	class NetObject
	{
  		public $m_sName;
		public $m_pFieldArray;
		public $m_nDataSizeMap;
  		
		function __construct(string $sName, array $pFieldArray)
		{
			$this->m_sName = $sName;
			$this->m_pFieldArray = $pFieldArray;

			$this->m_nDataSizeMap = array(
				"Uint32" => 0,
				"String" => 0,
				"Vector" => 0,
				"Object" => 0);

			for ($j = 0; $j < sizeof($this->m_pFieldArray); $j++)
			{
				$pField = $this->m_pFieldArray[$j];
				$pField->m_nDataIndex = $this->m_nDataSizeMap[$pField->GetDataType()]++;
			}
		}

		public function Output()
		{
			if (sizeof($this->m_pFieldArray) == 0)
			{
				echo $this->m_sName . " has no fields, skipping!!!!\n";
				return;
			}

			Output("\tclass " . $this->m_sName . "Info : NetObject::Info\n");
			Output("\t{\n");
				Output("\t\tpublic static " . $this->m_sName . "Info __pStatic = null;\n");
				Output("\t\tpublic construct() : base(");
					Output("" . $this->m_nDataSizeMap["Uint32"]);
					Output(", " . $this->m_nDataSizeMap["String"]);
					Output(", " . $this->m_nDataSizeMap["Object"]);
					Output(", " . $this->m_nDataSizeMap["Vector"]);
				Output(")\n");
				Output("\t\t{\n");
				Output("\t\t\tAssert::Plz(__pStatic == null);\n");
				Output("\t\t\t__pStatic = this;\n");
				for ($j = 0; $j < sizeof($this->m_pFieldArray); $j++)
				{
					$pField = $this->m_pFieldArray[$j];
					Output("\t\t\t__pFieldInfoVector.PushBack(new NetObject::FieldInfo(" . $pField->GetFieldInfoType() . ", \"" . $pField->m_sName . "\", " . $pField->m_nDataIndex . "));\n");
				}
				Output("\t\t}\n");
				Output("\t\tpublic destruct() { __pStatic = null; }\n");

				Output("\t\tpublic static " . $this->m_sName . "Info GetStatic()\n");
				Output("\t\t{\n");
					Output("\t\t\tAssert::Plz(__pStatic != null);\n");
					Output("\t\t\treturn __pStatic;\n");
				Output("\t\t}\n");

				if ($this->m_nDataSizeMap["Object"] + $this->m_nDataSizeMap["Vector"] > 0)
				{
					Output("\t\tpublic override NetObject::Object** __CreateChildObject(int nFieldIndex, NetObject::Filter pFilter)\n");
					Output("\t\t{\n");
						Output("\t\t\tswitch (nFieldIndex)\n");
						Output("\t\t\t{\n");
							for ($j = 0; $j < sizeof($this->m_pFieldArray); $j++)
							{
								$pField = $this->m_pFieldArray[$j];
								if ($pField->IsCustomType())
									Output("\t\t\t\tcase " . $j . ": return new " . $pField->m_sName . "();\n");
							}
						Output("\t\t\t}\n");
						Output("\t\t\tAssert::Plz(false);\n");
						Output("\t\t\treturn null;\n");
					Output("\t\t}\n");
				}
			Output("\t}\n");
			Output("\n");

			Output("\tclass " . $this->m_sName . " : NetObject::Object\n");
			Output("\t{\n");
				Output("\t\tpublic construct(NetObject::Filter pFilter = null) : base(" . $this->m_sName . "Info::GetStatic()");
				Output(", pFilter) { }\n");
				for ($j = 0; $j < sizeof($this->m_pFieldArray); $j++)
				{
					$pField = $this->m_pFieldArray[$j];
					Output("\t\tpublic " . $pField->m_sType . " Get" . $pField->m_sName . "() { ");
					if ($pField->IsCustomType())
						Output("return cast " . $pField->m_sType . "(Get" . $pField->GetDataType() . "(" . $pField->m_nDataIndex . "));");
					else
						Output("return Get" . $pField->GetDataType() . "(" . $pField->m_nDataIndex . ");");
					Output(" }\n");
				}
			Output("\t}\n");
		}
	};

	class NetObjectFilter
	{
  		public $m_sName;
		public $m_sObjectName;
		public $m_pFieldArray;
  		
		function __construct(string $sName, string $sObjectName, array $pFieldArray)
		{
			$this->m_sName = $sName;
			$this->m_sObjectName = $sObjectName;
			$this->m_pFieldArray = $pFieldArray;
		}

		public function Output()
		{
			Output("\tclass " . $this->m_sName . " : NetObject::Filter\n");
			Output("\t{\n");

				Output("\t\tpublic static " . $this->m_sName . " __pStatic = null;\n");
				Output("\t\tpublic construct()\n");
				Output("\t\t{\n");
				Output("\t\t\tAssert::Plz(__pStatic == null);\n");
				Output("\t\t\t__pStatic = this;\n");
				
				Output("\t\t}\n");
				Output("\t\tpublic destruct() { __pStatic = null; }\n");
			Output("\t}\n");
		}
	};

	function NetObject_Output($pObjectArray, $sContainerName = "NetObjectStatic")
	{
		for ($i = 0; $i < sizeof($pObjectArray); $i++)
		{
			if ($i > 0)
				Output("\n");

			$pObject = $pObjectArray[$i];
			$pObject->Output();
		}

		Output("\n");
		Output("\tclass " . $sContainerName . "\n");
		Output("\t{\n");
			for ($i = 0; $i < sizeof($pObjectArray); $i++)
			{
				if ($i > 0)
					Output("\n");

				$pObject = $pObjectArray[$i];
				$sPostFix = "";
				if (is_a($pObject, "NetObject"))
					$sPostFix = "Info";
				Output("\t\tpublic " . $pObject->m_sName . $sPostFix . "* m_p" . $pObject->m_sName . $sPostFix . ";");
				// info vs filter...
			}
			Output("\n");
			Output("\t\tpublic construct()\n");
			Output("\t\t{\n");
			for ($i = 0; $i < sizeof($pObjectArray); $i++)
			{
				$pObject = $pObjectArray[$i];
				$sPostFix = "";
				if (is_a($pObject, "NetObject"))
					$sPostFix = "Info";
				Output("\t\t\tm_p" . $pObject->m_sName . $sPostFix . " = own new " . $pObject->m_sName . $sPostFix . "();\n");
			}
			Output("\t\t}\n");
		Output("\t}\n");
	}
?>