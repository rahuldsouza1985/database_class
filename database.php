<?php
session_start();
    class database {
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
		
		private function sql($query, $parameters, $additional_parameters = false) {
			$stmt = $this->dbh->prepare($query);
			$stmt->execute($parameters);
			if(isset($additional_parameters) and !empty($additional_parameters)) {
				if($additional_parameters === 'insert') {
					return $this->dbh->lastInsertId();
				} else {
					return $stmt->fetchAll(PDO::FETCH_ASSOC);
				}
			}
		}
		
		public function create($parameters, $table) {
			extract($parameters);
			
			return $this->sql("INSERT INTO $table(".implode(',', $columns).") VALUES(".array_keys($data).")", $data, 'insert');
		}
		
		public function read($parameters, $tables_joins, $conditions) {
			$columns = array();
			$sql="SELECT ";
			$query_parameters = array();
			extract($tables_joins);
			foreach($tables as $table) {
				$columns[$table]=$this->sql("SHOW COLUMNS FROM $table", array());
				$column_names=array();
				foreach($columns[$table] as $c) {
					$column_names["$table.$c"]="$table.$c";
				}
				$query_parameters[$table]=implode(',', $column_names);
			}
			$sql.=implode(',', $query_parameters).' FROM '.$tables[0];
			$i=1;
			foreach($joins as $join) {
				$sql.=" LEFT OUTER JOIN ".$table[$i]." ON ($join) ";
				$i++;
			}
			extract($conditions);
			if($where !== NULL) {
				$sql.= " WHERE $where ";
			}
			if($order_by !== NULL) {
				$sql.= " ORDER BY $order_by ";
			}
			if($group_by !== NULL) {
				$sql.= " GROUP BY $group_by ";
			}
			if($limit !== NULL) {
				extract($limit);
				$sql.= " LIMIT $start, $end ";
			}
			
			return $this->sql($sql, $parameters);
		}
		
		public function update_delete($parameters, $table, $condition, $mode=NULL) {
			if($mode !== NULL) {
				$new_data=array();
				foreach($parameters as $column => $data) {
					$new_data[]="$column=$data";
				}
				$this->sql("UPDATE $table SET ".implode(',', $new_data)." WHERE $condition", $parameters);
			} else {
				$this->sql("DELETE FROM $table WHERE $condition", $parameters);
			}
		}
	}
?>
