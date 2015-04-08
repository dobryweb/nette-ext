<?php

/**
 * In-browser file view response support for Nette Framework (instead of download, for example PDF file).
 *
 * Copyright (c) 2015 Dobry web (http://www.dobryweb.cz)
 * This software is licensed under the New BSD License.
 *
 * Version:     1.0
 * Tested Nette Framework:     2.1.9
 */

namespace Dobryweb\NetteExt\Application\Responses;

use Nette;


/**
 * In-browser view file response
 *
 * @author     Vaclav Jirovsky
 * @author     David Grudl
 *
 * @property-read string $file
 * @property-read string $name
 * @property-read string $contentType
 */
class FileViewResponse extends Nette\Application\Responses\FileResponse
{
	
	/**
	 * Sends response to output.
	 * @return void
	 */
	public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse)
	{
		$httpResponse->setContentType($this->contentType);
		$httpResponse->setHeader('Content-Disposition', 'inline; filename="' . $this->name . '"');

		$filesize = $length = filesize($this->file);
		$handle = fopen($this->file, 'r');

		if ($this->resuming) {
			$httpResponse->setHeader('Accept-Ranges', 'bytes');
			if (preg_match('#^bytes=(\d*)-(\d*)\z#', $httpRequest->getHeader('Range'), $matches)) {
				list(, $start, $end) = $matches;
				if ($start === '') {
					$start = max(0, $filesize - $end);
					$end = $filesize - 1;

				} elseif ($end === '' || $end > $filesize - 1) {
					$end = $filesize - 1;
				}
				if ($end < $start) {
					$httpResponse->setCode(416); // requested range not satisfiable
					return;
				}

				$httpResponse->setCode(206);
				$httpResponse->setHeader('Content-Range', 'bytes ' . $start . '-' . $end . '/' . $filesize);
				$length = $end - $start + 1;
				fseek($handle, $start);

			} else {
				$httpResponse->setHeader('Content-Range', 'bytes 0-' . ($filesize - 1) . '/' . $filesize);
			}
		}

		$httpResponse->setHeader('Content-Length', $length);
		while (!feof($handle) && $length > 0) {
			echo $s = fread($handle, min(4e6, $length));
			$length -= strlen($s);
		}
		fclose($handle);
	}

}
