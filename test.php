<?php

/**
 * Class Travel
 * 用于验证一定数字范围内 “1+1=2” 即哥德巴赫猜想
 */
class Travel{
	private $primeFile = '素数集合.txt';
	private $explainFile = 'explain.txt';
	private $exceptionFile = 'exception.txt';
	public function __invoke(){
		try{
			$start = $this->getExplain();
			for($i=$start;$i<500000;$i++){
				// 可以被分解成
				for ($j=1;$j<$i;$j++){
					// 是否是素数
					if ($this->isPrime($j)){
						$anotherNumber = $i-$j;
						// 证实是素数后缓存
						if ($this->isPrime($anotherNumber)) {
							$this->logExplain($i,$j,$anotherNumber);
							break 1;
						}
					}
					if ($j==$i-1) {
						throw new Exception('当前i='.$i);
					}
				}
				$i++;
			}
		}catch (\Throwable $e){
			echo $e->getTraceAsString();
            file_put_contents($this->exceptionFile, $e->getMessage());
		}finally{

		}
	}
	function isPrime($number){
		$arr = $this->getFromLogPrime();
		if ($number==1){
			return false;
		}
		if (in_array($number,$arr)) {
			return true;
		}
		for ($i=2;$i<$number;$i++){
			if($number%$i==0){
				return false;
			}
		}
		$this->logPrime($number);
		return true;
	}

	function logPrime($prime){
		file_put_contents(__DIR__.'/'.$this->primeFile,$prime.',',FILE_APPEND);
	}

	function getFromLogPrime(){
		$content = file_get_contents(__DIR__.'/'.$this->primeFile);
		return $content ? explode(',',$content) : [];
	}

	function logExplain($number,$a,$b){
		$str = "{$number}={$a}+{$b}".PHP_EOL;
		echo $str;
		file_put_contents(__DIR__.'/'.$this->explainFile, $str, FILE_APPEND);
	}

	function getExplain(){
		$initial = 2;
		$fp = file(__DIR__.'/'.$this->explainFile);
		$count=count($fp);
		if ($count==0) {
			return $initial;
		}
		$last = $fp[$count-1];
		$lastArr = explode('=',$last);
		$explain=$lastArr[0];
		return $explain ? $explain+2 : $initial;
	}
}

$t=new Travel();
$t();

/**
 * 从最后一行开始读取
 * @param $filepath
 * @param int $lines
 * @param int $skip
 * @param bool $adaptive
 * @return bool|string
 */
function tailWithSkip($filepath, $lines = 1, $skip = 0, $adaptive = true)
{
    // Open file
    $f = @fopen($filepath, "rb");
    if (@flock($f, LOCK_SH) === false) return false;
    if ($f === false) return false;

    // Sets buffer size, according to the number of lines to retrieve.
    // This gives a performance boost when reading a few lines from the file.
    $max=max($lines, $skip);
    if (!$adaptive) $buffer = 4096;
    else $buffer = ($max < 2 ? 64 : ($max < 10 ? 512 : 4096));

    // Jump to last character
    fseek($f, -1, SEEK_END);

    // Read it and adjust line number if necessary
    // (Otherwise the result would be wrong if file doesn't end with a blank line)
    if (fread($f, 1) == "\n") {
        if ($skip > 0) { $skip++; $lines--; }
    } else {
        $lines--;
    }

    // Start reading
    $output = '';
    $chunk = '';
    // While we would like more
    while (ftell($f) > 0 && $lines >= 0) {
        // Figure out how far back we should jump
        $seek = min(ftell($f), $buffer);

        // Do the jump (backwards, relative to where we are)
        fseek($f, -$seek, SEEK_CUR);

        // Read a chunk
        $chunk = fread($f, $seek);

        // Calculate chunk parameters
        $count = substr_count($chunk, "\n");
        $strlen = mb_strlen($chunk, '8bit');

        // Move the file pointer
        fseek($f, -$strlen, SEEK_CUR);

        if ($skip > 0) { // There are some lines to skip
            if ($skip > $count) { $skip -= $count; $chunk=''; } // Chunk contains less new line symbols than
            else {
                $pos = 0;

                while ($skip > 0) {
                    if ($pos > 0) $offset = $pos - $strlen - 1; // Calculate the offset - NEGATIVE position of last new line symbol
                    else $offset=0; // First search (without offset)

                    $pos = strrpos($chunk, "\n", $offset); // Search for last (including offset) new line symbol

                    if ($pos !== false) $skip--; // Found new line symbol - skip the line
                    else break; // "else break;" - Protection against infinite loop (just in case)
                }
                $chunk=substr($chunk, 0, $pos); // Truncated chunk
                $count=substr_count($chunk, "\n"); // Count new line symbols in truncated chunk
            }
        }

        if (strlen($chunk) > 0) {
            // Add chunk to the output
            $output = $chunk . $output;
            // Decrease our line counter
            $lines -= $count;
        }
    }

    // While we have too many lines
    // (Because of buffer size we might have read too many)
    while ($lines++ < 0) {
        // Find first newline and remove all text before that
        $output = substr($output, strpos($output, "\n") + 1);
    }

    // Close file and return
    @flock($f, LOCK_UN);
    fclose($f);
    return trim($output);
}


