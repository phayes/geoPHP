<?php
/**
 * This file contains the BinaryReader class.
 * For more information see the class description below.
 *
 * @author Peter Bathory <peter.bathory@cartographia.hu>
 * @since 2016-02-18
 *
 * This code is open-source and licenced under the Modified BSD License.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace geoPHP\Adapter;

/**
 * Helper class BinaryReader
 *
 * A simple binary reader supporting both byte orders
 */
class BinaryReader {

	const BIG_ENDIAN = 0;
	const LITTLE_ENDIAN = 1;

	private $buffer;
	private $endianness = 0;

	/**
	 * BinaryReader constructor.
	 * Opens a memory buffer with the given input
	 *
	 * @param string $input
	 */
	public function __construct($input) {
//		if (@is_readable($input)) {
//			$this->buffer = fopen($input, 'r+');
//		} else {
			$this->buffer = fopen('php://memory', 'x+');
			fwrite($this->buffer, $input);
			fseek($this->buffer, 0);
//		}
	}

	/**
	 * Closes the memory buffer
	 */
	public function close() {
		fclose($this->buffer);
	}

	/**
	 * @param self::BIG_ENDIAN|self::LITTLE_ENDIAN $endian
	 */
	public function setEndianness($endian) {
		$this->endianness = $endian === self::BIG_ENDIAN ? self::BIG_ENDIAN : self::LITTLE_ENDIAN;
	}

	/**
	 * @return int Returns 0 if reader is in BigEndian mode or 1 if in LittleEndian mode
	 */
	public function getEndianness() {
		return $this->endianness;
	}

	/**
	 * Reads a signed 8-bit integer from the buffer
	 * @return int|null
	 */
	public function readSInt8() {
		$char = fread($this->buffer, 1);
		return $char !== '' ? current(unpack("c", $char)) : null;
	}

	/**
	 * Reads an unsigned 8-bit integer from the buffer
	 * @return int|null
	 */
	public function readUInt8() {
		$char = fread($this->buffer, 1);
		return $char !== '' ? current(unpack("C", $char)) : null;
	}

	/**
	 * Reads an unsigned 32-bit integer from the buffer
	 * @return int|null
	 */
	public function readUInt32() {
		$int32 = fread($this->buffer, 4);
		return $int32 !== '' ? current(unpack($this->endianness == self::LITTLE_ENDIAN ? 'V' : 'N', $int32)) : null;
	}

	/**
	 * Reads one or more double values from the buffer
	 * @param int $length How many double values to read. Default is 1
	 * @return float[]
	 */
	public function readDoubles($length = 1) {
		$bin = fread($this->buffer, $length);
		return $this->endianness == self::LITTLE_ENDIAN
				? array_values(unpack("d*", $bin))
				: array_reverse(unpack("d*", strrev($bin)));
	}

	/**
	 * Reads an unsigned base-128 varint from the buffer
	 *
	 * Ported from https://github.com/cschwarz/wkx/blob/master/lib/binaryreader.js
	 *
	 * @return int
	 */
	public function readUVarInt() {
		$result = 0;
		$bytesRead = 0;

		do {
			$nextByte = $this->readUInt8();
			$result += ($nextByte & 0x7F) << (7 * $bytesRead);
			$bytesRead++;
		} while ($nextByte >= 0x80);
		return $result;
	}

	/**
	 * Reads a signed base-128 varint from the buffer
	 *
	 * @return int
	 */
	public function readSVarInt() {
		return self::ZigZagDecode($this->readUVarInt());
	}

	/**
	 * ZigZag decoding maps unsigned integers to signed integers
	 *
	 * @param int $value Encrypted positive integer value
	 * @return int Decoded signed integer
	 */
	public static function ZigZagDecode ($value) {
		return ($value & 1) === 0 ? $value >> 1 : -($value >> 1) - 1;
	}
}
