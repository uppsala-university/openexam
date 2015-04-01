<?php

namespace OpenExam\Models;

use OpenExam\Library\Model\Filter;
use OpenExam\Library\Security\Roles;
use OpenExam\Plugins\Security\Model\ObjectAccess;
use Phalcon\Mvc\Model;

/**
 * Base class for all models.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ModelBase extends Model
{

        protected function initialize()
        {
                if (defined('MODEL_ALWAYS_USE_MASTER_CONNECTION') && MODEL_ALWAYS_USE_MASTER_CONNECTION) {
                        $this->setReadConnectionService('dbwrite');
                        $this->setWriteConnectionService('dbwrite');
                } else {
                        $this->setReadConnectionService('dbread');
                        $this->setWriteConnectionService('dbwrite');
                }
        }

        /**
         * Get model resource name.
         * @return string
         */
        public function getResourceName()
        {
                return strtolower(substr(strrchr(get_class($this), "\\"), 1));
        }

        /**
         * Get relation map.
         * @param string $resource The resource name.
         * @param string $leftcol The left hand column.
         * @param string $rightcol The right hand column.
         * @param string $rightres The right hand resource.
         * @return string
         */
        public static function getRelation($resource, $leftcol = null, $rightcol = null, $rightres = null)
        {
                if (isset($rightres)) {
                        return
                            __NAMESPACE__ . '\\' . ucfirst($resource) . '.' . $leftcol . '=' .
                            __NAMESPACE__ . '\\' . ucfirst($rightres) . '.' . $rightcol;
                } elseif (isset($rightcol)) {
                        return __NAMESPACE__ . '\\' . ucfirst($resource) . '.' . $leftcol . '=' . $rightcol;
                } elseif (isset($leftcol)) {
                        return __NAMESPACE__ . '\\' . ucfirst($resource) . '.' . $leftcol;
                } else {
                        return __NAMESPACE__ . '\\' . ucfirst($resource);
                }
        }

        /**
         * Prepare query parameters. 
         * 
         * We have to support parameters as passed to both query builder and 
         * find. To add complexity, sometimes a comma separated list defines
         * them and sometimes it uses an array.
         * 
         * @param string $class The name of the model class.
         * @param array $parameters The query parameters.
         * @return array
         */
        protected static function getParameters($class, $parameters)
        {
                // 
                // Handle conditions (e.g. name = ?0 or name = :name:):
                // 
                if (isset($parameters['conditions'])) {
                        if (is_string($parameters['conditions'])) {
                                $parameters['conditions'] = preg_replace("/(\[?[a-z\\\\]+\]?\.)?(\[?\w+?\]?) (=|between|in)/i", "${class}.$2 $3", $parameters['conditions']);
                        }
                }

                // 
                // Handle e.g. findFirst() or relational arguments:
                // 
                foreach (array_keys($parameters) as $index) {
                        if (is_int($index)) {
                                $parameters[$index] = preg_replace("/(\[?[a-z\\\\]+\]?\.)?(\[?\w+?\]?) (=|between|in)/i", "${class}.$2 $3", $parameters[$index]);
                        }
                }

                // 
                // Handle order conditions:
                // 
                if (isset($parameters['order'])) {
                        if (is_string($parameters['order'])) {
                                $parameters['order'] = explode(",", $parameters['order']);
                        }
                        foreach ($parameters['order'] as $index => $value) {
                                if (strpos($value, '.')) {
                                        continue;
                                }
                                $parameters['order'][$index] = $class . '.' . $parameters['order'][$index];
                        }
                }

                return $parameters;
        }

        /**
         * Get model access control object.
         * @return ObjectAccess
         */
        public function getObjectAccess()
        {
                $class = sprintf("OpenExam\Plugins\Security\Model\%sAccess", ucfirst($this->getResourceName()));
                return new $class();
        }

        /**
         * Get filter for result set.
         * @param array $params The query parameters.
         * @return Filter The result set filter object.
         */
        public function getFilter($params)
        {
                return null;
        }

        protected function afterFetch()
        {
                $modelsManager = $this->getModelsManager();
                $eventsManager = $modelsManager->getEventsManager();
                $eventsManager->fire('model:afterFetch', $this);
        }

        protected function beforeValidationOnUpdate()
        {
                // 
                // Check that required attributes are set:
                // 
                $required = $this->getModelsMetaData()->getNotNullAttributes($this);

                foreach ($required as $attr) {
                        if (!isset($this->$attr)) {
                                $populate = true;
                                break;
                        }
                }

                if (!isset($populate)) {
                        return;
                }

                $class = get_class($this);
                $model = $class::findFirst("id = $this->id");

                foreach ($required as $attr) {
                        if (!isset($this->$attr)) {
                                $this->$attr = $model->$attr;
                        }
                }

                // 
                // Now it gets even more ugly:
                // 
                $attributes = get_object_vars($this);
                foreach ($attributes as $attr => $value) {
                        if (!isset($this->$attr) && isset($model->$attr)) {
                                $this->$attr = $model->$attr;
                        }
                }
        }

        /**
         * Saves related records that must be stored prior to save the 
         * master record.
         * 
         * @param \Phalcon\Db\AdapterInterface $connection
         * @param \Phalcon\Mvc\ModelInterface[] $related
         * @return bool 
         */
        protected function _preSaveRelatedRecords($connection, $related)
        {
                // 
                // Only perform access control on the master record. Bypass
                // ACL for related records using the system role.
                // 
                $user = $this->getDI()->get('user');
                $role = $user->setPrimaryRole(Roles::SYSTEM);

                $txlevel = $connection->getTransactionLevel();

                try {
                        $result = parent::_preSaveRelatedRecords($connection, $related);
                } catch (\Exception $exception) {
                        while ($connection->getTransactionLevel() > $txlevel) {
                                try {
                                        $connection->rollback();
                                } catch (\PDOException $e) {
                                        // ignore
                                }
                        }
                        $user->setPrimaryRole($role);
                        throw $exception;
                }

                $user->setPrimaryRole($role);
                return $result;
        }

}
