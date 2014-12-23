<?php

namespace Vertex\Framework\Modeling;
use Vertex\Framework\Database;
use Vertex\Framework\Input;

/**
 * @property mixed id
 */
class Model {
    /**
     * @var Database
     */
	private $db;

	protected $table = NULL;
	public $idField = 'id';

	public $isNew = true;

	public $attributes = [];
	protected $exclude = [];

	public function __construct() {
        /** @var Application $app */
        global $app;
        $this->db = $app->db;
		if ($this->table === NULL) {
			$refl = new \ReflectionClass(get_class($this));
			$this->table = $app->getConfig('database', 'prefix').strtolower($refl->getShortName()).'s';
		}
	}

	public function getModelName() {
		$refl = new \ReflectionClass(get_class($this));
		return $refl->getShortName();
	}

	public function hydrate($data, $clear = true) {
        if ($clear)
		    $this->attributes = [];
		foreach ($data as $key=>$val) {
			if (in_array($key, $this->exclude))
				continue;
			$this->attributes[($key)] = ($val);
		}
	}


    public function hydrateFromInput() {
        $data = [];
        /** @var ModelField $field */
        foreach ($this->getSchema()->fields as $field) {
            if ($field->isInversedForeignKey() || $field->isManyToMany())
                continue;
            $value = Input::get($field->getName(), NULL);
            if ($value !== NULL)
                $data[$field->getName()] = $value;
        }

        $this->hydrate($data, false);
    }

	public function id() {
		if (array_key_exists($this->idField, $this->attributes))
			return $this->attributes[$this->idField];
		else
			return NULL;
	}

	public function getIdField() {
		return $this->idField;
	}

	public function getTableName() {
		return $this->table;
	}

	public function get($attr) {
        /** @var ModelSchema $schema */
        $schema = $this->getSchema();

        /** @var ModelField $field */
        foreach ($schema->fields as $field) {
            if ($field->getName() == $attr) {
                if ($field->getType() == 'date' || $field->getType() == 'datetime')
                    return new \DateTime($this->attributes[$field->getName()]);

                else if ($field->getType() == 'boolean')
                    return boolval($this->attributes[$field->getName()]);

                else
                    return array_key_exists($attr, $this->attributes) ? $this->attributes[$field->getName()] : NULL;
            } else if ($field->isForeignKey() && $field->getOption('__fk_field') == $attr) {
                return $this->foreignKey($field->getOption('__fk_model'), $field->getName());
            } else if ($field->isInversedForeignKey() && $field->getOption('__ifk') == $attr) {
                return $this->inversedForeignKey($field->getOption('__ifk_model'), $field->getOption('__ifk_field'));
            } else if ($field->isManyToMany() && $field->getOption('__m2m_field') == $attr) {
                return $this->manyToMany($field->getOption('__m2m_model'), $field->getOption('__m2m'));
            }
        }

        foreach ($this->attributes as $key => $value)
            if ($key == $attr)
                return $value;

        return NULL;
	}

	public function __invoke($attr) {
		return $this->get($attr);
	}

	public function __get($name) {
		return $this->get($name);
    }

	public function __set($attr, $val) {
        /** @var ModelField $field */
        foreach ($this->getSchema()->fields as $field) {
            if ($field->getName() == $attr) {
                if ($field->getType() == 'date' && $val instanceof \DateTime) {
                    $this->attributes[$field->getName()] = $val->format('Y-m-d');
                    return;
                }
                else if ($field->getType() == 'datetime' && $val instanceof \DateTime) {
                    $this->attributes[$field->getName()] = $val->format('Y-m-d H:i:s');
                    return;
                }
                else if ($field->getType() == 'boolean' && is_bool($val)) {
                    $this->attributes[$field->getName()] = $val->format('Y-m-d H:i:s');
                    return;
                }
                else {
                    $this->attributes[$field->getName()] = $val;
                    return;
                }

            } else if ($field->isForeignKey() && $field->getOption('__fk_field') == $attr && $val instanceof Model) {
                $this->attributes[$field->getName()] = $val->id();
                return;
            } else if ($field->isInversedForeignKey() && $field->getOption('__ifk') == $attr && $val instanceof Model) {
                return;
            } else if ($field->isManyToMany() && $field->getOption('__m2m_field').'__add' == $attr && $val instanceof Model) {
                $query = "INSERT INTO ".$field->getOption('__m2m')." (";
                $query .= strtolower($this->getModelName())."_id, ";
                $query .= strtolower($field->getOption('__m2m_model'))."_id) VALUES(:id1, :id2)";
                $res = $this->db->success($query, [
                    'id1'=>$this->id(),
                    'id2'=>$val->id()
                ]);
                return;
            }
        }

        $this->attributes[$attr] = $val;
	}

