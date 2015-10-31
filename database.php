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
		
		public function sql($query, $parameters, $additional_parameters = false) {
			$stmt = $this->dbh->prepare($query);
			$stmt->execute($parameters);
			if(isset($additional_parameters) and !empty($additional_parameters)) {
				if($additional_parameters === 'insert') {
					return $this->dbh->lastInsertId();
				} else {
					return $stmt->fetchAll(PDO::FETCH_OBJ);
				}
			}
		}
	}
?>
