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
	//protected $container;
	protected $params = array();
	protected $count;
	protected $columns = array();
	protected $mysqli;

	/** @var string $columnListPrefix prefix used by \GLOTR\Table::getPrefixedColumnList method */
	protected $columnListPrefix;
	public function __construct(Nette\Database\Connection $database,  Nette\DI\Container $container, LazyMysqli $mysqli)
	{
		$this->connection = $database;
		if($this->tableName === NULL)
		{
			$class =  get_class($this);
			throw new Nette\InvalidStateException("Table name must be filled in $class::\$tableName");

		}
		$this->params = $container->parameters;
		$this->tableName = $this->params["tablePrefix"].$this->tableName;
		$this->columnListPrefix = "_".$this->tableName."_";
		$this->mysqli = $mysqli;
		$this->setup();
	}
	/**
	 * Setup the object
	 */
	protected function setup()
	{}
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
	/**
	 * Get database columns names
	 * @return array
	 */
    public function getColumns()
    {
		if(empty($this->columns))
		{
			$this->columns = array();
			$data = $this->getConnection()->query("Show columns from $this->tableName");
			while($col = $data->fetch())
			{
				$this->columns[$col->Field] =(array) $col;
			}
		}

        return $this->columns;
    }
	/**
	 * Run query on database connection and fetch the results
	 * @param string $query
	 * @param array $params parameters to bind to the query
	 * @return array
	 */
	public function invokeQuery($query, $params)
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
	/**
	 * Runs mysli::multi_query on database, splits query into smaller chunks if neccessary
	 * @param string $query
	 * @param string $delimiter
	 * @return boolean
	 * @throws Nette\Application\ApplicationException
	 */
	protected function chunkedMultiQuery($query, $delimiter = ';')
	{
		$mysqli = $this->mysqli;
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

		return true;
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
