<?php
class rating{

	public $average = 0;
	public $votes;
	public $status;
	public $table;
	private $path;
	
	function __construct($table){
		try{
			$pathinfo = pathinfo(__FILE__);
			$this->path = realpath($pathinfo['dirname']) . "/database/ratings.sqlite";
			$dbh = new PDO("sqlite:$this->path");
			$this->table = $dbh->quote($table);
			// check if table needs to be created
			$table_check = $dbh->query("SELECT * FROM $this->table WHERE id='1'");
			if(!$table_check){
				// create database table
				$dbh->query("CREATE TABLE $this->table (id INTEGER PRIMARY KEY, rating FLOAT(3,2), ip VARCHAR(15))");
				$dbh->query("INSERT INTO $this->table (rating, ip) VALUES (0, 'master')");				
			} else {
				$this->average = $table_check->fetchColumn(1);
			}
			$this->votes = ($dbh->query("SELECT COUNT(*) FROM $this->table")->fetchColumn()-1);
		}catch( PDOException $exception ){
				die($exception->getMessage());
		}
		$dbh = NULL;		
	}

	function set_score($score, $ip){
		try{
			$dbh = new PDO("sqlite:$this->path");
			$voted = $dbh->query("SELECT id FROM $this->table WHERE ip='$ip'");
			if(sizeof($voted->fetchAll())==0){
				
				$dbh->query("INSERT INTO $this->table (rating, ip) VALUES ($score, '$ip')");
				$this->votes++;
				
				//cache average in the master row
				$statement = $dbh->query("SELECT rating FROM $this->table");
				$total = $quantity = 0;
				$row = $statement->fetch(); //skip the master row
				while($row = $statement->fetch()){
					$total = $total + $row[0];
					$quantity++;
				}
				$this->average = round((($total*20)/$quantity),0);
				$statement = $dbh->query("UPDATE $this->table SET rating = $this->average WHERE id=1");
				$this->status = '(thanks!)';
			} else {
				$this->status = '(already scored)';
			}
			
		}catch( PDOException $exception ){
				die($exception->getMessage());
		}
		$dbh = NULL;
	}
}

function rating_form($table){
	$ip = $_SERVER["REMOTE_ADDR"];
	if(!isset($table) && isset($_GET['table'])){
		$table = $_GET['table'];
	}
	$rating = new rating($table);
	$status = "<div class='score'>
				<a class='score1' href='?score=1&amp;table=$table&amp;user=$ip'>1</a>
				<a class='score2' href='?score=2&amp;table=$table&amp;user=$ip'>2</a>
				<a class='score3' href='?score=3&amp;table=$table&amp;user=$ip'>3</a>
				<a class='score4' href='?score=4&amp;table=$table&amp;user=$ip'>4</a>
				<a class='score5' href='?score=5&amp;table=$table&amp;user=$ip'>5</a>
			</div>
	";
	if(isset($_GET['score'])){
		$score = $_GET['score'];
		if(is_numeric($score) && $score <=5 && $score >=1 && ($table==$_GET['table']) && isset($_GET["user"]) && $ip==$_GET["user"]){
			$rating->set_score($score, $ip);
			$status = $rating->status;
		}
	}
	if(!isset($_GET['update'])){ echo "<div class='rating_wrapper'>"; }
	?>
	<div class="sp_rating">
		<div class="rating">Rating:</div>
		<div class="base"><div class="average" style="width:<?php echo $rating->average; ?>%"><?php echo $rating->average; ?></div></div>
		<div class="votes"><?php echo $rating->votes; ?> votes</div>
		<div class="status">
			<?php echo $status; ?>
		</div>
	</div>
	<?php
	if(!isset($_GET['update'])){ echo "</div>"; }
}

if(isset($_GET['update'])&&isset($_GET['table'])){
	rating_form($_GET['table']);
}
