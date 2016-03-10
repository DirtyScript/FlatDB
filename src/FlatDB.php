<?php
/**
 * DS FlatDB - Dirty Script Flat Data Base
 * 
 * a flat file basic data base
 * manage datas by key(id)
 * 
 * This is a BETA, so, please, don't use this script in production.
 * If you want to impove, debug (...), go to the github of this project.
 * Thanks
 * 
 * @package   DirtyScript
 * @author    RemRem <remrem@dirty-script.com>
 * @copyright Copyright (C) dirty-script.com,  All rights reserved.
 * @licence   MIT
 * @version   0.01.001 beta
 * @link      http://dirty-script/Data-Base
 * @link      https://github.com/Dirty-Script/Data-Base
 */

/**
 * CHANGELOG
 * 
 * 0.01.001 2016-03-09
 *  - test integration to composer
 * 
 * 0.01.000 2016-03-05
 *  - add db_file_exists() to check if db file exists
 *  - add param auto_create_db to __construct, default, false
 *  - rename exception class from DSSBException to FlatDBException
 *  - add MIT Licence legal stuff (see below)
 * 
 * 0.00.701 2016-02-29
 *  - change version format X.X.X > X.XX.XXX
 *  - update some part to use with composer
 *  - rename class from DSDB to FlatDB
 * 
 * 0.0.7 2014-11-19
 *  - add data_next_available_key()
 *  - add data_get_last_line()
 *  - remove data('test') and all tests linked to data('test')
 * 
 * 0.0.6 2014-10-11
 *  - add $last_error
 *  - add get_last_error()
 */

/**
 * The MIT License (MIT)
 * Copyright (c) 2016 RemRem <remrem@dirty-script.com>
 * 
 * Permission is hereby granted, free of charge, to any person obtaining 
 * a copy of this software and associated documentation files 
 * (the "Software"), to deal in the Software without restriction, 
 * including without limitation the rights to use, copy, modify, merge, 
 * publish, distribute, sublicense, and/or sell copies of the Software, 
 * and to permit persons to whom the Software is furnished to do so, 
 * subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included 
 * in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS 
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF 
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. 
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY 
 * CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, 
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE 
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */


namespace DirtyScript\FlatDB;

class FlatDB
{

	/**
	 * current version
	 */
	private $version = '0.01.004';

	/**
	 * database
	 */
	private $datas = array();

	/**
	 * database file
	 */
	private $db_file = '';

	/**
	 * last error
	 */
	private $last_error = '';

	/**
	 * constructor
	 * 
	 * @param string $db_file absolute path to db file
	 * @param bool $auto_create_db for create db file if not exist
	 */
	public function __construct( $db_file , $auto_create_db = false ){
		$this->db_file = $db_file.'.json.gz.php';

		// create db file
		if (!$this->db_file_exists()){
			if ($auto_create_db !== false){
				$this->db_create();
			} else {
				throw new FlatDBException('db file ' . $this->db_file .' doesn\'t exists !');
				return false;
			}
		}

		// read the db
		$this->db_read();
	}

	/**
	 * check if db file exists
	 */
	public function db_file_exists(){
		return file_exists($this->db_file);
	}

	/**
	 * create the datas base file
	 *  and put data test
	 * 
	 * @return array
	 */
	public function db_create(){
		if (false === file_put_contents($this->db_file,"<?php /* <!--\n",LOCK_EX)){
			throw new FlatDBException('Could not create file ' . $this->db_file);
			return false;
		}
		return true;
	}

	/**
	 * rewrite database file 
	 *  and put data test
	 *  and put all datas
	 * 
	 * @return bool
	 */
	private function db_rewrite(){
		$lines = '';

		foreach ($this->datas as $data_id => $datas){
			$line = json_encode(array($data_id => $datas),JSON_FORCE_OBJECT);
			$line = substr($line,1);
			$line = substr($line,0,-1);
			$line = gzcompress($line,3);
			$lines .= str_replace(array("\r","\n"),array('{{r}}','{{n}}'),$line)."\n";
		}

		if ($this->db_create($this->db_file)){
			if (false === file_put_contents($this->db_file, $lines, FILE_APPEND | LOCK_EX)){
				throw new FlatDBException('Could not push data in db file ' . $this->db_file);
			} else {
				return true;
			}
		}
		$this->last_error = 'db_rewrite() fail on db_create()';
		return false;
	}

	/**
	 * @return string the last error
	 */
	public function get_last_error(){
		return $this->last_error;
	}

