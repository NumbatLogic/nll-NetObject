<?
	global $g_pNetObjectArray;
	$g_pNetObjectArray = array();

	function GetPrefix($sType)
	{
		switch ($sType)
		{
			case "string": return "s";
			case "Uint32": return "n";
		}
		return "p";
	}

	class NetObjectField
	{
		public string $m_sType;
		public string $m_sName;
		public bool $m_bVector;

		function __construct(string $sType, string $sName, bool $bVector)
		{
			$this->m_sType = $sType;
			$this->m_sName = $sName;
			$this->m_bVector = $bVector;
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
	}


	class NetObject
	{
  		public $m_sName;
		public $m_pFieldArray;
  		
		function __construct(string $sName, array $pFieldArray)
		{
			$this->m_sName = $sName;
			$this->m_pFieldArray = $pFieldArray;
		}


/*should partial objects be distinct?
or just a expose list?

MinimalMember::SubPack(pMember);
changesets woudl be weird with this?

MinimalMemberChangeset::Pack(pMemberChangeset);
// this is just sugar for pMemberChangeset.Pack(privacy struct);
// toplevel can hardcode at the partial class level, can use exposure array at lower levels?

// client can hard crash if server accedentally packs wihout expose filter
// so we shouldn't accedentally pack secrets...
*/

		/*public static function CreatePartial(string $sName, NetObject $pBaseObject, array $sExposedFieldArray)
		{
			$pFieldArray = array();
			for ($i = 0; $i < sizeof($sExposedFieldArray); $i++)
			{
				$sExposedField = $sExposedFieldArray[$i];

				$j = 0;
				$pBaseField = null;
				for ($j = 0; $j < sizeof($pBaseObject->m_pFieldArray); $j++)
				{
					$pBaseField = $pBaseObject->m_pFieldArray[$j];
					if ($pBaseField->m_sName == $sExposedField)
						break;
				}

				if ($j >= sizeof($pBaseObject->m_pFieldArray))
					throw new Exception("not found!!!");

				$pFieldArray[] = $pBaseField;
			}

			$pNetObject = new NetObject($sName, $pFieldArray);

			return $pNetObject;
		}*/

		public function Output()
		{
			if (sizeof($this->m_pFieldArray) == 0)
			{
				echo $this->m_sName . " has no fields, skipping!!!!\n";
				return;
			}

			Output("\tclass " . $this->m_sName . "Info : NetObject::Info\n");
			Output("\t{\n");
			Output("\t\tpublic construct()\n");
			Output("\t\t{\n");
			for ($j = 0; $j < sizeof($this->m_pFieldArray); $j++)
			{
				$pField = $this->m_pFieldArray[$j];

			//	Output("\t\t\t__pFieldInfoVector.PushBack(new NetObject::FieldInfo(TYPE, \"" . $pField->GetMemberName() . "\"));\n");
			}
			Output("\t\t}\n");
			Output("\t}\n");
			Output("\n");

			Output("\tclass " . $this->m_sName . " : NetObject::Object\n");
			Output("\t{\n");

			for ($j = 0; $j < sizeof($this->m_pFieldArray); $j++)
			{
				$pField = $this->m_pFieldArray[$j];

				Output("\t\tpublic " . $pField->GetMemberType());
				if ($pField->IsCustomType())
					Output("*");
				Output(" " . $pField->GetMemberName() . ";\n");
			}
			Output("\n");

			Output("\t\tpublic construct()\n");
			Output("\t\t{\n");
			for ($j = 0; $j < sizeof($this->m_pFieldArray); $j++)
			{
				$pField = $this->m_pFieldArray[$j];
				if ($pField->IsString())
					Output("\t\t\t" . $pField->GetMemberName() . " = own new InternalString(\"\");\n");
			}
			Output("\t\t}\n");
			Output("\n");

			for ($j = 0; $j < sizeof($this->m_pFieldArray); $j++)
			{
				$pField = $this->m_pFieldArray[$j];
				Output("\t\tpublic " . $pField->m_sType . " Get" . $pField->m_sName . "() { ");
				Output("return " . $pField->GetMemberName());
				if ($pField->IsString())
					Output(".GetExternalString()");
				Output("; }\n");
			}
			Output("\n");

			// blob
			Output("\t\tpublic void Pack(gsBlob pBlob, voidptr pFieldFilter = null)\n");
			Output("\t\t{\n");
			for ($j = 0; $j < sizeof($this->m_pFieldArray); $j++)
			{
				$pField = $this->m_pFieldArray[$j];

				if ($pField->IsCustomType())
					Output("\t\t\t" . $pField->GetMemberName() . ".Pack(pBlob);\n");
				else
					Output("\t\t\tpBlob.Pack" . $pField->GetBlobPackType() . "(" . $pField->GetMemberName() . ");\n");
			}
			Output("\t\t}\n");

			Output("\t\tpublic bool Unpack(gsBlob pBlob)\n");
			Output("\t\t{\n");

			Output("\t\t\treturn ");

			for ($j = 0; $j < sizeof($this->m_pFieldArray); $j++)
			{
				$pField = $this->m_pFieldArray[$j];
				if ($j > 0)
					Output(" &&\n\t\t\t\t\t");
				if ($pField->IsCustomType())
					Output($pField->GetMemberName() . ".Unpack(pBlob)");
				else
					Output("pBlob.Unpack" . $pField->GetBlobPackType() . "(" . $pField->GetMemberName() . ")");
			}
			Output(";\n");
			Output("\t\t}\n");
			Output("\n");

			// IsEqual
			Output("\t\tpublic bool IsEqual(" . $this->m_sName . " pOther)\n");
			Output("\t\t{\n");

			Output("\t\t\treturn ");

			for ($j = 0; $j < sizeof($this->m_pFieldArray); $j++)
			{
				$pField = $this->m_pFieldArray[$j];
				if ($j > 0)
					Output(" &&\n\t\t\t\t\t");
				if ($pField->IsString())
					Output($pField->GetMemberName() . ".IsEqual(pOther." . $pField->GetMemberName() . ".GetExternalString())");
				else
					Output($pField->GetMemberName() . " == pOther." . $pField->GetMemberName());
			}
			Output(";\n");
			Output("\t\t}\n");

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
			Output("\tclass " . $this->m_sName . "Info : NetObject::Filter\n");
			Output("\t{\n");
			Output("\t}\n");
		}
	};

	function NetObject_Output($pObjectArray)
	{
		for ($i = 0; $i < sizeof($pObjectArray); $i++)
		{
			if ($i > 0)
				Output("\n");

			$pObject = $pObjectArray[$i];

			$pObject->Output();
		}
	}
?>