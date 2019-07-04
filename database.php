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
			if(count($parameters) > 0) {
				foreach($parameters as $placeholder => $value) {
					switch($value['type'])
					{
						case 'PDO::PARAM_BOOL':
							$value['value'] = filter_var($value['value'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
						break;
						case 'PDO::PARAM_INT':
						case 'PDO::PARAM_LONG':
							$value['value'] = filter_var($value['value'], FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
						break;
						case 'PDO::PARAM_FLOAT':
						case 'PDO::PARAM_DOUBLE':
							$value['value'] = filter_var($value['value'], FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
						break;
						case 'PDO::PARAM_STR':
							$value['value'] = htmlentities($value['value']);
						break;
						case 'PDO::PARAM_TIMESTAMP':
							if(!preg_match('[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1]) (2[0-3]|[01][0-9]):[0-5][0-9]:[0-5][0-9]', $value['value'])) {
								$value['value'] = NULL;
							}
						break;
						default:
							$value['value'] = NULL;
						break;
					}
					if($value['value'] === NULL)
					{
						$value['value'] = '';
					}
					$stmt->bindValue($placeholder, $value['value'], $value['type']);
				}
				$stmt->execute();
			} else {
				$stmt->execute($parameters);
			}
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
				$columns = $this->sql("SHOW COLUMNS FROM $table", array());
				$column_names=array();
				foreach($columns as $c) {
					$column_names["$table.{$c['Field']}"]="$table.{$c['Field']} AS '$table.{$c['Field']}'";
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
			
			return $this->sql($sql, $parameters, true);
		}
		
		public function update_delete($parameters, $table, $condition, $mode=NULL) {
			if($mode !== NULL) {
				$new_data=array();
				foreach($parameters as $column => $data) {
					$new_data[]=str_replace(':', '', $column)."=$data";
				}
				$this->sql("UPDATE $table SET ".implode(',', $new_data)." WHERE $condition", $parameters);
			} else {
				$this->sql("DELETE FROM $table WHERE $condition", $parameters);
			}
		}
		
		public function single_inflate(&$dataset, $table_name, $id, $columns) {
			$row = $this->sql("SELECT ".implode(",", $columns)." FROM $table_name WHERE id=:id", array(':id' => $id));
			$dataset = array_merge($dataset,$row);
		}
		
		public function __destruct() {
			$this->dbh = NULL;
		}
	}
?>
