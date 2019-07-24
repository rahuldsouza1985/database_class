<?php
session_start();
class Database {
	private $dbh;
	
	public function __construct() {
		try {
			$this->dbh = new PDO(DB_DSN, DB_USER, DB_PASSWORD);
			$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch(PDOException $e) {
			echo '<pre>';
			print_r($e);
			die;
		}			
	}
	
	private function run_query($query, $parameters, $additional_parameters = false) {
		$stmt = $this->dbh->prepare($query);
		foreach($parameters as $placeholder => $value) {
			$stmt->bindValue($placeholder, htmlentities($value));
		}
		$debug_query = str_replace(array_keys($parameters), array_values($parameters), $query);
		$stmt->execute() or die("$debug_query<br/>".print_r($stmt->errorInfo(), true));
		if(isset($additional_parameters) and !empty($additional_parameters)) {
			if($additional_parameters === 'insert') {
				return $this->dbh->lastInsertId();
			}
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
	}
	
	public function create($parameters, $table) {
		return $this->run_query(
			"INSERT INTO $table(".
			str_replace(':', '', implode(', ', array_keys($parameters))).
			") VALUES(".
			implode(', ', array_keys($parameters)).
			")", 
			$parameters, 
			'insert'
		);
	}
	
	public function read($parameters, $tables_joins, $conditions) {
		$columns = array();
		$sql = "SELECT ";
		$query_parameters = array();
		extract($tables_joins);
		foreach($tables as $table) {
			$columns = $this->run_query("SHOW COLUMNS FROM $table", array(), true);
			$column_names = array();
			foreach($columns as $c) {
				$column_names["$table.{$c['Field']}"] = "$table.{$c['Field']} AS '$table.{$c['Field']}'";
			}
			$query_parameters[$table] = implode(',', $column_names);
		}
		$sql .= implode(',', $query_parameters)." FROM {$tables[0]}";
		if(array_key_exists('joins', $tables_joins)) {
			foreach($joins as $join) {
				$sql .= " LEFT OUTER JOIN $join ";
			}
		}
		extract($conditions);
		if(array_key_exists('where', $conditions)) {
			$sql .= " WHERE $where ";
		}
		if(array_key_exists('order_by', $conditions)) {
			$sql .= " ORDER BY $order_by ";
		}
		if(array_key_exists('group_by', $conditions)) {
			$sql .= " GROUP BY $group_by ";
		}
		if(array_key_exists('having', $conditions)) {
			$sql .= " HAVING $having ";
		}
		if(array_key_exists('limit', $conditions)) {
			extract($limit);
			$sql .= " LIMIT $start, $end ";
		}
		return $this->run_query($sql, $parameters, true);
	}
	
	public function update_delete($parameters, $table, $condition, $mode = NULL) {
		if($mode !== NULL) {
			$new_data=array();
			foreach($parameters as $column => $data) {
				$new_data[] = str_replace(':', '', $column)." = $column";
			}
			$this->run_query("UPDATE $table SET ".implode(',', $new_data)." WHERE $condition", $parameters);
		} else {
			$this->run_query("DELETE FROM $table WHERE $condition", $parameters);
		}
	}
	
	public function single_inflate(&$dataset, $table_name, $id, $columns) {
		foreach($columns as $key => $c) {
			$columns[$key] = "$table_name.$c AS '$table_name.$c'";
		}
		$row = $this->run_query("SELECT ".implode(",", $columns)." FROM $table_name WHERE $table_name.recid = :id", array(':id' => $id));
		$dataset = array_merge($dataset,$row);
	}
	
	public function __destruct() {
		$this->dbh = NULL;
	}
}
$db_obj = new Database();
?>
