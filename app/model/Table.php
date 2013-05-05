<?php
namespace GLOTR;
use Nette;
use Nette\Security as NS;
class Table extends Nette\Object
{
	/** @var \Nette\Database\Connection */
	protected $connection;
	/** @var string Name of the table used by model*/
	protected $tableName;
	protected $container;
	protected $apiFile;
	protected $count;

	/** @var string $columnListPrefix prefix used by \GLOTR\Table::getPrefixedColumnList method */
	protected $columnListPrefix;
	public function __construct(Nette\Database\Connection $database,  Nette\DI\Container $container)
	{
		$this->connection = $database;

		if($this->tableName === NULL)
		{
			$class =  get_class($this);
			throw new Nette\InvalidStateException("Table name must be filled in $class::\$tableName");

		}
		$this->container = $container;
		$this->tableName = $this->container->parameters["tablePrefix"].$this->tableName;
		$this->columnListPrefix = "_".$this->tableName."_";
	}
	/**
	 * returns whole table from db
	 * @return \Nette\Database\Table\Selection
	 */
	public function getTable()
	{
		return $this->connection->table($this->tableName);
	}
	/**
	 * returns all records from table
	 * @return \Nette\Database\Table\Selection
	 */
	public function findAll()
	{
		return $this->getTable();
	}
	/**
	 * returns matched records from table
	 * @param array $by
	 * @return \Nette\Database\Table\Selection
	 */
	public function findBy(array $by)
	{
		return $this->getTable()->where($by);
	}
	/**
	 *	returns the first matched record from table
	 * @param array $by
	 * @return \Nette\Database\Table\ActiveRow|FALSE
	 */
	public function findOneBy(array $by)
	{
		return $this->findBy($by)->limit(1)->fetch();
	}
	/**
	 * Returns record by id
	 * @param int $id
	 * @return \Nette\Database\Table\ActiveRow|FALSE
	 */
	public function find($id)
	{
		return $this->getTable()->get($id);
	}

	/**
		* Return database connection
		* @return \Nette\Database\Connection
		*/
	public function getConnection()
	{
		return $this->connection;
	}
	public function getTableName()
	{
		return  $this->tableName;
	}
	/**
	 * Get list of all table columns for SELECT statement with prefixed alias
	 * @return string list of columns with , at the end
	 */
	public function getPrefixedColumnList()
	{
		$cols = $this->getTable()->limit(1)->fetch();
		if($cols)
		{
			$columns = " ";
		foreach($cols as $name => $col)
		{
			$columns .= "$this->tableName.$name as $this->columnListPrefix"."$name, ";
		}
		return $columns;
		}

    }
    public function getColumns()
    {
        $cols = array();
        $data = $this->getConnection()->query("Show columns from $this->tableName");
        while($col = $data->fetch())
        {
            $cols[$col->Field] =(array) $col;
        }
        return $cols;
    }
	/**
	 * checks if model needs update from Ogame API
	 * @return boolean
	 */
	public function needApiUpdate()
	{
		return ($this->container->config->load("$this->tableName-finished")+$this->container->parameters["ogameApiExpirations"][$this->apiFile] < time());
	}

	public function ogameApiGetFileNeeded()
	{
		if($this->needApiUpdate())
		{
			return $this->container->ogameApi->url.$this->apiFile;
		}
		else
			return false;
	}
	protected function getMoonsFromPlanets($planets)
	{
		$moons = array();
			foreach($planets as $planet)
				if($planet["moon_size"] || $planet["moon_res_updated"])
					$moons[] = $planet;
		return $moons;
	}
	protected function invokeQuery($query, $params)
	{
		$args = array();
		$args[] = $query;
		$args = array_merge($args, $params);
		$method = new \ReflectionMethod("Nette\Database\Connection", "query");

		$res = $method->invokeArgs($this->connection, $args);

		$results = array();

		while($result = $res->fetch())
		{
			$results[] = $result;
		}
		return $results;
	}
	protected function chunkedMultiQuery($query, $delimiter = ';')
	{
		$mysqli = $this->container->mysqli;
		$r = $mysqli->query("SHOW VARIABLES LIKE 'max_allowed_packet'");
		$max = 1024*1024; // DEFAULT settings
		if($r)
		{
			$max = $r->fetch_row();
			$max = $max[1];
		}
		$max -= 1024; // just to be safe
		if(strlen($query) > $max)
		{
			$tmp = explode($delimiter, $query);
			if($max < strlen($tmp[0]))
			{
				throw new Nette\Application\ApplicationException("Mysql max_allowed_packet too small!");
				return false;
			}
			else
			{
				$queryPart = "";
				$len = 0;
				$i = 0;
				$count = count($tmp);
				foreach($tmp as $t)
				{
					$i++;
					$t .= ";";
					//$mysqli->query($t); continue;
					if(($len+strlen($t)) < $max)
					{
						$len += strlen($t);
						$queryPart .= $t;
						if($i == $count)
						{
							$mysqli->multi_query($queryPart);
							while ($mysqli->more_results() && $mysqli->next_result()){}
						}

					}
					else
					{

						$mysqli->multi_query($queryPart);
						while ($mysqli->more_results() && $mysqli->next_result()){}
						$queryPart = $t;
						$len = strlen($queryPart);
					}
				}

			}
		}
		else
		{
			$mysqli->multi_query($query);
			while ($mysqli->more_results() && $mysqli->next_result()){}
		}

		return false;
	}
	public function getCount()
	{
		if(!$this->count)
		{
			$this->count = $this->getTable()->count("*");
		}
		return $this->count;
	}
}
