<?php
namespace GLOTR;
use Nette;
class Table extends Nette\Object
{
	/** @var \Nette\Database\Connection */
	protected $connection;
	/** @var string Name of the table used by model*/
	protected $tableName;
	protected $container;
	protected $apiFile;
	/** @var string $columnListPrefix prefix used by \GLOTR\Table::getPrefixedColumnList method */
	protected $columnListPrefix;
	public function __construct(Nette\Database\Connection $database, Nette\DI\Container $container)
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
}
