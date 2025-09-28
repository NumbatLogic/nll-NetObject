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

	function GetBlobPackType($sType)
	{
		if ($sType == "string")
			return "InternalString";
		return $sType;
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
			if ($this->m_sType == "string")
				return "InternalString*";
			return $this->m_sType;
		}

		function GetMemberName()
		{
			return "__" . GetPrefix($this->m_sType) . $this->m_sName;
		}

		function IsString()
		{
			return $this->m_sType == "string";
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
	};

	function NetObject_Header($pObjectArray)
	{
		for ($i = 0; $i < sizeof($pObjectArray); $i++)
		{
			$pObject = $pObjectArray[$i];

			if (sizeof($pObject->m_pFieldArray) == 0)
			{
				echo $pObject->m_sName . " has no fields, skipping!!!!\n";
				continue;
			}

			Output("\tclass " . $pObject->m_sName . " : NetObject\n");
			Output("\t{\n");

			for ($j = 0; $j < sizeof($pObject->m_pFieldArray); $j++)
			{
				$pField = $pObject->m_pFieldArray[$j];
				Output("\t\tpublic " . $pField->GetMemberType() . " " . $pField->GetMemberName() . ";\n");
			}
			Output("\n");

			Output("\t\tpublic construct()\n");
			Output("\t\t{\n");
			for ($j = 0; $j < sizeof($pObject->m_pFieldArray); $j++)
			{
				$pField = $pObject->m_pFieldArray[$j];
				if ($pField->IsString())
					Output("\t\t\t" . $pField->GetMemberName() . " = own new InternalString(\"\");\n");
			}
			Output("\t\t}\n");
			Output("\n");

			for ($j = 0; $j < sizeof($pObject->m_pFieldArray); $j++)
			{
				$pField = $pObject->m_pFieldArray[$j];
				Output("\t\tpublic " . $pField->m_sType . " Get" . $pField->m_sName . "() { ");
				Output("return " . $pField->GetMemberName());
				if ($pField->IsString())
					Output(".GetExternalString()");
				Output("; }\n");
			}
			Output("\n");

			// blob
			Output("\t\tpublic void Pack(gsBlob pBlob)\n");
			Output("\t\t{\n");
			for ($j = 0; $j < sizeof($pObject->m_pFieldArray); $j++)
			{
				$pField = $pObject->m_pFieldArray[$j];
				Output("\t\t\tpBlob.Pack" . GetBlobPackType($pField->m_sType) . "(" . $pField->GetMemberName() . ");\n");
			}
			Output("\t\t}\n");

			Output("\t\tpublic bool Unpack(gsBlob pBlob)\n");
			Output("\t\t{\n");

			Output("\t\t\treturn ");

			for ($j = 0; $j < sizeof($pObject->m_pFieldArray); $j++)
			{
				$pField = $pObject->m_pFieldArray[$j];
				if ($j > 0)
					Output(" &&\n\t\t\t\t\t");
				Output("pBlob.Unpack" . GetBlobPackType($pField->m_sType) . "(" . $pField->GetMemberName() . ")");
			}
			Output(";\n");
			Output("\t\t}\n");
			Output("\n");

			// IsEqual
			Output("\t\tpublic bool IsEqual(" . $pObject->m_sName . " pOther)\n");
			Output("\t\t{\n");

			Output("\t\t\treturn ");

			for ($j = 0; $j < sizeof($pObject->m_pFieldArray); $j++)
			{
				$pField = $pObject->m_pFieldArray[$j];
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
		Output("\n");
	}
?>