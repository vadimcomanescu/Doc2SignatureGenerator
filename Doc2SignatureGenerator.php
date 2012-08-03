<?php

class Doc2SignatureGenerator {

	static function generate($extensionName, $outputFile, $docDir) {
		$functionDocFiles = self::getFunctionDocFiles($docDir, $extensionName);
		$signatures = array();
		foreach ($functionDocFiles as $functionDocFile)
			$signatures[] = self::generateSignatureData(self::getDocFileContentsAsArray($docDir, $functionDocFile));
		self::writeToOutputFile(self::stringifySignatures($signatures), $outputFile);
	}

	private static function writeToOutputFile($stringifiedSignatures, $outputFile) {
		file_put_contents($outputFile, $stringifiedSignatures);
	}
	
	private static function lineDefinesClassName($line) {
		return preg_match('/class="classname"/', $line);
	}
	
	private static function lineDefinesMethodName($line) {
		return preg_match('/class="methodname"/', $line);	
	}
	
	private static function lineDefinesMethodParam($line) {
		return preg_match('/class="methodparam"/', $line);
	}
	
	private static function parseMethodNameFrom($line) {
		if (preg_match('#class="methodname"><strong>(\S+)</strong>#', $line, $matches)) return $matches[1];
		if (preg_match('#class="methodname">(\S+)</a>#', $line, $matches)) return $matches[1];
	}
	
	private static function parseMethodParamsFrom($line) {
		if (preg_match('#class="methodparam">void</span>#', $line)) return array('paramVariable' => null, 'optional' => false);
		preg_match('#<code class=("parameter"|"parameter reference")>(\S+)</code>#', $line, $matches);
		$isOptionalParam = preg_match('/\[/', $line) ? true : false;
		return array('paramVariable' => $matches[2], 'optional' => $isOptionalParam);
	}
	
	private static function parseClassNameFrom($line) {
		preg_match('/class="classname">(\S+)</U', $line, $matches);
		return $matches[1];
	}
	
	private static function generateSignatureData($docFileContents) {
		$signatureData = array();
		foreach ($docFileContents as $line) {
			if (self::lineDefinesClassName($line)) $signatureData['classname'] = self::parseClassNameFrom($line);
			if (self::lineDefinesMethodName($line)) $methodName = self::parseMethodNameFrom($line);
			if (self::lineDefinesMethodParam($line)) {
				if (!$methodName) continue;
				$signatureData['methods'][$methodName][] = self::parseMethodParamsFrom($line);		
			}
		}
		return $signatureData;
	}
	
	private static function stringifySignatures($signatures) {
		$result = "<?php\n";
		$signatures = self::splitSignaturesInOOAndProcedural($signatures);
		foreach ($signatures['oo'] as $className => $data) 
			$result .= self::stringifySignatureData(array('classname' => $className, 'methods' => $data['methods']));
		$result .= self::stringifySignatureData($signatures['procedural']);	
		return $result;
	}
	
	private static function splitSignaturesInOOAndProcedural($signatures) {
		$sigs = array();
		foreach ($signatures as $signatureData) {
			if (!isset($signatureData['classname'])) {
				if (!isset($sigs['procedural'])) $sigs['procedural'] = array();
				$sigs['procedural'] = array_merge($sigs['procedural'], $signatureData);
			} else {
				if (!isset($sigs['oo'][$signatureData['classname']]['methods'])) $sigs['oo'][$signatureData['classname']]['methods'] = array();
				$sigs['oo'][$signatureData['classname']]['methods'] = array_merge($sigs['oo'][$signatureData['classname']]['methods'], $signatureData['methods']);
			}
		}
		return $sigs;
	}
	
	private static function stringifySignatureData($signatureData) {
		if (isset($signatureData['classname']))  {
			$classname = $signatureData['classname'];
			unset($signatureData['classname']);
			return sprintf("class %s {\n%s}\n", $classname, self::stringifyMethodWithParams($signatureData['methods'], $classname));
		}
		return self::stringifyMethodWithParams($signatureData['methods']);
	}
	
	private static function stringifyMethodWithParams($methodWithParams, $className = null) {
		$result = '';
		foreach ($methodWithParams as $methodName => $paramData) {
			$result .= sprintf("%sfunction %s(%s);\n", $className ? '    ' : '', str_ireplace($className.'_', '', $methodName), implode(", ", array_map(function ($e) {
				return $e['optional'] ? $e['paramVariable'] . ' = null' : $e['paramVariable'];
			}, $paramData)));
		}
		return $result;
	}
	
	private static function getDocFileContentsAsArray($docDir, $docFile) {
		return file($docDir . '/' . $docFile);
	}

	private static function getFunctionDocFiles($docDir, $extensionName) {
		return array_values(array_filter(scandir($docDir), function ($file) use ($extensionName){
			return preg_match("/function.$extensionName|class.$extensionName.html/", $file);
		}));
	}
}