	/**
	 * read db file
	 * 
	 * @return array()
	 */
	public function db_read(){
		$i = 0;
		$handle = fopen($this->db_file,"r");
		$lines = '';

		if (!$handle) {
			// error opening the file.
			throw new FlatDBException('db file read (FAIL)');
		}

		while (($line = fgets($handle)) !== false) {
			++$i;
			// skip the first line
			if ($i !== 1){
				$lines .= gzuncompress(str_replace( array('{{r}}','{{n}}'),array("\r","\n"),$line) ) .',';
			}
		}

		fclose($handle);

		// avoid db read on empty file (file < 16 octets)
		if (empty($lines) && filesize($this->db_file) < 16){
			$this->datas = array();
			return array();
		}

		$datas = json_decode( '{'. trim($lines,',') .'}' ,true);
		$this->datas = $datas;
		return $datas;
	}

	/**
	 * reset the db
	 * 
	 * @return bool
	 */
	public function db_reset(){
		$this->datas = array();
		return $this->db_create($this->db_file);
	}

	/**
	 * create a backup (simple file copy)
	 * @param string $backup_name optional the name of the backup
	 * if empty $backup_name add '-backup' before extension ('.json.gz.php')
	 * add '.json.gz.php' to the file name
	 */
	public function db_backup( $backup_name = '' ){
		if (empty($backup_name)){
			$backup_file = str_replace('.json.gz.php','-backup.json.gz.php',$this->db_file);
		} else {
			$backup_file = str_replace('.json.gz.php', '-'.$backup_name .'.json.gz.php',$this->db_file);
		}
		return copy($this->db_file, $backup_file);
	}

	/**
	 * get somes infos about the db
	 * 
	 * @return array
	 */
	public function db_infos(){
		$infos = stat($this->db_file);
		$infos['line'] = count($this->datas);
		return $infos;
	}

	/**
	 * return an export of the db
	 */
	public function db_export( $format = 'json' ){
		if ($format == 'csv'){
			$contents = '';
			$handle = fopen('php://temp', 'r+');
			foreach ($this->datas as $line) {
				fputcsv($handle, $line, ',', '"');
			}
			rewind($handle);
			while (!feof($handle)){
				$contents .= fread($handle,8192);
			}
			fclose($handle);
			return $contents;
		}

		if ($format == 'json'){
			return json_encode( $this->datas , JSON_FORCE_OBJECT);
		}

		if ($format == 'xml'){
			$xml = new SimpleXMLElement('<DS/>');
			foreach ($this->datas as $id => $datas){
				$e = $xml->addChild('data');
				foreach ($datas as $key => $value){
					$e->addChild($key,$value);
				}
			}
			return $xml->asXML();
		}

		if ($format == 'serialize'){
			return serialize( $this->datas );
		}

		$this->last_error = 'unknow export format';
		return false;
	}

	/**
	 * push data in db
	 * 
	 * @param string $data_id id(key) for data ; if (is_null) auto key
	 * @param multiple $data the data to store
	 * @param bool $overwritedata over write data if already key exist
	 * @return false or $data_id
	 */
	public function data_push( $data_id, $data, $overwritedata = false ){
		if (empty( $data_id ) && $data_id !== 0 && !is_null( $data_id )){
			$this->last_error = 'data id is empty';
			return false;
		}
		if (is_null( $data_id )){
			$data_id = $this->data_next_available_key();
		}

		$line = json_encode(array($data_id => $data),JSON_FORCE_OBJECT);
		$line = substr($line,1);
		$line = substr($line,0,-1);
		$line = gzcompress($line,3);
		$line = str_replace(array("\r","\n"),array('{{r}}','{{n}}'),$line)."\n";

		// push
		if ($overwritedata === true){
			if (!$this->data_key_exists($data_id)){
				$this->datas[$data_id] = $data;
				$success = file_put_contents($this->db_file, $line, FILE_APPEND | LOCK_EX);
				if (false === $success){
					throw new FlatDBException('Unable to push datas in db file, check the read/write access to your dir_db');
				}
			} else {
				$this->datas[$data_id] = $data;
				$success = $this->db_rewrite();
			}
			if ($success){
				// return true;
				return $data_id;
			} else {
				unset($this->datas[$data_id]);
				$this->last_error = 'fail on write db file';
				return false;
			}
		} else if ($overwritedata === false){
			if (!$this->data_key_exists($data_id)){
				$this->datas[$data_id] = $data;
				if (false === file_put_contents($this->db_file, $line, FILE_APPEND | LOCK_EX)){
					throw new FlatDBException('Unable to push datas in db file, check the read/write access to your dir_db');
				}
				// return true;
				return $data_id;
			} else {
				$this->last_error = 'data id alreay exists';
				return false;
			}
		}
		throw new FlatDBException('unknow error');
		return false;
	}


