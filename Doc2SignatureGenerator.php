<?php

class ExtFuncSignatureGenerator {

	static function generate($extensionName, $outputFile, $docDir) {
		$functionDocFiles = self::getFunctionDocFiles($docDir, $extensionName);
		$signatures = array();
		foreach ($functionDocFiles as $functionDocFile)
			$signatures = array_merge($signatures, self::generateFunctionSignatureData(self::getDocFileContentsAsArray($docDir, $functionDocFile)));
		self::writeToOutputFile(self::stringifySignatures($signatures), $outputFile);
	}

	private static function writeToOutputFile($stringifiedSignatures, $outputFile) {
		$output = sprintf("<?php\n%s", $stringifiedSignatures);
		file_put_contents($outputFile, $output);
	}
	
	private static function lineDefinesMethodName($line) {
		return preg_match('/class="methodname"/', $line);	
	}
	
	private static function lineDefinesMethodParam($line) {
		return preg_match('/class="methodparam"/', $line);
	}
	
	private static function parseMethodNameFrom($line) {
		if (preg_match('/<strong>(\S+)<\/strong>/', $line, $matches)) return $matches[1];
		if (preg_match('/class="methodname">(\S+)<\/a>/', $line, $matches)) return $matches[1];
	}
	
	private static function parseMethodParamsFrom($line) {
		if (!preg_match('/<code class="parameter">(\S+)<\/code>/', $line, $matches)) return array('paramVariable' => null, 'optional' => null);
		$isOptionalParam = preg_match('/\[/', $line) ? true : false;
		return array('paramVariable' => $matches[1], 'optional' => $isOptionalParam);
	}
	
	private static function generateFunctionSignatureData($docFileContents) {
		$functionSignatureData = array();
		foreach ($docFileContents as $line) {
			if (self::lineDefinesMethodName($line)) $methodName = self::parseMethodNameFrom($line);
			if (self::lineDefinesMethodParam($line)) {
				if (!$methodName) continue;
				$functionSignatureData[$methodName][] = self::parseMethodParamsFrom($line);		
			}
		}
		return $functionSignatureData;
	}
	
	private static function stringifySignatures($signatures) {
		$result = '';
		foreach ($signatures as $methodName => $paramData) {
			$result .= sprintf("function %s(%s);\n", $methodName, implode(", ", array_map(function ($e) {
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
			return preg_match("/function.$extensionName|class.$extensionName/", $file);
		}));
	}
}
