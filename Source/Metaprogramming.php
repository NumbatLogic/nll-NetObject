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

		function __construct(string $sType, string $sName)
		{
			$this->m_sType = $sType;
			$this->m_sName = $sName;
			$this->m_bVector = false;
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
			if ($this->m_bVector)
				return "Vector";
			return "Object";
		}

		function GetFieldInfoType()
		{
			switch ($this->m_sType)
			{
				case "Uint32": return "NetObject::FieldInfo::Type::UINT32";
				case "string": return "NetObject::FieldInfo::Type::STRING";
			}
			if ($this->m_bVector)
				return "NetObject::FieldInfo::Type::VECTOR";
			return "NetObject::FieldInfo::Type::OBJECT";
		}
	}

	class NetObjectVectorField extends NetObjectField
	{
		public array $m_sLookupFieldArray;
		function __construct(string $sType, string $sName, array $sLookupFieldArray)
		{
			parent::__construct($sType, $sName);
			$this->m_sLookupFieldArray = $sLookupFieldArray;
			$this->m_bVector = true;
			if (!$this->IsCustomType())
				throw new Error("vectors must be custom types");
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

		public function Output(array $pObjectArray)
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
				Output("\t\tpublic static " . $this->m_sName . "Info GetStatic() { Assert::Plz(__pStatic != null); return __pStatic; }\n");

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
									Output("\t\t\t\tcase " . $j . ": return new " . $pField->m_sType . "();\n");
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

					if ($pField->m_bVector)
					{
						$pVectorObject = null;
						for ($k = 0; $k < sizeof($pObjectArray); $k++)
						{
							if ($pObjectArray[$k]->m_sName == $pField->m_sType)
							{
								$pVectorObject = $pObjectArray[$k];
								break;
							}
						}

						if ($pVectorObject == null)
							throw new Error("Unable to find type for vector object " . $pField->m_sType);


						Output("\t\tpublic int GetNum" . $pField->m_sName . "() { return __GetVectorSize(" . $pField->m_nDataIndex . "); }\n");
						Output("\t\tpublic " . $pField->m_sType . " Get" . $pField->m_sName . "ByIndex(int nIndex) { return cast " . $pField->m_sType . "(__GetVectorObject(" . $pField->m_nDataIndex . ", nIndex)); }\n");

						// add custom lookup fields
						if (sizeof($pField->m_sLookupFieldArray) > 0)
						{
							for ($k = 0; $k < sizeof($pField->m_sLookupFieldArray); $k++)
							{
								$sLookupField = $pField->m_sLookupFieldArray[$k];

								$pVectorObjectField = null;
								for ($l = 0; $l < sizeof($pVectorObject->m_pFieldArray); $l++)
								{
									if ($pVectorObject->m_pFieldArray[$l]->m_sName == $sLookupField)
									{
										$pVectorObjectField = $pVectorObject->m_pFieldArray[$l];
										break;
									}
								}

								if ($pVectorObjectField == null)
									throw new Error("Unable to find lookup field " . $sLookupField);

								if ($pVectorObjectField->IsCustomType())
									throw new Error("Cannot lookup by a field with a custom type " . $sLookupField . " " . $pVectorObjectField->m_sType);

								$sParamName = GetPrefix($pVectorObjectField->m_sType) . $pVectorObjectField->m_sName;
								
								Output("\t\tpublic " . $pField->m_sType . " Get" . $pField->m_sName . "By" . $pVectorObjectField->m_sName);
									Output("(" . $pVectorObjectField->m_sType . " " . $sParamName . ") { return cast " . $pField->m_sType . "(");
									Output("__GetVectorObjectBy" . $pVectorObjectField->GetDataType() . "(" . $pField->m_nDataIndex . ", " . $pVectorObjectField->m_nDataIndex . ", " . $sParamName . ")); }\n");
							}
						}
					}
					else
					{
						Output("\t\tpublic " . $pField->m_sType . " Get" . $pField->m_sName . "() { ");
						if ($pField->IsCustomType())
							Output("return cast " . $pField->m_sType . "(__Get" . $pField->GetDataType() . "(" . $pField->m_nDataIndex . ", $j));");
						else
							Output("return __Get" . $pField->GetDataType() . "(" . $pField->m_nDataIndex . ", $j);");
						Output(" }\n");
					}
				}
			Output("\t}\n");
		}
	};

	class NetObjectFilter
	{
  		public $m_sName;
		public $m_sObjectName;
		public $m_xFieldFilterMap;
  		
		function __construct(string $sName, string $sObjectName, array $xFieldFilterMap)
		{
			$this->m_sName = $sName;
			$this->m_sObjectName = $sObjectName;
			$this->m_xFieldFilterMap = $xFieldFilterMap;
		}

		public function Output(array $pObjectArray)
		{
			$i = 0;
			$pObject = null;
			for ($i = 0; $i < sizeof($pObjectArray); $i++)
			{
				$pObject = $pObjectArray[$i];
				if ($pObject->m_sName == $this->m_sObjectName)
					break;
			}
			if ($i >= sizeof($pObjectArray))
				throw new Error("Object not found " . $this->m_sObjectName);
			
			Output("\tclass " . $this->m_sName . " : NetObject::Filter\n");
			Output("\t{\n");

				Output("\t\tpublic static " . $this->m_sName . " __pStatic = null;\n");
				Output("\t\tpublic construct() : base(" . sizeof($pObject->m_pFieldArray) . ")\n");
				Output("\t\t{\n");
				Output("\t\t\tAssert::Plz(__pStatic == null);\n");
				Output("\t\t\t__pStatic = this;\n");

				$this->SubOutput($pObject, $this->m_xFieldFilterMap, $pObjectArray, 3, "");
				
				Output("\t\t}\n");
				Output("\t\tpublic destruct() { __pStatic = null; }\n");
				Output("\t\tpublic static " . $this->m_sName . " GetStatic() { Assert::Plz(__pStatic != null); return __pStatic; }\n");
			Output("\t}\n");
		}

		public function SubOutput($pObject, array $xFieldFilterMap, array $pObjectArray, $nDepth, $sPrefix)
		{
			foreach($xFieldFilterMap as $sField => $xValue)
			{
				$nFieldIndex = 0;
				$pField = null;
				for ($nFieldIndex = 0; $nFieldIndex < sizeof($pObject->m_pFieldArray); $nFieldIndex++)
				{
					$pField = $pObject->m_pFieldArray[$nFieldIndex];
					if ($pField->m_sName == $sField)
						break;
				}
				if ($nFieldIndex >= sizeof($pObject->m_pFieldArray))
					throw new Error("coudn't find field " . $sField);

				$sPadding = "";
				for ($i = 0; $i < $nDepth; $i++)
					$sPadding .= "\t";

				if (is_array($xValue))
				{
					if (!$pField->IsCustomType())
						throw new Error("Field " . $sField . " is not an object so cannot have nested filter!");

					$i = 0;
					$pFieldObject = null;
					for ($i = 0; $i < sizeof($pObjectArray); $i++)
					{
						$pFieldObject = $pObjectArray[$i];
						if ($pFieldObject->m_sName == $pField->m_sType)
							break;
					}

					if ($i >= sizeof($pObjectArray))
						throw new Error("Unable to find type for sub field " . $pField->m_sType);

					Output($sPadding . "{\n");
						Output($sPadding . "\tFilter p" . $nDepth . " = " . $sPrefix . "__ExposeFieldCreateFilter(" . $nFieldIndex . ", 999);\n");
						$this->SubOutput($pFieldObject, $xValue, $pObjectArray, $nDepth+1, "p" . $nDepth . ".");
					Output($sPadding . "}\n");
				}
				else
				{
					if ($xValue)
						Output($sPadding . $sPrefix . "__ExposeField(" . $nFieldIndex . ");\n");
				}
			}
			
		}
	};

	function NetObject_Output($pObjectArray, $sContainerName = "NetObjectStatic")
	{
		for ($i = 0; $i < sizeof($pObjectArray); $i++)
		{
			if ($i > 0)
				Output("\n");

			$pObject = $pObjectArray[$i];
			$pObject->Output($pObjectArray);
		}

		Output("\n");
		Output("\tclass " . $sContainerName . "\n");
		Output("\t{\n");
			Output("\t\tpublic NetObject::ChangeMap* __pChangeMap;\n");
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
			Output("\t\t\t__pChangeMap = own new NetObject::ChangeMap();\n");
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