	/**
	 * get datas by id
	 * 
	 * @param string|int $data_id
	 * @return array
	 */
	public function data_get( $data_id ){
		if (!isset($this->datas[$data_id]) || !array_key_exists( $data_id , $this->datas )){
			return null;
		}
		return $this->datas[$data_id];
	}

	/**
	 * return the (int)X last line of the db
	 * 
	 * @param int $last
	 * @return array
	 */
	public function data_get_last_line( $last = 5 ){
		return array_slice($this->datas, (int)-$last, $last, true);
	}

	/**
	 * @param string|int $data_id
	 * @retun bool
	 */
	public function data_key_exists( $data_id ){
		return array_key_exists($data_id,$this->datas);
	}

	/**
	 * @return array all keys
	 */
	public function data_keys(){
		return array_keys( $this->datas );
	}

	/**
	 * @return int the next available key
	 */
	public function data_next_available_key( $last_key = null ){
		if (is_null( $last_key )){
			$last_key = $this->data_last_key();
			if (is_null( $last_key )){
				$last_key = 0;
			}
		}

		while ($this->data_key_exists($last_key)){
			$last_key++;
			$last_key = $this->data_next_available_key($last_key);
		}

		return $last_key;
	}

	/**
	 * @return int the last key
	 */
	public function data_last_key(){
		end( $this->datas );
		return key( $this->datas );
	}

	/**
	 * @return int the first key
	 */
	public function data_first_key(){
		reset( $this->datas );
		return key( $this->datas );
	}

	/**
	 * remove data by his id
	 * 
	 * @param string|int $data_id
	 * @return bool
	 */
	public function data_remove($data_id){
		if (isset( $this->datas[$data_id] )){
			unset( $this->datas[$data_id] );
			return $this->db_rewrite();
		}
		return true;
	}

	/**
	 * test a value
	 * 
	 * return @bool
	 */
	private function data_test( $string , $test ){
		if ($test == 'is_email'){return filter_var($string, FILTER_VALIDATE_EMAIL);}
		if ($test == 'is_numeric'){return is_numeric( $string );}
		if ($test == 'is_string'){return is_string( $string );}
		if ($test == 'is_array'){return is_array( $string );}
		if ($test == 'is_int'){return is_int( $string );}
		if ($test == 'is_blank'){return empty($string) && !is_numeric($string);}
		if ($test == 'is_url'){if (!filter_var($string, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)){return false;} else {return true;}}
		if (strpos( $test, '>=' ) === 0){return ( $string >= substr( $test , 2 ) );}					// 1982>=  $string
		if (strpos( $test, '>' ) === 0){return ( substr( $test , 1 ) < $string );}						// 1982>  $string
		if (strpos( $test, '<=' ) === 0){return ( $string <= substr( $test , 2 ) );}					// 1982<=  $string
		if (strpos( $test, '<' ) === 0){return ( substr( $test , 1 ) > $string );}						// 1982<  $string
		if (strpos( $test, '==' ) === 0){return ( $string == substr( $test , 2 ) );}					// 1982==  $string
		if (strpos( $test, 'strpos:' ) === 0){return (strpos( $string, substr( $test , 7 ) ) !== false);}	// a tester
		if (strpos( $test, '!strpos:' ) === 0){return (strpos( $string, substr( $test , 8 ) ) === false);}	// a tester

		// if (strpos( $test, 'regex:' ) === 0){return substr( $string , 6 );}

		return false;
	}

	/**
	 * search data
	 * @param array $test
	 * use a -(int) key when your db values are not an array
	 *      array(
	 *        'key-1' => array('is_int', '>10'),
	 *        'key-2' => array('!empty')
	 * @return array founded
	 */
	public function data_search( $tests , $limit = 10){
		$found = array();
		$i = 0;
		foreach ($tests as $test_key => $test_test){
			// search when data is a string
			if (is_numeric($test_key) && $test_key < 0){
				if ($i === 0){
					foreach ($this->datas as $id => $data){
						if (!is_string($data)){continue;}
						if ($this->data_test($data,$test_test)){
							$found[] = $id;
						}
					}
				}
			// search when data is an array
			} else {
				foreach ($this->datas as $id => $data){
					if (!isset($data[$test_key])){continue;}
					if ($i === 0){
						if ($this->data_test($data[$test_key],$test_test)){
							$found[] = $id;
						}
					}
					
				}
			}
			$i++;
		}

		return $found;
	}
}

class FlatDBException extends Exception {}