	public function saveQuery() {
		if ($this->isNew) {
			$query = "INSERT INTO ".$this->table." (";
			$query .= implode(', ', array_keys($this->attributes));
			$query .= ") VALUES(:";
			$query .= implode(", :", array_keys($this->attributes));
			$query .= ")";
		} else {
			$query = "UPDATE ".$this->table." SET";
			$i = 0;
			foreach ($this->attributes as $attr => $val) {
//				if ($attr == $this->idField)
//					continue;
				$query .= ($i > 0 ? ',': '').' '.$attr." = :".$attr;
				$i++;
			}
			$query .= " WHERE ".$this->idField." = ".$this->id();
		}
		return $query;
	}
	public function save() {
		
		//return $query;
		$res = $this->db->success($this->saveQuery(), $this->attributes);
		if ($res && $this->isNew) {
			$this->isNew = false;
			$this->attributes[$this->getIdField()] = $this->db->lastInsertId();
		}
		return $res;
	}

	public function deleteQuery() {
		return 'DELETE FROM '.$this->table.' WHERE '.$this->idField.' = :id';
	}

	public function delete() {
		if ($this->isNew) {
			global $app;
            /** @var Application $app */
            $app->raise(500, "Unable to delete non-persited entity.");
		}
		return $this->db->success($this->deleteQuery(), ['id'=>$this->id()]);

	}

	protected function foreignKey($model, $field = NULL) {
		$repo = $this->db->repository($model);
        if ($field === NULL)
            $field = strtolower($model).'_id';
        if (!array_key_exists($field, $this->attributes))
            return NULL;
        return $repo->find($this->attributes[$field]);
	}

	protected function inversedForeignKey($model, $field = NULL) {
		$repo = $this->db->repository($model);
			if ($field === NULL)
				$field = strtolower($this->getModelName()).'_id';
			return $repo->query()->where($field, $this->id())->get();
	}

	protected function manyToMany($model, $table = NULL, $thisField = NULL, $otherField = NULL) {
		$thisModel = $this->getModelName();
		if ($thisField === NULL)
			$thisField = strtolower($thisModel).'_id';
		if ($otherField === NULL)
			$otherField = strtolower($model).'_id';
		if ($table === NULL) {
			if ($model < $thisModel)
				$table = $model.'_'.$thisModel;
			else
				$table = $thisModel.'_'.$model;
		}
		$qb = new QueryBuilder($table);
		$items = $qb->where($thisField, $this->id())->get();
		$ids = [];
		foreach ($items as $item)
			$ids[] = $item[$otherField];
		$repo = $this->db->repository($model);
		$entities =  $repo->findRange($ids);

		// We add the relation's attributes to each entities

		if (count($items) > 0 && count(array_keys($items[0])) > 2) {
			foreach ($items as $item) {
				$otherId = $item[$otherField];
				unset($item[$thisField]);
				unset($item[$otherField]);
                /** @var Model $entity */
                foreach ($entities as $entity) {
					if ($entity->id() == $otherId) {
						foreach ($item as $key => $val) {
							$entity->attributes['__rel'][($key)] = ($val);
						}
					}
				}
			}
		}
		//var_dump($entities);
		return $entities; 
	}

    public static function create($modelName) {
        $className = static::getFullModelName($modelName);
        return new $className;
    }

    public static function getFullModelName($modelName) {
        return 'App\\Models\\'.$modelName;
    }

    protected function schema(ModelSchema $schema) {
        return $schema;
    }

    public function getSchema() {
        $schema = new ModelSchema($this);
        return $this->schema($schema);
    }

    public function getRepository() {
        return new Repository($this->getModelName());
    }


}